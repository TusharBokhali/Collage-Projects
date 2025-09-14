<?php include_once 'site_connection.php'; ?>

<?php 
// Razorpay Configuration - Test Mode (centralized config if present)
if (file_exists(__DIR__ . '/razorpay_config.php')) {
	include_once __DIR__ . '/razorpay_config.php';
	$creds = function_exists('getRazorpayCredentials') ? getRazorpayCredentials() : null;
	$razorpay_key_id = $creds ? $creds['key_id'] : (defined('RAZORPAY_TEST_KEY_ID') ? RAZORPAY_TEST_KEY_ID : '');
	$razorpay_key_secret = $creds ? $creds['key_secret'] : (defined('RAZORPAY_TEST_KEY_SECRET') ? RAZORPAY_TEST_KEY_SECRET : '');
}
else
{
	$razorpay_key_id = defined('RAZORPAY_TEST_KEY_ID') ? RAZORPAY_TEST_KEY_ID : '';
	$razorpay_key_secret = defined('RAZORPAY_TEST_KEY_SECRET') ? RAZORPAY_TEST_KEY_SECRET : '';
}

if(isset($_SESSION['login']))
{
$login_id = $_SESSION['login'];
$sql_select_login = "select * from `user_register` where `id`='$login_id'";
$data_login = mysqli_query($conn,$sql_select_login);
$row_login = mysqli_fetch_assoc($data_login);

$sql_select = "select * from `cart` where `user_id`='$login_id'";
$data = mysqli_query($conn,$sql_select);

// Check if cart has items
$cart_count = mysqli_num_rows($data);
if($cart_count == 0) {
    header('location:shoping-cart.php');
    exit();
}

$sql_select_pro_id = "select `product_id` from `cart` where `user_id`='$login_id'";
$data_pro_id = mysqli_query($conn,$sql_select_pro_id);


while ($row = mysqli_fetch_assoc($data_pro_id)) {
	$pro_id = $row['product_id'];

	$sql_select = "select * from `product` where `id`='$pro_id'";
	$data_price = mysqli_query($conn,$sql_select);
}

$amt_total = "select * from `cart` where `user_id`='$login_id'";
$data_total = mysqli_query($conn,$amt_total);

$total_price = 0;
while($row_total = mysqli_fetch_assoc($data_total))
{
	$total_price = $total_price + $row_total['price'] * $row_total['num_product'];
}

$sql_select_r = "select * from `user_register` where `id`='$login_id'";
$data_r = mysqli_query($conn,$sql_select_r);
$row_r = mysqli_fetch_assoc($data_r);
}
else
{
	header('location:login_home.php');
	exit();
}

// Manual test - Remove after testing
if (isset($_GET['manual_test']) && $_GET['manual_test'] == '1') {
	// Simulate successful payment
	$test_payment_id = 'test_' . time();
	$test_razorpay_order_id = 'test_order_' . time();
	
	// Create test order details in session
	$_SESSION['order_details'] = array(
		'address' => 'Test Address',
		'city' => 'Test City', 
		'pincode' => '123456',
		'name' => 'Test Customer',
		'mobile' => '9876543210',
		'email' => 'test@test.com',
		'date_time' => date('Y-m-d H:i:s'),
		'payment' => 'Test Payment',
		'amount' => 10000,
		'user_id' => $login_id,
		'total_price' => 100
	);
	
	// Add test product to cart
	$test_cart_insert = "INSERT INTO `cart` (`user_id`, `product_id`, `name`, `price`, `num_product`, `size`, `color`, `image`) VALUES ('$login_id', '1', 'Test Product', '100', '1', 'M', 'Red', 'test.jpg')";
	mysqli_query($conn, $test_cart_insert);
	
	// Process the test order
	processSuccessfulOrder($conn, $login_id, $test_payment_id, $test_razorpay_order_id);
	exit();
}

