<?php
session_start();
require '../config/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
if (!isset($_SESSION['temp_booking'])) { header("Location: dashboard.php"); exit(); }
$b = $_SESSION['temp_booking'];

if (isset($_POST['confirm_payment'])) {
    $user_id = $_SESSION['user_id'];
    $method  = $_POST['method'];
    $tid     = ($method == 'upi') ? mysqli_real_escape_string($conn, trim($_POST['tid'])) : 'CASH';

    $out = date('Y-m-d H:i:s', strtotime($b['check_in']) + ($b['duration'] * 3600));

    $q = "INSERT INTO bookings (user_id, slot_id, vehicle_number, vehicle_type, check_in, check_out, duration_hours, amount, transaction_id, payment_status, status)
          VALUES ('{$_SESSION['user_id']}', '{$b['slot_id']}', '{$b['vehicle_number']}', '{$b['vehicle_type']}', '{$b['check_in']}', '$out', '{$b['duration']}', '{$b['amount']}', '$tid', 'paid', 'active')";

    if (mysqli_query($conn, $q)) {
        mysqli_query($conn, "UPDATE parking_slots SET status='occupied' WHERE slot_id='{$b['slot_id']}'");
        unset($_SESSION['temp_booking']);
        header("Location: dashboard.php");
        exit();
    } else {
        $db_error = mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | SmartPark</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            background: #020617; color: white; min-height: 100vh;
            display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 30px 20px;
        }
        .back-link { align-self: flex-start; margin-left: max(20px, calc(50% - 380px)); margin-bottom: 16px; color: #3b82f6; text-decoration: none; font-size: 0.85rem; font-weight: 600; }

        .pay-wrap { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; width: 100%; max-width: 760px; }
        .pay-card {
            background: #0f172a; padding: 32px; border-radius: 20px;
            border: 1px solid #1e293b;
        }
        .pay-card h3 { font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 20px; }

        /* Summary card */
        .summary-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #1e293b; font-size: 0.9rem; }
        .summary-row:last-child { border-bottom: none; }
        .summary-row span:first-child { color: #64748b; }
        .summary-row span:last-child { color: white; font-weight: 600; }
        .total-row { display: flex; justify-content: space-between; margin-top: 16px; padding: 16px; background: #1e293b; border-radius: 12px; }
        .total-row span:first-child { color: #94a3b8; font-size: 0.9rem; }
        .total-row .amount { font-size: 1.8rem; color: #3b82f6; font-weight: 800; }

        /* QR */
        .qr-box { background: #1e293b; border-radius: 14px; padding: 20px; text-align: center; margin-bottom: 18px; }
        .qr-box img { border-radius: 10px; border: 3px solid white; display: block; margin: 0 auto 10px; }
        .qr-placeholder {
            width: 150px; height: 150px; background: #0f172a; border-radius: 10px;
            border: 2px solid #3b82f6; display: flex; flex-direction: column;
            align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 2.5rem;
        }
        /* 
            ===================================================
            TO ADD YOUR QR CODE:
            1. Save image to: smartpark/images/qr.jpg
            2. Replace <div class="qr-placeholder"> with:
               <img src="../images/qr.jpg" width="150" height="150" style="border-radius:10px;border:3px solid white;display:block;margin:0 auto 10px;">
            ===================================================
        */
        .qr-box p { color: #64748b; font-size: 0.78rem; }
        .qr-box .upi-id { color: #3b82f6; font-weight: 700; font-size: 0.95rem; margin-top: 4px; }

        /* Methods */
        .method-label {
            background: #1e293b; border: 2px solid #334155; padding: 14px 16px;
            border-radius: 12px; margin-bottom: 10px; display: flex; align-items: center;
            gap: 10px; cursor: pointer; transition: border-color 0.2s; font-size: 0.92rem;
        }
        .method-label:hover { border-color: #3b82f6; }
        .method-label input[type="radio"] { accent-color: #3b82f6; width: 16px; height: 16px; }

        .tid-box { display: none; margin-top: 10px; }
        input[type="text"] {
            width: 100%; padding: 12px 14px; border-radius: 10px;
            border: 1px solid #334155; background: #020617; color: white;
            font-size: 0.9rem; outline: none; transition: border 0.2s; font-family: inherit;
        }
        input[type="text"]:focus { border-color: #3b82f6; }

        .btn {
            width: 100%; padding: 15px; background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none; color: white; font-weight: 800; border-radius: 12px;
            margin-top: 18px; cursor: pointer; font-family: inherit; font-size: 1rem;
            transition: all 0.2s;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(34,197,94,0.35); }

        .error-box { background: rgba(127,29,29,0.4); border: 1px solid #991b1b; color: #fca5a5; padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; font-size: 0.85rem; }

        @media(max-width: 600px) { .pay-wrap { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<a href="dashboard.php" class="back-link">← Back to Dashboard</a>

<div class="pay-wrap">

    <!-- Booking Summary -->
    <div class="pay-card">
        <h3>📋 Booking Summary</h3>
        <div class="summary-row"><span>Vehicle Type</span><span><?= strtoupper($b['vehicle_type']) ?></span></div>
        <div class="summary-row"><span>Vehicle Number</span><span><?= htmlspecialchars($b['vehicle_number']) ?></span></div>
        <div class="summary-row"><span>Check-in</span><span><?= date('d M Y, h:i A', strtotime($b['check_in'])) ?></span></div>
        <div class="summary-row"><span>Duration</span><span><?= $b['duration'] ?> Hour<?= $b['duration'] > 1 ? 's' : '' ?></span></div>
        <div class="summary-row"><span>Check-out</span><span><?= date('d M Y, h:i A', strtotime($b['check_in']) + ($b['duration'] * 3600)) ?></span></div>
        <div class="total-row">
            <span>Total Amount</span>
            <span class="amount">₹<?= $b['amount'] ?></span>
        </div>

        <div class="qr-box" style="margin-top: 20px;">
            <div class="qr-placeholder">
                📲
                <span style="font-size:0.65rem;color:#64748b;margin-top:6px;">Your QR Here</span>
            </div>
            <!--
                TO ADD YOUR QR:
                Replace the div.qr-placeholder above with:
                <img src="../images/qr.jpg" width="150" height="150" style="border-radius:10px;border:3px solid white;display:block;margin:0 auto 10px;">
            -->
            <p>Scan with GPay / PhonePe / Paytm</p>
            <div class="upi-id">smartpark@upi</div>
            <!-- Change the UPI ID above to your actual UPI ID -->
        </div>
    </div>

    <!-- Payment Form -->
    <div class="pay-card">
        <h3>💳 Choose Payment Method</h3>

        <?php if (isset($db_error)): ?>
            <div class="error-box">DB Error: <?= $db_error ?></div>
        <?php endif; ?>

        <form method="POST">
            <label class="method-label">
                <input type="radio" name="method" value="cash" checked onchange="toggleUPI(false)">
                💵 Cash Payment (Pay at counter)
            </label>
            <label class="method-label">
                <input type="radio" name="method" value="upi" onchange="toggleUPI(true)">
                📱 UPI / QR Scan
            </label>

            <div id="tid-box" class="tid-box">
                <input type="text" name="tid" id="tid_input" placeholder="Enter Transaction ID / UTR Number">
            </div>

            <button type="submit" name="confirm_payment" class="btn">✅ CONFIRM & BOOK SLOT</button>
        </form>
    </div>

</div>

<script>
    function toggleUPI(show) {
        const box = document.getElementById('tid-box');
        const inp = document.getElementById('tid_input');
        box.style.display = show ? 'block' : 'none';
        inp.required = show;
    }
</script>
</body>
</html>
