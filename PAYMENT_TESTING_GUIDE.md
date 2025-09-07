# 🔧 Payment Testing Guide - Fix Payment Failed Issues

This guide will help you resolve payment failed issues and properly test your Razorpay integration.

## 🚨 **Common Payment Failed Issues & Solutions**

### **Issue 1: "Invalid Key ID" Error**
**Problem**: Payment modal doesn't open or shows invalid key error
**Solution**: 
1. Check your Razorpay key in `razorpay_payment.php`
2. Ensure you're using the correct test key format: `rzp_test_...`
3. Verify the key is active in your Razorpay dashboard

### **Issue 2: Payment Modal Not Opening**
**Problem**: Clicking "Pay" button does nothing
**Solution**:
1. Check browser console for JavaScript errors
2. Ensure Razorpay SDK is loaded: `https://checkout.razorpay.com/v1/checkout.js`
3. Verify your internet connection
4. Check if any ad-blockers are blocking Razorpay

### **Issue 3: "Order Not Found" Error**
**Problem**: Razorpay can't find the order
**Solution**:
1. Ensure `razorpay_order_id` is properly generated
2. Check if the order ID is unique for each payment
3. Verify the order amount matches exactly

### **Issue 4: Payment Success but Order Not Created**
**Problem**: Payment succeeds but order isn't saved in database
**Solution**:
1. Check database connection
2. Verify table structure has required fields
3. Check for SQL errors in logs
4. Ensure session data is preserved

## 🧪 **Step-by-Step Testing Process**

### **Step 1: Update Test Credentials**
Replace the placeholder in `razorpay_payment.php`:
```php
// Change this line:
$razorpay_key_id = 'rzp_test_YOUR_TEST_KEY_ID';

// To this:
$razorpay_key_id = 'rzp_test_1DP5mmOlF5G5ag';
```

### **Step 2: Test Payment Flow**
1. **Add items to cart**
2. **Go to order-now.php**
3. **Fill order form completely**
4. **Click "Place Order"**
5. **Verify redirect to razorpay_payment.php**
6. **Click "Pay Securely with Razorpay"**
7. **Test with different payment methods**

### **Step 3: Test Different Scenarios**

#### **✅ Test Successful Payment**
- **Card**: `4111 1111 1111 1111`
- **Expiry**: Any future date (e.g., `12/25`)
- **CVV**: Any 3 digits (e.g., `123`)
- **Name**: Any name

#### **❌ Test Failed Payment**
- **Card**: `4000 0000 0000 0002`
- **Expiry**: Any future date
- **CVV**: Any 3 digits
- **Name**: Any name

#### **💳 Test UPI Payment**
- **UPI ID**: `success@razorpay` (always succeeds)
- **UPI ID**: `failure@razorpay` (always fails)

## 🔍 **Debugging Steps**

### **1. Check Browser Console**
Open Developer Tools (F12) and look for:
- JavaScript errors
- Network request failures
- Console.log messages

### **2. Check Network Tab**
Look for:
- Failed requests to Razorpay
- CORS errors
- 404/500 errors

### **3. Check PHP Error Logs**
Look for:
- Database connection errors
- SQL query errors
- Session errors

### **4. Verify Session Data**
Add this to your payment page for debugging:
```php
<?php
echo "<!-- Debug Info: ";
echo "Session: " . print_r($_SESSION, true);
echo "Order Details: " . print_r($_SESSION['order_details'], true);
echo " -->";
?>
```

## 🛠️ **Quick Fixes for Common Issues**

### **Fix 1: Session Issues**
```php
// Add this at the top of razorpay_payment.php
session_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

### **Fix 2: Database Connection Issues**
```php
// Add this to check database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
```

### **Fix 3: Amount Format Issues**
```php
// Ensure amount is in paise (multiply by 100)
$amount = $total_price * 100;
// Remove any decimal places
$amount = (int)$amount;
```

### **Fix 4: Order ID Generation Issues**
```php
// Generate unique order ID
$razorpay_order_id = 'RZP_' . time() . '_' . uniqid();
```

## 📱 **Mobile Testing**

### **Test on Mobile Devices**
1. **Use mobile browser** (Chrome, Safari)
2. **Test responsive design**
3. **Check payment modal on small screens**
4. **Test UPI apps integration**

### **Common Mobile Issues**
- **Touch events not working**
- **Modal not opening on mobile**
- **UPI apps not launching**

## 🔒 **Security Testing**

### **Test Input Validation**
1. **Try SQL injection**: `'; DROP TABLE orders; --`
2. **Test XSS**: `<script>alert('xss')</script>`
3. **Test CSRF**: Submit form from external site

### **Test Session Security**
1. **Check session timeout**
2. **Verify user authentication**
3. **Test session hijacking protection**

## 📊 **Database Testing**

### **Check Table Structure**
```sql
-- Run this to see your order table structure
DESCRIBE `order`;

-- Add missing columns if needed
ALTER TABLE `order` ADD COLUMN `razorpay_payment_id` VARCHAR(255);
ALTER TABLE `order` ADD COLUMN `razorpay_order_id` VARCHAR(255);
ALTER TABLE `order` ADD COLUMN `payment_status` VARCHAR(50);
```

### **Test Database Operations**
1. **Insert test order manually**
2. **Verify foreign key constraints**
3. **Check transaction rollback**

## 🚀 **Performance Testing**

### **Test Payment Speed**
1. **Measure time to open payment modal**
2. **Test payment processing time**
3. **Check for timeout issues**

### **Test Concurrent Payments**
1. **Open multiple payment tabs**
2. **Test simultaneous payments**
3. **Check for race conditions**

## 📝 **Testing Checklist**

- [ ] **Payment modal opens correctly**
- [ ] **Test cards work (success/failure)**
- [ ] **UPI payments work**
- [ ] **Order creation in database**
- [ ] **Cart clearing after success**
- [ ] **Error handling works**
- [ ] **Session management works**
- [ ] **Mobile responsiveness**
- [ ] **Security validation**
- [ ] **Database transactions**

## 🆘 **Emergency Fixes**

### **If Nothing Works:**
1. **Clear browser cache and cookies**
2. **Check if Razorpay is down**: [status.razorpay.com](https://status.razorpay.com)
3. **Verify your account status** in Razorpay dashboard
4. **Check if you've exceeded test limits**

### **Contact Support:**
- **Razorpay Support**: [support.razorpay.com](https://support.razorpay.com)
- **Check Razorpay Status**: [status.razorpay.com](https://status.razorpay.com)

## 🎯 **Success Indicators**

Your payment integration is working correctly when:
1. ✅ Payment modal opens without errors
2. ✅ Test cards process successfully
3. ✅ Orders are created in database
4. ✅ Cart is cleared after payment
5. ✅ Success/failure pages load correctly
6. ✅ No JavaScript errors in console
7. ✅ All payment methods work
8. ✅ Mobile devices work properly

---

**💡 Pro Tip**: Test with small amounts first, then gradually increase to ensure everything works correctly at scale.

**🔧 Need More Help?**: Check the browser console and PHP error logs for specific error messages that can help identify the exact issue.