// Handle order submission
if (isset($_POST['buy'])) {
	$address = mysqli_real_escape_string($conn, $_POST['address']);
	$city = mysqli_real_escape_string($conn, $_POST['city']);
	$pincode = mysqli_real_escape_string($conn, $_POST['pincode']);
	$name = mysqli_real_escape_string($conn, $_POST['name']);
	$mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
	$email = mysqli_real_escape_string($conn, $_POST['email']);
	$date_time = $_POST['date_time'];
	$payment = mysqli_real_escape_string($conn, $_POST['payment']);
	
	// Validate required fields
	if(empty($address) || empty($city) || empty($pincode) || empty($name) || empty($mobile) || empty($email) || empty($payment)) {
		$error_message = "All fields are required!";
	} else {
		// Store order details in session for Razorpay
		$_SESSION['order_details'] = array(
			'address' => $address,
			'city' => $city,
			'pincode' => $pincode,
			'name' => $name,
			'mobile' => $mobile,
			'email' => $email,
			'date_time' => $date_time,
			'payment' => $payment,
			'amount' => (int)round($total_price * 100), // Convert to paise (int)
			'user_id' => $login_id,
			'total_price' => $total_price
		);
		
		// Redirect to Razorpay payment page
		header('location:razorpay_payment.php');
		exit();
	}
}

// Handle Razorpay payment success callback
if(isset($_GET['payment_id']) && isset($_GET['payment_status'])) {
	$payment_id = $_GET['payment_id'];
	$payment_status = $_GET['payment_status'];
	$razorpay_order_id = $_GET['razorpay_order_id'] ?? '';
	$razorpay_signature = $_GET['razorpay_signature'] ?? '';
	$failure_code = $_GET['code'] ?? '';
	$failure_desc = $_GET['reason'] ?? '';
	
	if($payment_status == 'success') {
		// Process successful payment
		processSuccessfulOrder($conn, $login_id, $payment_id, $razorpay_order_id);
	} else {
		$error_details = '';
		if (!empty($failure_code) || !empty($failure_desc)) {
			$error_details = " (" . htmlspecialchars($failure_code) . ": " . htmlspecialchars($failure_desc) . ")";
		}
		$error_message = "Payment failed. Please try again." . $error_details;
		// Clear failed order details from session
		unset($_SESSION['order_details']);
		unset($_SESSION['razorpay_order_id']);
	}
}

