<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$msg = "";
if (isset($_GET['toggle'])) {
    $sid = (int)$_GET['toggle'];
    $cur = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM parking_slots WHERE slot_id='$sid'"));
    $new = ($cur['status'] === 'available') ? 'occupied' : 'available';
    mysqli_query($conn, "UPDATE parking_slots SET status='$new' WHERE slot_id='$sid'");
    header("Location: slots.php");
    exit();
}

$slots = mysqli_query($conn, "SELECT * FROM parking_slots ORDER BY vehicle_type, slot_number");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Slots | SmartPark Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#020617;color:white;min-height:100vh}
nav{background:#0f172a;border-bottom:1px solid #1e293b;padding:0 32px;display:flex;align-items:center;justify-content:space-between;height:64px;position:sticky;top:0;z-index:100}
.nav-logo{color:#f59e0b;font-size:1.4rem;font-weight:800}
.nav-links{display:flex;gap:4px}
.nav-links a{color:#64748b;text-decoration:none;padding:8px 14px;border-radius:8px;font-size:0.85rem;font-weight:500;transition:all 0.2s}
.nav-links a:hover,.nav-links a.active{background:#1e293b;color:white}
.logout-btn{background:#1e293b;border:1px solid #334155;color:#f87171;padding:8px 16px;border-radius:8px;text-decoration:none;font-size:0.82rem;font-weight:600}
.main{max-width:1200px;margin:0 auto;padding:32px 24px}
h1{font-size:1.8rem;font-weight:800;margin-bottom:8px}
h1 span{color:#f59e0b}
.sub{color:#64748b;font-size:0.9rem;margin-bottom:28px}
.type-section{margin-bottom:32px}
.type-title{font-size:1rem;font-weight:700;color:white;margin-bottom:16px}
.slots-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:12px}
.slot-card{background:#0f172a;border:2px solid;border-radius:14px;padding:18px 12px;text-align:center;cursor:pointer;transition:all 0.2s;text-decoration:none;display:block}
.slot-card.available{border-color:#059669}
.slot-card.available:hover{background:#064e3b}
.slot-card.occupied{border-color:#991b1b;background:#1a0a0a}
.slot-card.occupied:hover{background:#7f1d1d}
.slot-icon{font-size:1.8rem;margin-bottom:8px}
.slot-num{font-weight:700;font-size:0.95rem;color:white}
.slot-status{font-size:0.72rem;margin-top:4px;font-weight:600}
.available .slot-status{color:#34d399}
.occupied .slot-status{color:#f87171}
</style>
</head>
<body>
<nav>
    <div class="nav-logo">🚗 SmartPark <span style="color:#f59e0b;font-size:0.9rem">ADMIN</span></div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="slots.php" class="active">Manage Slots</a>
    </div>
    <a href="../logout.php" class="logout-btn">Logout</a>
</nav>

<div class="main">
    <h1>🅿️ Parking <span>Slots</span></h1>
    <p class="sub">Click any slot to toggle between available / occupied.</p>

    <?php
    $current_type = "";
    mysqli_data_seek($slots, 0);
    $slot_data = [];
    while($s = mysqli_fetch_assoc($slots)) {
        $slot_data[$s['vehicle_type']][] = $s;
    }
    $icons = ['bike'=>'🏍️','car'=>'🚗','truck'=>'🚚'];
    foreach($slot_data as $type => $slots_arr):
    ?>
    <div class="type-section">
        <div class="type-title"><?= $icons[$type] ?? '🅿️' ?> <?= ucfirst($type) ?> Slots</div>
        <div class="slots-grid">
            <?php foreach($slots_arr as $s): ?>
            <a href="?toggle=<?= $s['slot_id'] ?>" class="slot-card <?= $s['status'] ?>" onclick="return confirm('Toggle slot <?= $s['slot_number'] ?>?')">
                <div class="slot-icon"><?= $icons[$type] ?></div>
                <div class="slot-num"><?= $s['slot_number'] ?></div>
                <div class="slot-status"><?= strtoupper($s['status']) ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
</body>
</html>
