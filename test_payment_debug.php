<?php
/**
 * Payment Debug Page
 * Use this to test and debug payment issues
 */

include_once 'site_connection.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if(!isset($_SESSION['login'])) {
    echo "<h2>❌ User Not Logged In</h2>";
    echo "<p>Please <a href='login_home.php'>login first</a></p>";
    exit();
}

$login_id = $_SESSION['login'];

// Get cart items
$cart_query = "SELECT * FROM `cart` WHERE `user_id`='$login_id'";
$cart_result = mysqli_query($conn, $cart_query);
$cart_count = mysqli_num_rows($cart_result);

// Calculate total
$total_price = 0;
if($cart_count > 0) {
    while($row = mysqli_fetch_assoc($cart_result)) {
        $total_price += $row['price'] * $row['num_product'];
    }
}

// Get user details
$user_query = "SELECT * FROM `user_register` WHERE `id`='$login_id'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment Debug - Test Page</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <style>
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .warning { background-color: #fff3cd; border-color: #ffeaa7; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        .test-card { background-color: #e8f5e8; padding: 10px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container mt-4">
    <h1>🔧 Payment Debug & Test Page</h1>
    
    <!-- Session Status -->
    <div class="debug-section info">
        <h3>📋 Session Status</h3>
        <p><strong>User ID:</strong> <?php echo $login_id; ?></p>
        <p><strong>User Name:</strong> <?php echo htmlspecialchars($user_data['name'] ?? 'N/A'); ?></p>
        <p><strong>Session Active:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? '✅ Yes' : '❌ No'; ?></p>
        <p><strong>Order Details in Session:</strong> <?php echo isset($_SESSION['order_details']) ? '✅ Yes' : '❌ No'; ?></p>
    </div>
    
    <!-- Cart Status -->
    <div class="debug-section <?php echo $cart_count > 0 ? 'success' : 'warning'; ?>">
        <h3>🛒 Cart Status</h3>
        <p><strong>Cart Items:</strong> <?php echo $cart_count; ?></p>
        <p><strong>Total Amount:</strong> ₹<?php echo number_format($total_price, 2); ?></p>
        <p><strong>Amount in Paise:</strong> <?php echo $total_price * 100; ?></p>
        
        <?php if($cart_count == 0): ?>
            <p class="text-warning">⚠️ Cart is empty. Add items to test payment.</p>
        <?php endif; ?>
    </div>
    
    <!-- Database Connection -->
    <div class="debug-section <?php echo $conn ? 'success' : 'error'; ?>">
        <h3>🗄️ Database Connection</h3>
        <p><strong>Connection Status:</strong> <?php echo $conn ? '✅ Connected' : '❌ Failed'; ?></p>
        <?php if($conn): ?>
            <p><strong>Server Info:</strong> <?php echo mysqli_get_server_info($conn); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Test Payment Section -->
    <?php if($cart_count > 0): ?>
    <div class="debug-section success">
        <h3>💳 Test Payment</h3>
        <p>Click the button below to test the payment flow:</p>
        
        <form method="post" action="order-now.php">
            <input type="hidden" name="address" value="123 Test Street, Test City">
            <input type="hidden" name="city" value="Test City">
            <input type="hidden" name="pincode" value="123456">
            <input type="hidden" name="name" value="<?php echo htmlspecialchars($user_data['name'] ?? 'Test User'); ?>">
            <input type="hidden" name="mobile" value="<?php echo htmlspecialchars($user_data['mobile_number'] ?? '1234567890'); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? 'test@example.com'); ?>">
            <input type="hidden" name="payment" value="Credit / Debit Card">
            <input type="hidden" name="date_time" value="<?php echo date('Y-m-d H:i:s'); ?>">
            
            <button type="submit" name="buy" class="btn btn-primary btn-lg">
                🚀 Test Payment Flow
            </button>
        </form>
    </div>
    <?php endif; ?>
    
    <!-- Test Cards Information -->
    <div class="debug-section info">
        <h3>🧪 Test Cards</h3>
        <div class="test-card">
            <h5>✅ Success Card</h5>
            <p><strong>Number:</strong> 4111 1111 1111 1111</p>
            <p><strong>Expiry:</strong> Any future date (e.g., 12/25)</p>
            <p><strong>CVV:</strong> Any 3 digits (e.g., 123)</p>
            <p><strong>Name:</strong> Any name</p>
        </div>
        
        <div class="test-card">
            <h5>❌ Failure Card</h5>
            <p><strong>Number:</strong> 4000 0000 0000 0002</p>
            <p><strong>Expiry:</strong> Any future date</p>
            <p><strong>CVV:</strong> Any 3 digits</p>
            <p><strong>Name:</strong> Any name</p>
        </div>
        
        <div class="test-card">
            <h5>💳 Test UPI</h5>
            <p><strong>Success UPI:</strong> success@razorpay</p>
            <p><strong>Failure UPI:</strong> failure@razorpay</p>
        </div>
    </div>
    
    <!-- Common Issues & Solutions -->
    <div class="debug-section warning">
        <h3>🚨 Common Issues & Quick Fixes</h3>
        
        <h5>Issue: "Something went wrong"</h5>
        <ul>
            <li>Check browser console (F12) for JavaScript errors</li>
            <li>Verify Razorpay SDK is loaded</li>
            <li>Check internet connection</li>
            <li>Clear browser cache and cookies</li>
        </ul>
        
        <h5>Issue: "Payment failed"</h5>
        <ul>
            <li>Use correct test card numbers</li>
            <li>Check if test key is working</li>
            <li>Verify amount format (should be in paise)</li>
            <li>Check session data is preserved</li>
        </ul>
        
        <h5>Issue: Payment modal not opening</h5>
        <ul>
            <li>Check for JavaScript errors in console</li>
            <li>Verify Razorpay key is correct</li>
            <li>Check if ad-blockers are blocking Razorpay</li>
            <li>Try different browser</li>
        </ul>
    </div>
    
    <!-- Debug Actions -->
    <div class="debug-section info">
        <h3>🔍 Debug Actions</h3>
        <button onclick="checkRazorpaySDK()" class="btn btn-info">Check Razorpay SDK</button>
        <button onclick="testSession()" class="btn btn-info">Test Session Data</button>
        <button onclick="clearSession()" class="btn btn-warning">Clear Session</button>
        <button onclick="location.reload()" class="btn btn-secondary">Refresh Page</button>
    </div>
    
    <!-- Console Output -->
    <div class="debug-section">
        <h3>📝 Console Output</h3>
        <div id="console-output" style="background: #000; color: #0f0; padding: 10px; height: 200px; overflow-y: scroll; font-family: monospace;">
            <div>Console output will appear here...</div>
        </div>
    </div>
</div>

<script>
// Override console.log to show output on page
const originalLog = console.log;
const originalError = console.error;
const consoleOutput = document.getElementById('console-output');

function addToConsole(message, type = 'log') {
    const div = document.createElement('div');
    div.style.color = type === 'error' ? '#ff6b6b' : '#0f0';
    div.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
    consoleOutput.appendChild(div);
    consoleOutput.scrollTop = consoleOutput.scrollHeight;
}

console.log = function(...args) {
    originalLog.apply(console, args);
    addToConsole(args.join(' '));
};

console.error = function(...args) {
    originalError.apply(console, args);
    addToConsole(args.join(' '), 'error');
};

// Debug functions
function checkRazorpaySDK() {
    if (typeof Razorpay === 'undefined') {
        addToConsole('❌ Razorpay SDK not loaded!', 'error');
        alert('Razorpay SDK not loaded. Check internet connection.');
    } else {
        addToConsole('✅ Razorpay SDK loaded successfully');
        alert('Razorpay SDK is working!');
    }
}

function testSession() {
    addToConsole('Testing session data...');
    // You can add AJAX call here to test session
    alert('Session test completed. Check console for details.');
}

function clearSession() {
    if (confirm('Are you sure you want to clear all session data?')) {
        fetch('clear_session.php')
            .then(response => response.text())
            .then(data => {
                addToConsole('Session cleared');
                location.reload();
            })
            .catch(error => {
                addToConsole('Error clearing session: ' + error, 'error');
            });
    }
}

// Page load logging
document.addEventListener('DOMContentLoaded', function() {
    addToConsole('🔧 Payment Debug Page Loaded');
    addToConsole('User ID: <?php echo $login_id; ?>');
    addToConsole('Cart Items: <?php echo $cart_count; ?>');
    addToConsole('Total Amount: ₹<?php echo number_format($total_price, 2); ?>');
    
    // Check Razorpay SDK
    if (typeof Razorpay === 'undefined') {
        addToConsole('⚠️ Razorpay SDK not loaded yet', 'error');
    } else {
        addToConsole('✅ Razorpay SDK available');
    }
});

// Load Razorpay SDK
const script = document.createElement('script');
script.src = 'https://checkout.razorpay.com/v1/checkout.js';
script.onload = function() {
    addToConsole('✅ Razorpay SDK loaded successfully');
};
script.onerror = function() {
    addToConsole('❌ Failed to load Razorpay SDK', 'error');
};
document.head.appendChild(script);
</script>

</body>
</html>
