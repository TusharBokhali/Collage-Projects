<?php
include_once 'site_connection.php';
include_once 'razorpay_config.php';

// Check if user is logged in
if(!isset($_SESSION['login'])) {
    header('location:login_home.php');
    exit();
}

// Check if order details exist
if(!isset($_SESSION['order_details'])) {
    header('location:order-now.php');
    exit();
}

$order_details = $_SESSION['order_details'];
// Ensure amount is an integer in paise
$amount = isset($order_details['amount']) ? (int)$order_details['amount'] : 0; // Amount in paise
$user_email = $order_details['email'];
$user_name = $order_details['name'];
$user_mobile = $order_details['mobile'];

// Razorpay credentials based on environment
$credentials = getRazorpayCredentials();
$razorpay_key_id = isset($credentials['key_id']) ? $credentials['key_id'] : '';
$razorpay_key_secret = isset($credentials['key_secret']) ? $credentials['key_secret'] : '';

// Validate credentials based on environment
$environment = getRazorpayEnvironment();
$config_error = '';
if (empty($razorpay_key_id) || empty($razorpay_key_secret)) {
	$config_error = 'Razorpay credentials are missing. Please configure key_id and key_secret.';
}
if ($environment === 'test' && strpos($razorpay_key_id, 'rzp_test_') !== 0) {
	$config_error = 'Test environment selected but key_id is not a test key.';
}
if ($environment === 'production' && strpos($razorpay_key_id, 'rzp_live_') !== 0) {
	$config_error = 'Production environment selected but key_id is not a live key.';
}
if ($amount <= 0) {
	$config_error = 'Invalid payment amount. Amount must be a positive integer in paise.';
}
// Enforce minimum amount of ₹1 (100 paise)
if ($amount > 0 && $amount < 100) {
	$config_error = 'Amount too low. Minimum chargeable amount is ₹1 (100 paise).';
}

// Create an order on Razorpay (required for Checkout)
$razorpay_order_id = '';
$order_create_error = '';
$order_create_http_code = 0;
$order_create_raw_response = '';
$order_create_payload_json = '';
// Only attempt order creation if config is valid
if (empty($config_error)) {
try {
	$create_payload = array(
		'amount' => (int)$amount,
		'currency' => 'INR',
		'receipt' => 'rcpt_' . time() . '_' . uniqid(),
		'payment_capture' => 1,
		'notes' => array(
			'customer_name' => $user_name,
			'customer_email' => $user_email,
			'customer_mobile' => $user_mobile,
			'order_type' => 'ecommerce'
		)
	);

	$ch = curl_init('https://api.razorpay.com/v1/orders');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($create_payload));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_USERPWD, $razorpay_key_id . ':' . $razorpay_key_secret);
    // Reasonable timeouts
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

	$response = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curl_err = curl_error($ch);
	curl_close($ch);

	if ($curl_err) {
		throw new Exception('cURL Error: ' . $curl_err);
	}

	$resp_data = @json_decode($response, true);
	if ($http_code >= 200 && $http_code < 300 && isset($resp_data['id'])) {
		$razorpay_order_id = $resp_data['id'];
	} else {
		$err_msg = isset($resp_data['error']['description']) ? $resp_data['error']['description'] : 'Unexpected error creating order';
		// Log full API response for debugging
		$log_entry = date('Y-m-d H:i:s') . "\nHTTP: " . $http_code . "\nPayload: " . json_encode($create_payload) . "\nResponse: " . $response . "\n";
		file_put_contents('razorpay_order_create_errors.log', $log_entry, FILE_APPEND | LOCK_EX);
		// Capture details for inline debug
		$order_create_http_code = (int)$http_code;
		$order_create_raw_response = $response;
		$order_create_payload_json = json_encode($create_payload);
		throw new Exception('Razorpay order create failed (' . $http_code . '): ' . $err_msg);
	}
} catch (Exception $ex) {
	$order_create_error = $ex->getMessage();
}
}

// Store Razorpay order ID in session for verification (if created)
if ($razorpay_order_id) {
	$_SESSION['razorpay_order_id'] = $razorpay_order_id;
}

