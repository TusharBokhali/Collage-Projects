<?php
/**
 * Test Credentials for Razorpay
 * Use these for immediate testing
 * 
 * ⚠️ IMPORTANT: These are PUBLIC test keys - safe to use in frontend
 * ⚠️ NEVER use these in production
 */

// Test Mode Credentials (Safe for frontend)
$test_key_id = 'rzp_test_1DP5mmOlF5G5ag'; // Public test key
$test_key_secret = 'thisissecret'; // Secret key (only for backend)

// Test Card Details
$test_cards = array(
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

// Test UPI IDs
$test_upi = array(
    'success' => 'success@razorpay',
    'failure' => 'failure@razorpay'
);

// Instructions
echo "<!-- 
    🔑 Test Credentials Loaded
    
    Test Key ID: $test_key_id
    Test Cards: 
    - Success: {$test_cards['success']['number']}
    - Failure: {$test_cards['failure']['number']}
    
    Test UPI:
    - Success: {$test_upi['success']}
    - Failure: {$test_upi['failure']}
    
    Instructions:
    1. Replace 'rzp_test_YOUR_TEST_KEY_ID' with '$test_key_id' in your files
    2. Use test cards for payment testing
    3. Use test UPI IDs for UPI testing
-->";
?>
