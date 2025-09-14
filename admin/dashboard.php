<?php 
include_once 'header.php'; 

// Sliders count
$sql_slider = "SELECT COUNT(*) as total FROM `slider`";
$data_slider = mysqli_query($conn, $sql_slider);
$slider_count = mysqli_fetch_assoc($data_slider)['total'];

// Products count
$sql_products = "SELECT COUNT(*) as total FROM `product`";
$data_products = mysqli_query($conn, $sql_products);
$products_count = mysqli_fetch_assoc($data_products)['total'];

// Products In-Stock
$sql_products_stock = "SELECT COUNT(*) as total FROM `product` WHERE stock='In Stock'";
$data_products_stock = mysqli_query($conn, $sql_products_stock);
$products_count_stock = mysqli_fetch_assoc($data_products_stock)['total'];

// Products Out-of-Stock
$sql_products_out = "SELECT COUNT(*) as total FROM `product` WHERE stock='Out of Stock'";
$data_products_out = mysqli_query($conn, $sql_products_out);
$products_count_out = mysqli_fetch_assoc($data_products_out)['total'];

// Blogs count
$sql_blog = "SELECT COUNT(*) as total FROM `blog`";
$data_blog = mysqli_query($conn, $sql_blog);
$blog_count = mysqli_fetch_assoc($data_blog)['total'];

// Contact count
$sql_contact = "SELECT COUNT(*) as total FROM `contact_us`";
$data_contact = mysqli_query($conn, $sql_contact);
$contact_count = mysqli_fetch_assoc($data_contact)['total'];

// Orders Pending
$sql_order_pending = "SELECT COUNT(*) as total FROM `order` WHERE LOWER(status)='pending'";
$data_order_pending = mysqli_query($conn, $sql_order_pending);
$order_count = mysqli_fetch_assoc($data_order_pending)['total'];

// Orders Delivered
$sql_order_deli = "SELECT COUNT(*) as total FROM `order` WHERE LOWER(status)='delivered'";
$data_order_deli = mysqli_query($conn, $sql_order_deli);
$order_count_deli = mysqli_fetch_assoc($data_order_deli)['total'];

// Orders Cancelled
$sql_order_can = "SELECT COUNT(*) as total FROM `order` WHERE LOWER(status)='cancelled-by-supplier' OR LOWER(status)='cancelled-by-client'";
$data_order_can = mysqli_query($conn, $sql_order_can);
$order_count_can = mysqli_fetch_assoc($data_order_can)['total'];

// Total Orders
$sql_order_total = "SELECT COUNT(*) as total FROM `order`";
$data_order_total = mysqli_query($conn, $sql_order_total);
$order_count_total = mysqli_fetch_assoc($data_order_total)['total'];
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Dashboard</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
          </ol>
          <div class="mt-2">
            <a href="dashboard.php" class="btn btn-primary btn-sm">Refresh</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- /.content-header -->

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        
        <!-- Products Total -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-info">
            <div class="inner">
              <h3><?php echo $products_count; ?></h3>
              <p>Total Products Updated</p>
            </div>
            <div class="icon"><i class="ion ion-stats-bars"></i></div>
            <a href="view-product.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Products In Stock -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-info">
            <div class="inner">
              <h3><?php echo $products_count_stock; ?></h3>
              <p>Products In-Stock</p>
            </div>
            <div class="icon"><i class="ion ion-person-add"></i></div>
            <a href="view-product1.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Products Out of Stock -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-info">
            <div class="inner">
              <h3><?php echo $products_count_out; ?></h3>
              <p>Products Out of Stock</p>
            </div>
            <div class="icon"><i class="ion ion-pie-graph"></i></div>
            <a href="view-product2.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- People Contacted -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-dark">
            <div class="inner">
              <h3><?php echo $contact_count; ?></h3>
              <p>People Contacted Us</p>
            </div>
            <div class="icon"><i class="ion ion-pie-graph"></i></div>
            <a href="contacted-us.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Orders Pending -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-danger">
            <div class="inner">
              <h3><?php echo $order_count; ?></h3>
              <p>New Order Received</p>
            </div>
            <div class="icon"><i class="ion ion-pie-graph"></i></div>
            <a href="view-received-order.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Orders Delivered -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-success">
            <div class="inner">
              <h3><?php echo $order_count_deli; ?></h3>
              <p>Delivered</p>
            </div>
            <div class="icon"><i class="ion ion-pie-graph"></i></div>
            <a href="view-all-orders.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Orders Cancelled -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-success">
            <div class="inner">
              <h3><?php echo $order_count_can; ?></h3>
              <p>Cancelled</p>
            </div>
            <div class="icon"><i class="ion ion-pie-graph"></i></div>
            <a href="view-all-orders.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Sliders -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning">
            <div class="inner">
              <h3><?php echo $slider_count; ?></h3>
              <p>Sliders</p>
            </div>
            <div class="icon"><i class="ion ion-bag"></i></div>
            <a href="view-slider.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Blogs -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning">
            <div class="inner">
              <h3><?php echo $blog_count; ?></h3>
              <p>Blogs</p>
            </div>
            <div class="icon"><i class="ion ion-pie-graph"></i></div>
            <a href="view-blog.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Total Orders -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-info">
            <div class="inner">
              <h3><?php echo $order_count_total; ?></h3>
              <p>Total Orders</p>
            </div>
            <div class="icon"><i class="ion ion-bag"></i></div>
            <a href="view-all-orders.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

      </div>
    </div>
  </section>
</div>

<?php include_once 'footer.php'; ?>
<aside class="control-sidebar control-sidebar-dark"></aside>
</div>
<?php include_once 'scripts.php'; ?>
</body>
</html>
