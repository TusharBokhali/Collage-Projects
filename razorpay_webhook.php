<?php
/**
 * Razorpay Webhook Handler
 * This file handles payment notifications from Razorpay
 * Use this for production mode
 */

include_once 'site_connection.php';
include_once 'razorpay_config.php';

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
    
    // Update order status in database
    $update_query = "UPDATE `order` SET 
                     `status` = 'confirmed', 
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
?>
