<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$payments = mysqli_query($conn, "
    SELECT booking_id, vehicle_number, vehicle_type, amount, transaction_id, created_at, status, payment_status
    FROM bookings
    WHERE user_id = '$user_id'
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History | SmartPark</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: linear-gradient(135deg, #0f172a, #020617); color: white; min-height: 100vh; }

        nav {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 8%; background: rgba(2,6,23,0.95);
            backdrop-filter: blur(10px); border-bottom: 1px solid #1e293b;
            position: sticky; top: 0; z-index: 100;
        }
        .logo { font-size: 1.4rem; font-weight: 800; color: #3b82f6; text-decoration: none; }
        .nav-links { display: flex; align-items: center; gap: 6px; }
        .nav-links a {
            color: #94a3b8; text-decoration: none; font-weight: 600;
            padding: 8px 14px; border-radius: 10px; font-size: 0.88rem; transition: 0.2s;
        }
        .nav-links a:hover { background: #1e293b; color: white; }
        .nav-links a.active-link { background: #1e293b; color: white; }
        .logout {
            background: linear-gradient(135deg, #f87171, #ef4444) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(239,68,68,0.3);
        }

        .container { max-width: 1000px; margin: 50px auto; padding: 0 5%; }
        h1 { font-size: 2rem; font-weight: 800; margin-bottom: 6px; }
        h1 span { color: #3b82f6; }
        .sub { color: #64748b; font-size: 0.9rem; margin-bottom: 30px; }

        .table-wrap {
            width: 100%; background: rgba(30,41,59,0.4);
            border-radius: 20px; border: 1px solid #1e293b;
            overflow: hidden; border-collapse: collapse;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
        }
        th {
            background: rgba(30,41,59,0.7); padding: 18px 20px;
            text-align: left; color: #94a3b8;
            font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px;
        }
        td { padding: 18px 20px; border-bottom: 1px solid #1e293b; font-size: 0.92rem; color: #cbd5e1; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .txn-id {
            font-family: 'Courier New', monospace; color: #3b82f6;
            font-weight: 700; background: rgba(59,130,246,0.1);
            padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(59,130,246,0.2);
            font-size: 0.85rem;
        }

        .badge { padding: 5px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .badge-success   { background: rgba(34,197,94,0.1);  color: #22c55e; border: 1px solid rgba(34,197,94,0.2); }
        .badge-cancelled { background: rgba(239,68,68,0.1);  color: #f87171; border: 1px solid rgba(239,68,68,0.2); }

        .no-data { text-align: center; padding: 60px; color: #334155; }
        .no-data a { color: #3b82f6; text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>

<nav>
    <a href="dashboard.php" class="logo">🚗 SmartPark</a>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="my_bookings.php">My Bookings</a>
        <a href="payments.php" class="active-link">Payment History</a>
        <a href="../logout.php" class="logout">Logout</a>
    </div>
</nav>

<div class="container">
    <h1>💳 My <span>Transactions</span></h1>
    <p class="sub">Complete payment history for your account.</p>

    <table class="table-wrap">
        <thead>
            <tr>
                <th>Date</th>
                <th>Vehicle</th>
                <th>Transaction / UTR ID</th>
                <th>Amount</th>
                <th>Payment</th>
                <th>Booking</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $found = false;
            while ($row = mysqli_fetch_assoc($payments)):
                $found = true;
            ?>
            <tr>
                <td><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                <td>
                    <div style="font-weight:700;color:#3b82f6;"><?= htmlspecialchars($row['vehicle_number']) ?></div>
                    <div style="font-size:0.75rem;color:#64748b;"><?= strtoupper($row['vehicle_type']) ?></div>
                </td>
                <td>
                    <?php if (!empty($row['transaction_id']) && $row['transaction_id'] !== 'CASH'): ?>
                        <span class="txn-id"><?= htmlspecialchars($row['transaction_id']) ?></span>
                    <?php elseif ($row['transaction_id'] === 'CASH'): ?>
                        <span style="color:#f59e0b;font-weight:600;">💵 Cash</span>
                    <?php else: ?>
                        <span style="color:#475569;">—</span>
                    <?php endif; ?>
                </td>
                <td style="font-weight:800;">₹<?= number_format($row['amount'], 2) ?></td>
                <td>
                    <span class="badge badge-<?= $row['status'] === 'cancelled' ? 'cancelled' : 'success' ?>">
                        <?= $row['status'] === 'cancelled' ? 'CANCELLED' : strtoupper($row['payment_status']) ?>
                    </span>
                </td>
                <td>
                    <span class="badge" style="background:rgba(99,102,241,0.1);color:#a5b4fc;border:1px solid #6366f1;">
                        <?= strtoupper($row['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endwhile; ?>

            <?php if (!$found): ?>
                <tr><td colspan="6" class="no-data">No transactions yet. <a href="dashboard.php">Book a slot →</a></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