// Debug information (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment - Razorpay</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="images/icons/favicon.png"/>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="css/main_css.css">
    
    <style>
        .payment-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .payment-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .amount-display {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            text-align: center;
            margin: 20px 0;
        }
        .razorpay-button {
            background: #528FF0;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        .razorpay-button:hover {
            background: #3A7BD5;
        }
        .back-button {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
        }
        .back-button:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .test-info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .debug-info {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 12px;
            font-family: monospace;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="payment-container">
        <div class="payment-header">
            <h2><i class="fa fa-credit-card"></i> Complete Your Payment</h2>
            <p>Secure payment powered by Razorpay</p>
        </div>
        
        <!-- Debug Information (Remove in production) -->
        <div class="debug-info">
            <strong>Debug Info:</strong><br>
            Amount: ₹<?php echo number_format($amount/100, 2); ?> (<?php echo $amount; ?> paise)<br>
            Order ID: <?php echo $razorpay_order_id ?: 'NOT_CREATED'; ?><br>
            Key ID: <?php echo substr($razorpay_key_id, 0, 20) . '...'; ?><br>
            Session Status: <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?>
        </div>

        <?php if (!empty($config_error)) { ?>
        <div class="error-message">
            <?php echo htmlspecialchars($config_error); ?>
        </div>
        <?php } ?>

        <?php if (!empty($order_create_error)) { ?>
        <div class="error-message">
        <?php echo htmlspecialchars($order_create_error); ?>
        </div>
        <div class="debug-info">
            <strong>Order Create Failure Debug</strong><br>
            Environment: <?php echo htmlspecialchars($environment); ?><br>
            HTTP Code: <?php echo (int)$order_create_http_code; ?><br>
            Payload: <?php echo htmlspecialchars($order_create_payload_json); ?><br>
            Response: <?php echo htmlspecialchars($order_create_raw_response); ?>
        </div>
        <?php } ?>
        
        <!-- Test Mode Information -->
        <div class="test-info">
            <h6><i class="fa fa-info-circle"></i> Test Mode Active</h6>
            <p><strong>Test Cards:</strong></p>
            <ul style="margin: 5px 0; padding-left: 20px;">
                <li><strong>Success:</strong> 4111 1111 1111 1111</li>
                <li><strong>Failure:</strong> 4000 0000 0000 0002</li>
            </ul>
            <p><strong>Test UPI:</strong> success@razorpay (always succeeds)</p>
        </div>
        
        <div class="payment-details">
            <h5>Order Summary</h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($user_name); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
                    <p><strong>Mobile:</strong> <?php echo htmlspecialchars($user_mobile); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order_details['payment']); ?></p>
                    <p><strong>Order Date:</strong> <?php echo date('d M Y, h:i A', strtotime($order_details['date_time'])); ?></p>
                </div>
            </div>
        </div>
        
        <div class="amount-display">
            Total Amount: ₹<?php echo number_format($amount/100, 2); ?>
        </div>
        
        <button class="razorpay-button" onclick="initiatePayment()" id="pay-button" <?php echo (empty($razorpay_order_id) || !empty($config_error)) ? 'disabled' : ''; ?>>
            <i class="fa fa-lock"></i> Pay Securely with Razorpay
        </button>
        
        <a href="order-now.php" class="back-button">
            <i class="fa fa-arrow-left"></i> Back to Order
        </a>
    </div>
</div>

<!-- Razorpay SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
// Global variables for debugging
let paymentAttempted = false;
let paymentModal = null;

function initiatePayment() {
// alert()
    console.log('Payment initiation started...');
    
    // Prevent double clicks
    if (paymentAttempted) {
        console.log('Payment already attempted, preventing double click');
        return;
    }
    
    paymentAttempted = true;
    
    // Disable button to prevent double clicks
    const payButton = document.getElementById('pay-button');
    payButton.disabled = true;
    payButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
    
    try {

        // alert("=========")
        console.log('Creating Razorpay options...');
        
        var options = {
            "key": "<?php echo $razorpay_key_id; ?>",
            "amount": <?php echo (int)$amount; ?>,
            "currency": "INR",
            "name": "Your Store Name",
            "description": "Order Payment - <?php echo htmlspecialchars($user_name); ?>",
            "image": "images/icons/logo-01.png",
            "order_id": "<?php echo $razorpay_order_id; ?>",
            "handler": function (response) {
                console.log('Payment successful response:', response);
                handlePaymentSuccess(response);
            },
            "prefill": {
                "name": "<?php echo htmlspecialchars($user_name ?? ''); ?>",
                "email": "<?php echo htmlspecialchars($user_email ?? ''); ?>",
                "contact": "<?php echo htmlspecialchars($user_mobile ?? ''); ?>"
            },
            "notes": {
                "address": "<?php echo htmlspecialchars($order_details['address']); ?>",
                "city": "<?php echo htmlspecialchars($order_details['city']); ?>",
                "pincode": "<?php echo htmlspecialchars($order_details['pincode']); ?>",
                "order_type": "ecommerce"
            },
            "theme": {
                "color": "#528FF0"
            },
            "modal": {
                "ondismiss": function() {
                    console.log("Payment modal dismissed");
                    resetPaymentButton();
                },
                "onload": function() {
                    console.log("Payment modal loaded");
                }
            },
            "config": {
                "display": {
                    "blocks": {
                        "banks": {
                            "name": "Pay using UPI",
                            "instruments": [
                                {
                                    "method": "upi"
                                }
                            ]
                        }
                    },
                    "sequence": ["block.banks"],
                    "prefill": {
                        "method": "upi"
                    }
                }
            }
        };
        
        console.log('Razorpay options created:', options);
        console.log('Amount being sent:', options.amount);
        console.log('Order ID being sent:', options.order_id);
        
        // Create and open Razorpay instance
        console.log('Creating Razorpay instance...');
        // Validate order_id before opening checkout
        if (!options.order_id) {
            console.error('Missing order_id. Aborting checkout.');
            alert('Payment cannot be initiated at the moment. Please refresh and try again.');
            resetPaymentButton();
            return;
        }

        paymentModal = new Razorpay(options);

        // Attach failure handler
        if (paymentModal && typeof paymentModal.on === 'function') {
            paymentModal.on('payment.failed', function (response) {
                console.log('Payment failed event:', response);
                handlePaymentFailure(response);
            });
        }
        
        console.log('Opening Razorpay modal...');
        paymentModal.open();
        
        console.log('Payment modal opened successfully');
        
    } catch (error) {
        console.error('Error in payment initiation:', error);
        alert('Payment initialization failed: ' + error.message);
        resetPaymentButton();
    }
}

