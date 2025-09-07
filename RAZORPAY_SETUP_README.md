# 🚀 Razorpay Integration Setup Guide

This guide will help you set up Razorpay payment gateway integration for your e-commerce website.

## 📋 Prerequisites

1. **Razorpay Account**: Sign up at [razorpay.com](https://razorpay.com)
2. **Test API Keys**: Available in your Razorpay dashboard
3. **PHP Environment**: PHP 7.0+ with cURL extension
4. **HTTPS**: Required for production (Razorpay requirement)

## 🔑 Step 1: Get Razorpay API Keys

### Test Mode (Development)
1. Login to [Razorpay Dashboard](https://dashboard.razorpay.com)
2. Go to **Settings** → **API Keys**
3. Copy your **Test Key ID** and **Test Key Secret**
4. These start with `rzp_test_`

### Production Mode (Live Website)
1. In Razorpay Dashboard, switch to **Live Mode**
2. Go to **Settings** → **API Keys**
3. Copy your **Live Key ID** and **Live Key Secret**
4. These start with `rzp_live_`

## ⚙️ Step 2: Configure Credentials

### Update `razorpay_config.php`
```php
// Test Mode Credentials
define('RAZORPAY_TEST_KEY_ID', 'rzp_test_R9R8ogg9z07pEW');
define('RAZORPAY_TEST_KEY_SECRET', 'yv7TqC9TIC1gV2na8cuy3tsh');

// Production Mode Credentials  
define('RAZORPAY_LIVE_KEY_ID', 'rzp_test_R9R8ogg9z07pEW');
define('RAZORPAY_LIVE_KEY_SECRET', 'yv7TqC9TIC1gV2na8cuy3tsh');

// Environment setting
define('RAZORPAY_ENVIRONMENT', 'test'); // Change to 'production' for live
```

### Update `order-now.php`
```php
// Replace these with your actual credentials
$razorpay_key_id = 'rzp_test_R9R8ogg9z07pEW';
$razorpay_key_secret = 'yv7TqC9TIC1gV2na8cuy3tsh';
```

### Update `razorpay_payment.php`
```php
// Replace these with your actual credentials
$razorpay_key_id = 'rzp_test_R9R8ogg9z07pEW';
$razorpay_key_secret = 'yv7TqC9TIC1gV2na8cuy3tsh';
```

## 🧪 Step 3: Test Mode Setup

### Test Card Details
Use these cards for testing payments:

**✅ Successful Payment:**
- Card Number: `4111 1111 1111 1111`
- Expiry: Any future date (e.g., `12/25`)
- CVV: Any 3 digits (e.g., `123`)
- Name: Any name

**❌ Failed Payment:**
- Card Number: `4000 0000 0000 0002`
- Expiry: Any future date
- CVV: Any 3 digits
- Name: Any name

### Test UPI
- UPI ID: `success@razorpay` (always succeeds)
- UPI ID: `failure@razorpay` (always fails)

## 🌐 Step 4: Production Setup

### 1. Switch Environment
```php
// In razorpay_config.php
define('RAZORPAY_ENVIRONMENT', 'production');
```

### 2. Update Credentials
Use your live API keys instead of test keys.

### 3. Set Up Webhook
1. In Razorpay Dashboard → **Settings** → **Webhooks**
2. Add webhook URL: `https://yourdomain.com/razorpay_webhook.php`
3. Select events: `payment.captured`, `payment.failed`, `order.paid`
4. Copy the webhook secret

### 4. Update Webhook Secret
```php
// In razorpay_webhook.php
$webhook_secret = 'YOUR_ACTUAL_WEBHOOK_SECRET';
```

## 📁 File Structure

```
├── order-now.php              # Main order page with Razorpay integration
├── razorpay_payment.php       # Payment processing page
├── razorpay_config.php        # Configuration file
├── razorpay_webhook.php       # Webhook handler (production)
└── RAZORPAY_SETUP_README.md  # This file
```

## 🔄 Payment Flow

1. **User fills order form** → `order-now.php`
2. **Order details stored** in session
3. **Redirect to payment** → `razorpay_payment.php`
4. **Razorpay modal opens** with payment options
5. **Payment processed** by Razorpay
6. **Success callback** → Order confirmed in database
7. **Cart cleared** and user redirected to success page

## 🛡️ Security Features

- **Input Sanitization**: All user inputs are sanitized
- **Session Validation**: User must be logged in
- **Webhook Verification**: Payment signatures are verified
- **Database Transactions**: Ensures data integrity
- **Error Handling**: Comprehensive error handling and logging

## 🧪 Testing Checklist

- [ ] Test successful payment with test card
- [ ] Test failed payment with failure card
- [ ] Test UPI payment (success/failure)
- [ ] Verify order creation in database
- [ ] Check cart clearing after successful payment
- [ ] Test session handling
- [ ] Verify error handling

## 🚨 Common Issues & Solutions

### Issue: "Invalid Key ID"
**Solution**: Check your API key in `razorpay_config.php`

### Issue: Payment not processing
**Solution**: Ensure you're using correct test/live credentials

### Issue: Webhook not working
**Solution**: Check webhook URL and secret in Razorpay dashboard

### Issue: Order not created
**Solution**: Check database connection and table structure

## 📊 Database Requirements

Your `order` table should have these additional fields:
```sql
ALTER TABLE `order` ADD COLUMN `razorpay_payment_id` VARCHAR(255);
ALTER TABLE `order` ADD COLUMN `razorpay_order_id` VARCHAR(255);
ALTER TABLE `order` ADD COLUMN `payment_status` VARCHAR(50);
```

## 📞 Support

- **Razorpay Support**: [support.razorpay.com](https://support.razorpay.com)
- **Documentation**: [docs.razorpay.com](https://docs.razorpay.com)
- **Test Dashboard**: [dashboard.razorpay.com](https://dashboard.razorpay.com)

## 🔄 Updates & Maintenance

1. **Regular Updates**: Keep Razorpay SDK updated
2. **Monitor Logs**: Check webhook and payment logs regularly
3. **Security**: Regularly review and update security measures
4. **Testing**: Test payment flow after any code changes

---

**⚠️ Important Notes:**
- Never commit real API keys to version control
- Always test in test mode before going live
- Keep webhook secrets secure
- Monitor payment logs for any issues
- Test thoroughly before production deployment

**🎯 Ready to Go Live?**
1. Complete all testing in test mode
2. Switch environment to 'production'
3. Update all credentials to live keys
4. Set up production webhook
5. Test with small amounts first
6. Monitor closely after going live
