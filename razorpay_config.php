<?php
/**
 * Razorpay Configuration File
 * Update these credentials for testing and production
 */

// Test Mode Credentials (for development/testing)
define('RAZORPAY_TEST_KEY_ID', 'rzp_test_R9R8ogg9z07pEW');
define('RAZORPAY_TEST_KEY_SECRET', 'yv7TqC9TIC1gV2na8cuy3tsh');

// Production Mode Credentials (for live website)
define('RAZORPAY_LIVE_KEY_ID', 'rzp_test_R9R8ogg9z07pEW');
define('RAZORPAY_LIVE_KEY_SECRET', 'yv7TqC9TIC1gV2na8cuy3tsh');

// Environment setting (change to 'production' for live website)
define('RAZORPAY_ENVIRONMENT', 'test'); // Options: 'test' or 'production'

// Get current environment credentials
function getRazorpayCredentials() {
    if (RAZORPAY_ENVIRONMENT === 'production') {
        return array(
            'key_id' => 'rzp_test_R9R8ogg9z07pEW',
            'key_secret' => 'yv7TqC9TIC1gV2na8cuy3tsh'
        );
    } else {
        return array(
            'key_id' => 'rzp_test_R9R8ogg9z07pEW',
            'key_secret' => 'yv7TqC9TIC1gV2na8cuy3tsh'
        );
    }
}

// Get current environment name
function getRazorpayEnvironment() {
    return RAZORPAY_ENVIRONMENT;
}

// Check if in test mode
function isTestMode() {
    return RAZORPAY_ENVIRONMENT === 'test';
}

// Check if in production mode
function isProductionMode() {
    return RAZORPAY_ENVIRONMENT === 'production';
}

// Get webhook URL based on environment
function getWebhookUrl() {
    if (isTestMode()) {
        return 'https://your-test-domain.com/razorpay_webhook.php';
    } else {
        return 'https://your-live-domain.com/razorpay_webhook.php';
    }
}

// Test card details for testing mode
function getTestCardDetails() {
    if (isTestMode()) {
        return array(
            'success' => array(
                'number' => '4111 1111 1111 1111',
                'expiry' => '12/25',
                'cvv' => '123',
                'name' => 'Test User'
            ),
            'failure' => array(
                'number' => '4000 0000 0000 0002',
                'expiry' => '12/25',
                'cvv' => '123',
                'name' => 'Test User'
            )
        );
    }
    return array();
}
?>