function resetPaymentButton() {
    const payButton = document.getElementById('pay-button');
    payButton.disabled = false;
    payButton.innerHTML = '<i class="fa fa-lock"></i> Pay Securely with Razorpay';
    paymentAttempted = false;
}

function handlePaymentSuccess(response) {
    console.log('Payment success handler called with:', response);
    
    try {
        // Validate response
        if (!response.razorpay_payment_id) {
            throw new Error('Payment ID not received');
        }
        
        if (!response.razorpay_order_id) {
            throw new Error('Order ID not received');
        }
        
        // Show success message
        alert('Payment Successful!\nPayment ID: ' + response.razorpay_payment_id + '\nOrder ID: ' + response.razorpay_order_id);
        
        // Redirect to order-now.php with payment success parameters
        var sig = response.razorpay_signature ? ('&razorpay_signature=' + encodeURIComponent(response.razorpay_signature)) : '';
        window.location.href = 'order-now.php?payment_id=' + encodeURIComponent(response.razorpay_payment_id) + '&payment_status=success&razorpay_order_id=' + encodeURIComponent(response.razorpay_order_id) + sig;
        
    } catch (error) {
        console.error('Error in payment success handler:', error);
        alert('Payment verification failed: ' + error.message);
        resetPaymentButton();
    }
}

function handlePaymentFailure(response) {
    console.log('Payment failure handler called with:', response);
    var err = (response && response.error) ? response.error : response || {};
    var code = err.code || 'UNKNOWN_ERROR';
    var description = err.description || err.reason || 'Payment Failed! Please try again.';
    var source = err.source || '';
    var step = err.step || '';
    var reason = err.reason || '';
    alert('Payment Failed: ' + description + (code ? ('\nCode: ' + code) : ''));
    var redirectUrl = 'order-now.php?payment_status=failed';
    if (err.metadata && err.metadata.payment_id) {
        redirectUrl += '&payment_id=' + encodeURIComponent(err.metadata.payment_id);
    }
    if (err.metadata && err.metadata.order_id) {
        redirectUrl += '&razorpay_order_id=' + encodeURIComponent(err.metadata.order_id);
    }
    if (code) {
        redirectUrl += '&code=' + encodeURIComponent(code);
    }
    if (reason || description) {
        redirectUrl += '&reason=' + encodeURIComponent(reason || description);
    }
    window.location.href = redirectUrl;
}

// Listen for payment events
document.addEventListener('DOMContentLoaded', function() {
    console.log('Payment page loaded successfully');
    console.log('Order details:', <?php echo json_encode($order_details); ?>);
    console.log('Razorpay order ID:', '<?php echo $razorpay_order_id; ?>');
    console.log('Amount in paise:', <?php echo $amount; ?>);
    console.log('Razorpay key ID:', '<?php echo $razorpay_key_id; ?>');
    
    // Check if Razorpay SDK is loaded
    if (typeof Razorpay === 'undefined') {
        console.error('Razorpay SDK not loaded!');
        alert('Payment system not available. Please refresh the page.');
    } else {
        console.log('Razorpay SDK loaded successfully');
    }
});

// Handle page unload to prevent accidental navigation
window.addEventListener('beforeunload', function(e) {
    if (document.getElementById('pay-button').disabled) {
        e.preventDefault();
        e.returnValue = 'Payment is in progress. Are you sure you want to leave?';
    }
});

// Global error handler
window.addEventListener('error', function(e) {
    console.error('Global error caught:', e.error);
    alert('An error occurred: ' + e.error.message);
    resetPaymentButton();
});

// Handle unhandled promise rejections
window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
    alert('Payment system error: ' + e.reason);
    resetPaymentButton();
});
</script>

</body>
</html>
