# SmartPark — Setup Instructions

## STEP 1: Copy folder
Put the entire `smartpark` folder in:
→ C:\xampp\htdocs\smartpark

## STEP 2: Import Database
1. Open browser → http://localhost/phpmyadmin
2. Click "New" → Create database named `smartpark`
3. Click the `smartpark` database → go to "Import" tab
4. Choose file → select `smartpark.sql`
5. Click "Go"

## STEP 3: Run the project
Open browser → http://localhost/smartpark

This will auto-redirect to login.php 

## Default Login Credentials
- **Admin:** admin@smartpark.com / admin123
- **User:** Register a new account

## File Structure
```
smartpark/
├── index.php          ← redirects to login
├── login.php          ← login page
├── register.php       ← register page
├── logout.php         ← clears session
├── smartpark.sql      ← database file (IMPORT THIS)
├── config/
│   └── db.php         ← database connection
├── user/
│   ├── dashboard.php  ← book slots, view stats
│   ├── my_bookings.php
│   └── payment.php    ← QR code payment
├── admin/
│   ├── dashboard.php  ← admin overview
│   └── slots.php      ← manage slots
└── images/
    └── qr_code.jpg    ← PUT YOUR QR CODE HERE
```

## Adding Your QR Code
1. Save your QR image as `smartpark/images/qr_code.jpg`
2. Open `user/payment.php`
3. Find the comment that says "TO USE YOUR OWN QR CODE"
4. Replace the `<div class="qr-placeholder">` block with:
   ```html
   <img src="../images/qr_code.jpg" style="width:180px;height:180px;border-radius:12px;">
   ```
5. Change the UPI ID text from `smartpark@upi` to your actual UPI ID

## Pricing
- Bike: ₹20/hour
- Car: ₹50/hour  
- Truck: ₹100/hour