function processSuccessfulOrder($conn, $user_id, $payment_id, $razorpay_order_id) {
	global $razorpay_key_id, $razorpay_key_secret;
	
	// Check if order details exist in session
	if(!isset($_SESSION['order_details'])) {
		$error_message = "Order details not found. Please try again.";
		return;
	}
	
	// Validate payment ID
	if(empty($payment_id)) {
		$error_message = "Invalid payment ID received.";
		return;
	}
	
	// Start transaction
	mysqli_begin_transaction($conn);
	
	try {
		// Generate unique order ID
		$order_id = 'ORD' . date('Ymd') . rand(1000, 9999);
		
		// Get cart items
		$cart_query = "select * from `cart` where `user_id`='$user_id'";
		$cart_result = mysqli_query($conn, $cart_query);
		
		if(!$cart_result) {
			throw new Exception("Error fetching cart items: " . mysqli_error($conn));
		}
		
		// Check if cart has items
		if(mysqli_num_rows($cart_result) == 0) {
			throw new Exception("Cart is empty. Cannot process order.");
		}
		
		// Get order details from session
		$order_details = $_SESSION['order_details'];
		
		// Insert each cart item as separate order
		while($cart_item = mysqli_fetch_assoc($cart_result)) {
			$product_id = $cart_item['product_id'];
			$price = $cart_item['price'];
			$num_product = $cart_item['num_product'];
			$size = $cart_item['size'];
			$color = $cart_item['color'];
			$image = $cart_item['image'];
			$product_name = $cart_item['name'];
			
			// Insert into orders table
			$order_insert = "INSERT INTO `order` (
				`order_id`, `product_id`, `user_id`, `name`, `price`, `num_product`, 
				`size`, `color`, `image`, `address`, `city`, `pincode`, 
				`cust_name`, `mobile`, `email`, `payment`, `date_time`, `status`, `razorpay_payment_id`, `razorpay_order_id`
			) VALUES (
				'$order_id', '$product_id', '$user_id', '$product_name', '$price', '$num_product',
				'$size', '$color', '$image', '{$order_details['address']}', '{$order_details['city']}', '{$order_details['pincode']}',
				'{$order_details['name']}', '{$order_details['mobile']}', '{$order_details['email']}', '{$order_details['payment']}', '{$order_details['date_time']}', 'Pending', '$payment_id', '$razorpay_order_id'
			)";
			
			if(!mysqli_query($conn, $order_insert)) {
				throw new Exception("Error inserting order: " . mysqli_error($conn));
			}
		}
		
		// Clear cart after successful order
		$delete_cart = "DELETE FROM `cart` WHERE `user_id`='$user_id'";
		if(!mysqli_query($conn, $delete_cart)) {
			throw new Exception("Error clearing cart: " . mysqli_error($conn));
		}
		
		// Debug: Check if cart is cleared
		$check_cart = "SELECT COUNT(*) as cart_count FROM `cart` WHERE `user_id`='$user_id'";
		$cart_result = mysqli_query($conn, $check_cart);
		$cart_count = mysqli_fetch_assoc($cart_result)['cart_count'];
		error_log("Cart items after clearing: $cart_count");
		
		// Commit transaction
		mysqli_commit($conn);
		
		// Clear session order details
		unset($_SESSION['order_details']);
		unset($_SESSION['razorpay_order_id']);
		
		// Alert and redirect to home page
		echo "<script>alert('Payment Successful! Order placed successfully. Cart has been cleared. Order ID: $order_id'); window.location.href='index.php';</script>";
		exit();
		
	} catch (Exception $e) {
		// Rollback transaction on error
		mysqli_rollback($conn);
		$error_message = "Order failed: " . $e->getMessage();
		
		// Log the error for debugging
		error_log("Order processing failed for user $user_id: " . $e->getMessage());
	}
}

function verifyPayment($payment_id, $key_id, $key_secret) {
	// kept for compatibility
	return true;
}

function verifyPaymentSignature($order_id, $payment_id, $signature, $secret) {
	if (empty($order_id) || empty($payment_id) || empty($signature) || empty($secret)) {
		return false;
	}
	$generated = hash_hmac('sha256', $order_id . '|' . $payment_id, $secret);
	return hash_equals($generated, $signature);
}
?>

<!-- breadcrumb -->
<html lang="en">
<head>
	<title>Home</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->	
	<link rel="icon" type="image/png" href="images/icons/favicon.png"/>
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="fonts/iconic/css/material-design-iconic-font.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="fonts/linearicons-v1.0.0/icon-font.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/animate/animate.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/animsition/css/animsition.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/select2/select2.min.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="vendor/daterangepicker/daterangepicker.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/slick/slick.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/MagnificPopup/magnific-popup.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/perfect-scrollbar/perfect-scrollbar.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="css/util.css">
	<link rel="stylesheet" type="text/css" href="css/main_css.css">
