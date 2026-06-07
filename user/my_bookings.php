<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $bid   = (int)$_GET['cancel'];
    $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM bookings WHERE booking_id='$bid' AND user_id='$user_id' AND status='active'"));
    if ($check) {
        mysqli_query($conn, "UPDATE bookings SET status='cancelled' WHERE booking_id='$bid'");
        mysqli_query($conn, "UPDATE parking_slots SET status='available' WHERE slot_id='{$check['slot_id']}'");
    }
    header("Location: my_bookings.php?msg=cancelled");
    exit();
}

$bookings = mysqli_query($conn, "SELECT b.*, s.slot_number FROM bookings b JOIN parking_slots s ON b.slot_id = s.slot_id WHERE b.user_id = '$user_id' ORDER BY b.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | SmartPark</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #020617; color: white; min-height: 100vh; }

        nav {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 5%;
            background: rgba(2, 6, 23, 0.95);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            position: sticky; top: 0; z-index: 1000;
        }
        .logo { font-size: 1.4rem; font-weight: 800; color: #3b82f6; text-decoration: none; }
        .nav-links { display: flex; align-items: center; gap: 6px; }
        .nav-links a {
            color: #94a3b8; text-decoration: none; font-weight: 600;
            padding: 8px 14px; border-radius: 10px; font-size: 0.88rem; transition: 0.2s;
        }
        .nav-links a:hover, .nav-links a.active-link { background: rgba(59,130,246,0.1); color: white; }
        .logout {
            background: linear-gradient(135deg, #f87171, #ef4444) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(239,68,68,0.3);
        }

        .container { max-width: 1200px; margin: 40px auto; padding: 0 5%; }

        h1 { font-size: 2rem; font-weight: 800; margin-bottom: 6px; }
        h1 span { color: #3b82f6; }
        .sub { color: #64748b; font-size: 0.9rem; margin-bottom: 28px; }

        .msg {
            background: rgba(34,197,94,0.1); border: 1px solid #22c55e;
            color: #4ade80; padding: 14px 18px; border-radius: 12px;
            margin-bottom: 24px; font-size: 0.9rem;
        }

        .table-card {
            background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px; overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        table { width: 100%; border-collapse: collapse; }
        th {
            padding: 18px 20px; background: rgba(0,0,0,0.25);
            color: #64748b; font-size: 0.7rem; text-transform: uppercase;
            letter-spacing: 1.5px; text-align: left;
        }
        td { padding: 18px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.92rem; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .slot-pill {
            background: #3b82f6; color: white;
            padding: 4px 10px; border-radius: 6px;
            font-weight: 800; font-size: 0.8rem;
        }

        .badge { padding: 5px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .badge-active    { background: rgba(34,197,94,0.15);  color: #4ade80; border: 1px solid #22c55e; }
        .badge-completed { background: rgba(99,102,241,0.15); color: #a5b4fc; border: 1px solid #6366f1; }
        .badge-cancelled { background: rgba(239,68,68,0.15);  color: #f87171; border: 1px solid #ef4444; }

        .cancel-btn { color: #f87171; text-decoration: none; font-weight: 700; font-size: 0.85rem; }
        .cancel-btn:hover { text-decoration: underline; }

        .no-data { text-align: center; padding: 60px; color: #334155; font-size: 1rem; }
        .no-data a { color: #3b82f6; text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>

<nav>
    <a href="dashboard.php" class="logo">🚗 SmartPark</a>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="my_bookings.php" class="active-link">My Bookings</a>
        <a href="payments.php">Payment History</a>
        <a href="../logout.php" class="logout">Logout</a>
    </div>
</nav>

<div class="container">
    <h1>My <span>Bookings</span></h1>
    <p class="sub">All your parking history in one place.</p>

    <?php if (isset($_GET['msg'])): ?>
        <div class="msg">✅ Booking cancelled successfully. Slot has been released.</div>
    <?php endif; ?>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Slot</th>
                    <th>Vehicle</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Duration</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $found = false;
                while ($b = mysqli_fetch_assoc($bookings)):
                    $found = true;
                ?>
                <tr>
                    <td><span class="slot-pill"><?= $b['slot_number'] ?></span></td>
                    <td>
                        <div style="font-weight:700;"><?= htmlspecialchars($b['vehicle_number']) ?></div>
                        <div style="font-size:0.75rem;color:#64748b;"><?= strtoupper($b['vehicle_type']) ?></div>
                    </td>
                    <td><?= date('d M, h:i A', strtotime($b['check_in'])) ?></td>
                    <td><?= date('d M, h:i A', strtotime($b['check_out'])) ?></td>
                    <td><?= $b['duration_hours'] ?>h</td>
                    <td style="font-weight:800;color:#3b82f6;">₹<?= $b['amount'] ?></td>
                    <td><span class="badge badge-<?= $b['status'] ?>"><?= $b['status'] ?></span></td>
                    <td>
                        <?php if ($b['status'] === 'active'): ?>
                            <a href="?cancel=<?= $b['booking_id'] ?>" class="cancel-btn" onclick="return confirm('Cancel this booking?')">Cancel</a>
                        <?php else: ?>
                            <span style="color:#334155;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php if (!$found): ?>
                    <tr><td colspan="8" class="no-data">No bookings yet. <a href="dashboard.php">Book your first slot →</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
