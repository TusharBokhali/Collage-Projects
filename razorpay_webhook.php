<?php
/**
 * Razorpay Webhook Handler
 * This file handles payment notifications from Razorpay
 * Use this for production mode
 */

include_once 'site_connection.php';
include_once 'razorpay_config.php';

// Start session for accessing order details
session_start();

// Get webhook secret from Razorpay dashboard
$webhook_secret = 'YOUR_WEBHOOK_SECRET'; // Replace with your webhook secret

// Get the webhook payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

// Verify webhook signature
if (verifyWebhookSignature($payload, $signature, $webhook_secret)) {
    $data = json_decode($payload, true);
    
    if ($data) {
        $event = $data['event'];
        $payment_id = $data['payload']['payment']['entity']['id'] ?? '';
        $order_id = $data['payload']['payment']['entity']['order_id'] ?? '';
        $status = $data['payload']['payment']['entity']['status'] ?? '';
        $amount = $data['payload']['payment']['entity']['amount'] ?? 0;
        
        // Log webhook data
        logWebhookData($event, $payment_id, $order_id, $status, $amount);
        
        // Handle different webhook events
        switch ($event) {
            case 'payment.captured':
                handlePaymentCaptured($payment_id, $order_id, $status, $amount);
                break;
                
            case 'payment.failed':
                    handlePaymentFailed($payment_id, $order_id, $status);
                    break;
                
            case 'order.paid':
                handleOrderPaid($payment_id, $order_id, $status, $amount);
                break;
                
            default:
                // Log unknown event
                logWebhookData('unknown_event', $payment_id, $order_id, $status, $amount);
                break;
        }
        
        // Send success response
        http_response_code(200);
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload']);
    }
} else {
    // Invalid signature
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
}

/**
 * Verify webhook signature
 */
function verifyWebhookSignature($payload, $signature, $secret) {
    $expected_signature = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected_signature, $signature);
}

/**
 * Handle successful payment capture
 */
function handlePaymentCaptured($payment_id, $order_id, $status, $amount) {
    global $conn;
    
    // Check if order details exist in session (for direct payment flow)
    if(isset($_SESSION['order_details'])) {
        $order_details = $_SESSION['order_details'];
        $user_id = $order_details['user_id'];
        
        // Process the order similar to order-now.php
        processSuccessfulOrder($conn, $user_id, $payment_id, $order_id);
        
        // Clear session data
        unset($_SESSION['order_details']);
        unset($_SESSION['razorpay_order_id']);
        
        // Log successful payment
        logPaymentSuccess($payment_id, $order_id, $amount);
        
        return;
    }
    
    // Update existing order status in database (for webhook flow)
    $update_query = "UPDATE `order` SET 
                     `status` = 'Pending', 
                     `razorpay_payment_id` = '$payment_id',
                     `payment_status` = '$status'
                     WHERE `razorpay_order_id` = '$order_id'";
    
    if (mysqli_query($conn, $update_query)) {
        // Send confirmation email to customer
        sendOrderConfirmationEmail($payment_id, $order_id);
        
        // Log successful payment
        logPaymentSuccess($payment_id, $order_id, $amount);
    }
}

/**
 * Handle failed payment
 */
function handlePaymentFailed($payment_id, $order_id, $status) {
    global $conn;
    
    // Update order status
    $update_query = "UPDATE `order` SET 
                     `status` = 'payment_failed', 
                     `payment_status` = '$status'
                     WHERE `razorpay_order_id` = '$order_id'";
    
    mysqli_query($conn, $update_query);
    
    // Send failure notification to customer
    sendPaymentFailureEmail($payment_id, $order_id);
    
    // Log failed payment
    logPaymentFailure($payment_id, $order_id);
}

/**
 * Handle order paid event
 */
function handleOrderPaid($payment_id, $order_id, $status, $amount) {
    // This event is triggered when order is fully paid
    // You can add additional logic here
    logWebhookData('order_paid', $payment_id, $order_id, $status, $amount);
}

/**
 * Send order confirmation email
 */
function sendOrderConfirmationEmail($payment_id, $order_id) {
    // Implement email sending logic here
    // You can use PHPMailer or any other email library
    
    $to = "customer@example.com"; // Get from order details
    $subject = "Order Confirmed - Payment Successful";
    $message = "Your order has been confirmed. Payment ID: $payment_id, Order ID: $order_id";
    
    // mail($to, $subject, $message); // Uncomment when ready to send emails
}

/**
 * Send payment failure email
 */
function sendPaymentFailureEmail($payment_id, $order_id) {
    // Implement email sending logic for failed payments
    
    $to = "customer@example.com"; // Get from order details
    $subject = "Payment Failed - Order Not Confirmed";
    $message = "Your payment has failed. Please try again. Order ID: $order_id";
    
    // mail($to, $subject, $message); // Uncomment when ready to send emails
}

/**
 * Log webhook data
 */
function logWebhookData($event, $payment_id, $order_id, $status, $amount) {
    $log_entry = date('Y-m-d H:i:s') . " - Event: $event, Payment: $payment_id, Order: $order_id, Status: $status, Amount: $amount\n";
    file_put_contents('razorpay_webhook.log', $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Log successful payment
 */
function logPaymentSuccess($payment_id, $order_id, $amount) {
    $log_entry = date('Y-m-d H:i:s') . " - SUCCESS: Payment $payment_id for Order $order_id, Amount: $amount\n";
    file_put_contents('razorpay_payments.log', $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Log failed payment
 */
function logPaymentFailure($payment_id, $order_id) {
    $log_entry = date('Y-m-d H:i:s') . " - FAILED: Payment $payment_id for Order $order_id\n";
    file_put_contents('razorpay_payments.log', $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Process successful order - similar to order-now.php
 */
function processSuccessfulOrder($conn, $user_id, $payment_id, $razorpay_order_id) {
    // Check if order details exist in session
    if(!isset($_SESSION['order_details'])) {
        error_log("Order details not found in session for user $user_id");
        return false;
    }
    
    // Validate payment ID
    if(empty($payment_id)) {
        error_log("Invalid payment ID received for user $user_id");
        return false;
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
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Log successful order creation
        error_log("Order created successfully: $order_id for user $user_id, Payment: $payment_id");
        
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        error_log("Order processing failed for user $user_id: " . $e->getMessage());
        return false;
    }
}
?>