<!--===============================================================================================-->
	<style>
		.amount-card { padding: 40px 50px !important; }
		.amount-card h4 { margin-bottom: 20px !important; }
		.amount-card .flex-w.flex-t { align-items: center; }
		.amount-card .size-208 { width: 50%; }
		.amount-card .size-209 { width: 50%; text-align: right; }
		.amount-card .bor12 { border: 1px solid #e6e6e6; border-radius: 10px; padding: 14px 16px; }
		.amount-card .total-row { border-top: 1px dashed #e6e6e6; padding-top: 16px; margin-top: 8px; }
		.amount-card .total-row .mtext-110 { font-size: 22px; font-weight: 700; }
		@media (max-width: 992px) { .amount-card { padding: 30px 35px !important; } }
		@media (max-width: 576px) { .amount-card { padding: 18px 16px !important; } }
	</style>

</head>
<body class="animsition">

	<header class="header-v4">
		<!-- Header desktop -->
		<div class="container-menu-desktop">
			<!-- Topbar -->
			<div class="top-bar">
				<div class="content-topbar flex-sb-m h-full container">
					<div class="left-top-bar">
						Free shipping for standard order over $100
					</div>

					<div class="right-top-bar flex-w h-full">
						<a href="#" class="flex-c-m trans-04 p-lr-25">
							Help & FAQs
						</a>

						<a href="#" class="flex-c-m trans-04 p-lr-25">
							EN
						</a>

						<a href="#" class="flex-c-m trans-04 p-lr-25">
							INR
						</a>

						<?php if (isset($_SESSION['login']))
						{ ?>
							<div class="profile-main-menu">
							<a href="#" class="flex-c-m trans-04 p-lr-25" style="border-left: 0">My Account</a>
								<ul class="profile-sub-menu">
									<li><h5><?php echo $row_login['name']; ?></h5></li>
									<li><a href="my_profile.php">My Profile</a></li>
									<li><a href="order-list.php">Order List</a></li>
									<li><a href="shoping-cart.php">My Cart</a></li>
									<li><a href="logouts.php">Logout</a></li>
								</ul>
							</div>
						<?php }
						else{ ?>
							<a href="login_home.php" class="flex-c-m trans-04 p-lr-25">
								Login / Sign-in
							</a>
						<?php } ?>

						<?php if (isset($_SESSION['login']))
						{ ?>
							<a style="color: #b2b2b2;" class="flex-c-m trans-04 p-lr-25">
								Hello...<?php echo $row_login['name']; ?>!
							</a>
						<?php } ?>
					</div>
				</div>
			</div>

			<div class="wrap-menu-desktop">
				<nav class="limiter-menu-desktop container">
					
					<!-- Logo desktop -->		
					<a href="index.php" class="logo">
						<img src="images/icons/logo-01.png" alt="IMG-LOGO">
					</a>

					<!-- Menu desktop -->
					<!-- <div class="menu-desktop">
						<ul class="main-menu">
							<li class="active-menu">
								<a href="index.php">Home</a>
								<ul class="sub-menu">
									<li><a href="index.php">Homepage 1</a></li>
									<li><a href="index.php">Homepage 2</a></li>
									<li><a href="index.php">Homepage 3</a></li>
								</ul>
							</li>

							<li>
								<a href="product.php">Shop</a>
							</li>

							<li class="label1" data-label1="hot">
								<a href="shoping-cart.php">Shopping Cart</a>
							</li>

							<li>
								<a href="blog.php">Blog</a>
							</li>

							<li>
								<a href="about.php">About</a>
							</li>

							<li>
								<a href="contact.php">Contact</a>
							</li>
						</ul>
					</div> -->	

					<!-- Icon header -->
					<!-- <div class="wrap-icon-header flex-w flex-r-m" id="cart_data_count">
						<div class="icon-header-item cl2 hov-cl1 trans-04 p-l-22 p-r-11 js-show-modal-search">
							<i class="zmdi zmdi-search"></i>
						</div>

						<div class="icon-header-item cl2 hov-cl1 trans-04 p-l-22 p-r-11 icon-header-noti js-show-cart" data-notify="
						<?php if (isset($_SESSION['login']))
						{ echo $data_count; }
						else{
							echo "0";
						} ?>">
							<i class="zmdi zmdi-shopping-cart"></i>
						</div>

						<a href="#" class="dis-block icon-header-item cl2 hov-cl1 trans-04 p-l-22 p-r-11 icon-header-noti" data-notify="0">
							<i class="zmdi zmdi-favorite-outline"></i>
						</a>
					</div> -->
				</nav>
			</div>
		</div>

		<!-- Header Mobile -->
		<div class="wrap-header-mobile">
			<!-- Logo moblie -->		
			<div class="logo-mobile">
				<a href="index.php"><img src="images/icons/logo-01.png" alt="IMG-LOGO"></a>
			</div>

			<!-- Icon header -->
			<!-- <div class="wrap-icon-header flex-w flex-r-m m-r-15">
				<div class="icon-header-item cl2 hov-cl1 trans-04 p-r-11 js-show-modal-search">
					<i class="zmdi zmdi-search"></i>
				</div>

				<div class="icon-header-item cl2 hov-cl1 trans-04 p-r-11 p-l-10 icon-header-noti js-show-cart" data-notify="8">
					<i class="zmdi zmdi-shopping-cart"></i>
				</div>

				<a href="#" class="dis-block icon-header-item cl2 hov-cl1 trans-04 p-r-11 p-l-10 icon-header-noti" data-notify="0">
					<i class="zmdi zmdi-favorite-outline"></i>
				</a>
			</div> -->

			<!-- Button show menu -->
			<div class="btn-show-menu-mobile hamburger hamburger--squeeze">
				<span class="hamburger-box">
					<span class="hamburger-inner"></span>
				</span>
			</div>
		</div>


		<!-- Menu Mobile -->
		<div class="menu-mobile">
			<ul class="topbar-mobile">
				<li>
					<div class="left-top-bar">
						Free shipping for standard order over $100
					</div>
				</li>

				<li>
					<div class="right-top-bar flex-w">
						<a href="#" class="flex-c-m p-lr-13 trans-04">
							Help & FAQs
						</a>

						<a href="#" class="flex-c-m p-lr-13 trans-04">
							INR
						</a>

						<?php if (isset($_SESSION['login']))
						{ ?>
							<div class="profile-main-menu-m">
							<a href="#" class="flex-c-m trans-04 p-lr-25" style="border-left: 0">My Account</a>
								<ul class="profile-sub-menu-m">
									<li><h5><?php echo $row_login['name']; ?></h5></li>
									<li><a href="index.php" class="right-top-bar-2">My Profile</a></li>
									<li><a href="index.php">Order List</a></li>
									<li><a href="shoping-cart.php">My Cart</a></li>
									<li><a href="logout.php">Logout</a></li>
								</ul>
							</div>
						<?php }
						else{ ?>
							<a href="login_home.php" class="flex-c-m trans-04 p-lr-25">
								Login / Sign-in
							</a>
						<?php } ?>

						<?php if (isset($_SESSION['login']))
						{ ?>
							<a style="color: #b2b2b2;" class="flex-c-m trans-04 p-lr-15">
								Hello... <?php echo $row_login['name']; ?>!
							</a>
						<?php } ?>
					</div>
				</li>
			</ul>

			<!-- <ul class="main-menu-m">
				<li>
					<a href="index.php">Home</a>
					<ul class="sub-menu-m">
						<li><a href="index.php">Homepage 1</a></li>
						<li><a href="home-02.php">Homepage 2</a></li>
						<li><a href="home-03.php">Homepage 3</a></li>
					</ul>
					<span class="arrow-main-menu-m">
						<i class="fa fa-angle-right" aria-hidden="true"></i>
					</span>
				</li>

				<li>
					<a href="product.php">Shop</a>
				</li>

				<li>
					<a href="shoping-cart.php" class="label1 rs1" data-label1="hot">Shopping Cart</a>
				</li>

				<li>
					<a href="blog.php">Blog</a>
				</li>

				<li>
					<a href="about.php">About</a>
				</li>

				<li>
					<a href="contact.php">Contact</a>
				</li>
			</ul> -->
		</div>

		<!-- Modal Search -->
		<!-- <div class="modal-search-header flex-c-m trans-04 js-hide-modal-search">
			<div class="container-search-header">
				<button class="flex-c-m btn-hide-modal-search trans-04 js-hide-modal-search">
					<img src="images/icons/icon-close2.png" alt="CLOSE">
				</button>

				<form class="wrap-search-header flex-w p-l-15">
					<button class="flex-c-m trans-04">
						<i class="zmdi zmdi-search"></i>
					</button>
					<input class="plh3" type="text" name="search" placeholder="Search...">
				</form>
			</div>
		</div> -->
	</header>

	<div class="container">
		<div class="bread-crumb flex-w p-l-25 p-r-15 p-t-15 p-lr-0-lg">
			<a href="index.php" class="stext-109 cl8 hov-cl1 trans-04">
				Home
				<i class="fa fa-angle-right m-l-9 m-r-10" aria-hidden="true"></i>
			</a>

			<a href="shoping-cart.php" class="stext-109 cl8 hov-cl1 trans-04">
				Shoping Cart
				<i class="fa fa-angle-right m-l-9 m-r-10" aria-hidden="true"></i>
			</a>

			<span class="stext-109 cl4">
				Order-now
			</span>
		</div>
	</div>

	<!-- Shoping Cart -->
<div id="new_number_of_product">
	<form class="bg0 p-t-45 p-b-85" method="post">
		<div class="container">
			<div class="row">
				<div class="col-lg-10 col-xl-7 m-lr-auto m-b-50">
					<div style="margin-bottom: 10px;" class="m-l-25 m-r--38 m-lr-0-xl mb-10">
						<div class="wrap-table-shopping-cart">
							<table class="table-shopping-cart" align="center">
								<tr class="table_head">
									<th class="column-1">Product</th>
									<th class="column-2"></th>
									<th class="column-3">Price</th>
									<th class="column-4">Quantity</th>
									<th class="column-5">Total</th>
								</tr>

						<?php if(isset($_SESSION['login']))
						{
						while($row = mysqli_fetch_assoc($data)) { ?>
								<tr class="table_row">
									<td class="column-1" align="center">
										<div class="how-itemcart1">
											<img src="admin/image/<?php echo $row['image']; ?>">
										</div>										
									</td>
									<td class="column-2">
										<div class="p-b-10"><?php echo $row['name']; ?></div>
										<ul>
											<li><b>Size : </b><?php echo $row['size']; ?></li>
											<li><b>Color : </b><?php echo $row['color']; ?></li>
										</ul>	
									</td>
									<td class="column-3">Rs.<?php echo $row['price']; ?></td>
									<td class="column-4" align="center">
										<span class="num_pro"><?php echo $row['num_product']; ?></span>
									</td>
									<td class="column-5">
										<?php 

											$total_pro = $row['num_product'];
											$price = $row['price'];

											echo 'Rs.'.$total_pro*$price;
										 ?>
									</td>
									<td>
										
									</td>
								</tr>
						<?php } } ?>

						</table>
						</div>

						<div class="flex-w flex-sb-m bor15 p-t-18 p-b-15 mb-10 p-lr-40 p-lr-15-sm">
							<div class=" m-r-20 m-tb-5">
								<div class="stext-110 cl2 m-b-12 m-l-3">Address:</div>
								<div class="bor8 bg0 m-b-12">
									<textarea rows="5" class="stext-111 cl8 plh3 p-t-10 p-lr-15" cols="60" type="text" maxlength="1000" name="address" placeholder="Insert delivery address" required></textarea>
								</div>

								<div class="bor8 bg0 m-b-12">
									<input class="stext-111 cl8 plh3 size-111 p-lr-15" type="text" name="city" maxlength="20" placeholder="City" required>
								</div>

								<div class="bor8 bg0 m-b-12">
									<input class="stext-111 cl8 plh3 size-111 p-lr-15" type="text" name="pincode" maxlength="6" placeholder="Pincode" required>
								</div>
							
								<div class="stext-110 cl2 m-t-55 m-b-12 m-l-3">Confirm Name:</div>
								<div class="bor8 bg0 m-b-12">
									<input class="stext-111 cl8 plh3 size-111 p-lr-15" type="text" name="name" maxlength="40" placeholder="Name" value="<?php echo @$row_r['name']; ?>" required>
								</div>

								<div class="stext-110 cl2 m-t-55 m-b-12 m-l-3">Confirm Mobile Number:</div>
								<div class="bor8 bg0 m-b-12">
									<input class="stext-111 cl8 plh3 size-111 p-lr-15" type="text" name="mobile" minlength="10" maxlength="10" placeholder="Mobile Number" value="<?php echo @$row_r['mobile_number']; ?>" required>
								</div>

								<div class="stext-110 cl2 m-t-55 m-b-12 m-l-3">Confirm Email:</div>
								<div class="bor8 bg0 m-b-12">
									<input class="stext-111 cl8 plh3 size-111 p-lr-15" type="text" name="email" maxlength="35" placeholder="Email" value="<?php echo @$row_r['email']; ?>" required>
								</div>

								<div class="stext-110 cl2 m-t-55 m-b-12 m-l-3">Payment Method:</div>
								<div class="m-b-22">
									<select name="payment" class="stext-111 cl8 plh3 size-111 p-lr-15" style="border: 1px solid #e6e6e6;" required>
										<option value="">-Select payment option-</option>
										<option value="Credit / Debit Card">Credit / Debit Card</option>
										<option value="UPI">UPI</option>
										<option value="Cash on Delivery">Cash on Delivery</option>
										<option value="EMI">EMI</option>
									</select>
									<!-- <input class="stext-111 cl8 plh3 size-111 p-lr-15" type="text" name="email" maxlength="35" placeholder="Email" value="<?php echo @$row_r['email']; ?>" required> -->
								</div>

								<input type="hidden" name="date_time" value="<?php date_default_timezone_set('Asia/Kolkata'); echo date('Y-m-d H:i:s'); ?>" required>
							
							</div>
								<button class="flex-c-m stext-101 cl0 size-116 bg3 bor14 hov-btn3 p-lr-15 trans-04 pointer" name="buy">
								Place Order
								</button>
						</div>
					</div>

					<div style="    width: 47vw;
    margin-left: 11px;" class="col-sm-12 col-lg-7 col-xl-5	 m-b-50 mt-10">
						<div style="width: 47vw;" class="bor10 p-lr-40 p-t-30 p-b-40  m-lr-0-xl p-lr-15-sm amount-card mt-10">
							<h4 class="mtext-109 cl2 p-b-50">
								Amount to be Paid
							</h4>

							<div class="flex-w flex-t bor12 p-b-13">
								<div class="size-208">
									<span class="stext-110 cl2 ">
										Subtotal:
									</span>
								</div>

								<div class="size-209">
									<span class="mtext-110 cl2">
										<?php if(isset($_SESSION['login'])) { ?>
											Rs.<?php echo $total_price; ?>
										<?php }
										else {
											echo "Rs.0";
										} ?>
									</span>
								</div>
							</div>

							<div class="flex-w flex-t bor12 p-t-15 p-b-30">
								<div class="size-208 w-full-ssm">
									<span class="stext-110 cl2">
										Shipping:
									</span>
								</div>

								<div class="size-209 p-r-18 p-r-0-sm w-full-ssm">
									<p class="stext-111 cl6 p-t-2">
										Rs. 0
									</p>
								</div>
							</div>

							<div class="flex-w flex-t p-t-27 p-b-33 total-row">
								<div class="size-208">
									<span class="mtext-101 cl2">
										Total:
									</span>
								</div>

								<div class="size-209 p-t-1">
									<span class="mtext-110 cl2">
										<?php if(isset($_SESSION['login'])) { ?>
											Rs.<?php echo $total_price; ?>
										<?php }
										else {
											echo "Rs.0";
										} ?>
									</span>
								</div>
							</div>

							
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>


	<?php include_once 'footer.php'; ?>

	<?php include_once 'scripts.php'; ?>
