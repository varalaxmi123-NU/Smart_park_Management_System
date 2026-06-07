<?php
session_start();
require '../config/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
$user_id = $_SESSION['user_id'];

$active_q     = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE user_id='$user_id' AND status='active'");
$active_count = mysqli_fetch_assoc($active_q)['total'];

$spent_q     = mysqli_query($conn, "SELECT SUM(amount) as t FROM bookings WHERE user_id='$user_id' AND status != 'cancelled'");
$total_spent = mysqli_fetch_assoc($spent_q)['t'] ?? 0;

$avail_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM parking_slots WHERE status='available'");
$avail   = mysqli_fetch_assoc($avail_q)['c'];

$avail_bike  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM parking_slots WHERE vehicle_type='bike' AND status='available'"))['c'];
$avail_car   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM parking_slots WHERE vehicle_type='car' AND status='available'"))['c'];
$avail_truck = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM parking_slots WHERE vehicle_type='truck' AND status='available'"))['c'];

$error = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $type     = $_POST['vehicle_type'];
    $num      = strtoupper(mysqli_real_escape_string($conn, $_POST['vehicle_number']));
    $dur      = (int)$_POST['duration'];
    $check_in = $_POST['check_in'];

    $slot_q = mysqli_query($conn, "SELECT slot_id FROM parking_slots WHERE vehicle_type='$type' AND status='available' LIMIT 1");
    $slot   = mysqli_fetch_assoc($slot_q);

    if ($slot) {
        $rate = ['bike' => 20, 'car' => 50, 'truck' => 100][$type];
        $_SESSION['temp_booking'] = [
            'slot_id'        => $slot['slot_id'],
            'vehicle_number' => $num,
            'vehicle_type'   => $type,
            'check_in'       => $check_in,
            'duration'       => $dur,
            'amount'         => $rate * $dur
        ];
        header("Location: payment.php");
        exit();
    } else {
        $error = "No available slots for " . ucfirst($type) . " right now!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SmartPark</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #020617; color: white; min-height: 100vh; }

        nav {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 8%; background: #0f172a;
            border-bottom: 1px solid #1e293b;
            position: sticky; top: 0; z-index: 100;
        }
        .logo { font-size: 1.4rem; font-weight: 800; color: #3b82f6; text-decoration: none; }
        .nav-links { display: flex; align-items: center; gap: 6px; }
        .nav-links a {
            color: #94a3b8; text-decoration: none; font-weight: 600;
            padding: 8px 16px; border-radius: 10px; font-size: 0.88rem; transition: 0.2s;
        }
        .nav-links a:hover { background: #1e293b; color: white; }
        .logout {
            background: linear-gradient(135deg, #f87171, #ef4444) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(239,68,68,0.3);
        }
        .logout:hover { transform: translateY(-1px); filter: brightness(1.1); }

        .container { max-width: 1000px; margin: 40px auto; padding: 0 5%; }

        .welcome { margin-bottom: 28px; }
        .welcome h2 { font-size: 1.7rem; font-weight: 800; }
        .welcome h2 span { color: #3b82f6; }
        .welcome p { color: #64748b; margin-top: 4px; font-size: 0.9rem; }

        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
        .card {
            background: #0f172a; padding: 24px; border-radius: 18px;
            border: 1px solid #1e293b; text-align: center;
        }
        .card .icon { font-size: 2rem; margin-bottom: 8px; }
        .card h2 { font-size: 2rem; color: #3b82f6; font-weight: 800; }
        .card p { color: #64748b; font-size: 0.82rem; margin-top: 4px; }
        .card.green h2 { color: #22c55e; }
        .card.green { border-color: #166534; }

        .form-box {
            background: #0f172a; padding: 30px; border-radius: 20px;
            border: 1px solid #1e293b; max-width: 600px; margin: auto;
        }
        .form-box h3 { font-size: 1rem; font-weight: 700; color: #94a3b8; margin-bottom: 18px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Vehicle type selector */
        .vehicle-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
        .v-card {
            background: #1e293b; border: 2px solid #334155; border-radius: 12px;
            padding: 16px 10px; text-align: center; cursor: pointer; transition: all 0.2s;
        }
        .v-card:hover { border-color: #3b82f6; }
        .v-card.selected { border-color: #3b82f6; background: #1e3a5f; }
        .v-icon { font-size: 1.8rem; margin-bottom: 6px; }
        .v-name { font-weight: 700; font-size: 0.88rem; }
        .v-rate { color: #64748b; font-size: 0.75rem; margin-top: 2px; }
        .v-avail { color: #34d399; font-size: 0.72rem; margin-top: 3px; font-weight: 600; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        label { display: block; color: #94a3b8; font-size: 0.75rem; font-weight: 600; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.4px; margin-top: 14px; }
        input, select {
            width: 100%; padding: 12px 14px; background: #020617;
            border: 1px solid #334155; border-radius: 10px;
            color: white; font-size: 0.9rem; outline: none;
            font-family: inherit; transition: border 0.2s;
        }
        input:focus, select:focus { border-color: #3b82f6; }
        select option { background: #1e293b; }

        button {
            width: 100%; padding: 15px; background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none; border-radius: 12px; color: white;
            font-weight: 800; font-size: 1rem; cursor: pointer;
            transition: all 0.2s; font-family: inherit; margin-top: 18px;
        }
        button:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(59,130,246,0.4); }

        .error-box {
            background: rgba(127,29,29,0.4); border: 1px solid #991b1b;
            color: #fca5a5; padding: 12px 16px; border-radius: 10px;
            margin-bottom: 16px; font-size: 0.88rem;
        }
    </style>
</head>
<body>

<nav>
    <a href="dashboard.php" class="logo">🚗 SmartPark</a>
    <div class="nav-links">
        <a href="my_bookings.php">My Bookings</a>
        <a href="payments.php">Payment History</a>
        <a href="../logout.php" class="logout">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="welcome">
        <h2>Welcome, <span><?= htmlspecialchars($_SESSION['name']) ?></span> 👋</h2>
        <p>Choose your vehicle type and book a slot instantly.</p>
    </div>

    <div class="stats">
        <div class="card"><div class="icon">🅿️</div><h2><?= $avail ?></h2><p>Available Slots</p></div>
        <div class="card"><div class="icon">💰</div><h2>₹<?= number_format($total_spent,0) ?></h2><p>Total Spent</p></div>
        <div class="card green"><div class="icon">✅</div><h2><?= $active_count ?></h2><p>Active Bookings</p></div>
    </div>

    <?php if ($error): ?>
        <div class="error-box" style="max-width:600px;margin:0 auto 18px;">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="form-box">
        <h3>🚘 Book a Parking Slot</h3>

        <div class="vehicle-grid">
            <div class="v-card" onclick="selectVehicle('bike',this)">
                <div class="v-icon">🏍️</div>
                <div class="v-name">Bike</div>
                <div class="v-rate">₹20/hr</div>
                <div class="v-avail"><?= $avail_bike ?> free</div>
            </div>
            <div class="v-card selected" onclick="selectVehicle('car',this)">
                <div class="v-icon">🚗</div>
                <div class="v-name">Car</div>
                <div class="v-rate">₹50/hr</div>
                <div class="v-avail"><?= $avail_car ?> free</div>
            </div>
            <div class="v-card" onclick="selectVehicle('truck',this)">
                <div class="v-icon">🚚</div>
                <div class="v-name">Truck</div>
                <div class="v-rate">₹100/hr</div>
                <div class="v-avail"><?= $avail_truck ?> free</div>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="vehicle_type" id="vtype" value="car">

            <div class="form-grid">
                <div>
                    <label>Vehicle Number</label>
                    <input type="text" name="vehicle_number" placeholder="KA01AB1234" required>
                </div>
                <div>
                    <label>Duration</label>
                    <select name="duration" required>
                        <option value="1">1 Hour</option>
                        <option value="2">2 Hours</option>
                        <option value="3">3 Hours</option>
                        <option value="4">4 Hours</option>
                        <option value="6">6 Hours</option>
                        <option value="8">8 Hours</option>
                        <option value="12">12 Hours</option>
                        <option value="24">24 Hours</option>
                    </select>
                </div>
            </div>

            <label>Check-in Date & Time</label>
            <input type="datetime-local" name="check_in" id="checkin" required>

            <button type="submit">🅿️ PROCEED TO PAYMENT</button>
        </form>
    </div>
</div>

<script>
    // Auto-set check-in to current time
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('checkin').value = now.toISOString().slice(0, 16);

    function selectVehicle(type, el) {
        document.querySelectorAll('.v-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('vtype').value = type;
    }
</script>
</body>
</html>
