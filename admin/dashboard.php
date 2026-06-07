<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Stats
$total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='user'"))['c'];
$total_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings"))['c'];
$active_now     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE status='active'"))['c'];
$total_revenue  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as t FROM bookings WHERE payment_status='paid'"))['t'] ?? 0;
$pending_pay    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE payment_status='pending' AND status='active'"))['c'];

$slots_free     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM parking_slots WHERE status='available'"))['c'];
$slots_total    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM parking_slots"))['c'];

// Recent bookings
$recent = mysqli_query($conn, "SELECT b.*, u.name, u.phone, s.slot_number FROM bookings b JOIN users u ON b.user_id=u.user_id JOIN parking_slots s ON b.slot_id=s.slot_id ORDER BY b.created_at DESC LIMIT 15");

// All users
$users = mysqli_query($conn, "SELECT * FROM users WHERE role='user' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin | SmartPark</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#020617;color:white;min-height:100vh}
nav{background:#0f172a;border-bottom:1px solid #1e293b;padding:0 32px;display:flex;align-items:center;justify-content:space-between;height:64px;position:sticky;top:0;z-index:100}
.nav-logo{color:#f59e0b;font-size:1.4rem;font-weight:800}
.nav-links{display:flex;gap:4px}
.nav-links a{color:#64748b;text-decoration:none;padding:8px 14px;border-radius:8px;font-size:0.85rem;font-weight:500;transition:all 0.2s}
.nav-links a:hover,.nav-links a.active{background:#1e293b;color:white}
.admin-badge{background:#451a03;color:#fbbf24;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:700;border:1px solid #78350f}
.logout-btn{background:#1e293b;border:1px solid #334155;color:#f87171;padding:8px 16px;border-radius:8px;text-decoration:none;font-size:0.82rem;font-weight:600}
.main{max-width:1300px;margin:0 auto;padding:32px 24px}
.page-title{font-size:1.8rem;font-weight:800;margin-bottom:4px}
.page-title span{color:#f59e0b}
.sub{color:#64748b;font-size:0.9rem;margin-bottom:28px}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:32px}
.stat-card{background:#0f172a;border:1px solid #1e293b;border-radius:16px;padding:22px;transition:border-color 0.2s}
.stat-card:hover{border-color:#f59e0b}
.stat-icon{font-size:1.8rem;margin-bottom:10px}
.stat-val{font-size:1.9rem;font-weight:800;color:white}
.stat-label{color:#64748b;font-size:0.8rem;margin-top:4px}
.stat-card.revenue .stat-val{color:#34d399}
.stat-card.alert .stat-val{color:#fb923c}
.section-title{font-size:1rem;font-weight:700;color:white;margin-bottom:16px;display:flex;align-items:center;gap:8px;margin-top:32px}
/* Slots grid */
.slots-overview{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:32px}
.slot-type-card{background:#0f172a;border:1px solid #1e293b;border-radius:16px;padding:24px;text-align:center}
.slot-type-card h3{color:#94a3b8;font-size:0.85rem;font-weight:600;margin-bottom:12px;text-transform:uppercase}
.slot-bar{background:#1e293b;height:8px;border-radius:4px;overflow:hidden;margin:12px 0}
.slot-bar-fill{height:100%;background:linear-gradient(135deg,#3b82f6,#059669);border-radius:4px;transition:width 0.5s}
.slot-nums{display:flex;justify-content:space-between;font-size:0.78rem;color:#64748b}
/* Table */
.table-wrap{background:#0f172a;border:1px solid #1e293b;border-radius:16px;overflow:hidden;margin-bottom:32px}
.table-header{padding:16px 20px;border-bottom:1px solid #1e293b;display:flex;align-items:center;justify-content:space-between}
.table-header h3{font-size:0.95rem;font-weight:700;color:white}
table{width:100%;border-collapse:collapse}
th{background:#1e293b;color:#64748b;font-size:0.73rem;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;padding:12px 16px;text-align:left}
td{padding:13px 16px;border-bottom:1px solid #1e293b;color:#cbd5e1;font-size:0.85rem}
tr:last-child td{border-bottom:none}
tr:hover td{background:#1e293b55}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:600}
.badge-active{background:#064e3b;color:#34d399}
.badge-completed{background:#1e293b;color:#94a3b8}
.badge-pending{background:#431407;color:#fb923c}
.badge-paid{background:#064e3b;color:#34d399}
.badge-cancelled{background:#7f1d1d;color:#fca5a5}
.tabs{display:flex;gap:4px;margin-bottom:20px;background:#0f172a;border:1px solid #1e293b;border-radius:12px;padding:4px;width:fit-content}
.tab{padding:8px 20px;border-radius:8px;cursor:pointer;font-size:0.85rem;font-weight:600;color:#64748b;transition:all 0.2s}
.tab.active{background:#1e293b;color:white}
.tab-content{display:none}
.tab-content.active{display:block}
</style>
</head>
<body>

<nav>
    <div class="nav-logo">🚗 SmartPark <span style="color:#f59e0b;font-size:0.9rem">ADMIN</span></div>
    <div class="nav-links">
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="slots.php">Manage Slots</a>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
        <span class="admin-badge">👑 ADMIN</span>
        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<div class="main">
    <div class="page-title">Admin <span>Dashboard</span></div>
    <p class="sub">Full overview of SmartPark system.</p>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-val"><?= $total_users ?></div><div class="stat-label">Registered Users</div></div>
        <div class="stat-card"><div class="stat-icon">📋</div><div class="stat-val"><?= $total_bookings ?></div><div class="stat-label">Total Bookings</div></div>
        <div class="stat-card"><div class="stat-icon">🟢</div><div class="stat-val"><?= $active_now ?></div><div class="stat-label">Currently Parked</div></div>
        <div class="stat-card revenue"><div class="stat-icon">💰</div><div class="stat-val">₹<?= number_format($total_revenue,0) ?></div><div class="stat-label">Revenue Collected</div></div>
        <div class="stat-card alert"><div class="stat-icon">⏳</div><div class="stat-val"><?= $pending_pay ?></div><div class="stat-label">Pending Payments</div></div>
        <div class="stat-card"><div class="stat-icon">🅿️</div><div class="stat-val"><?= $slots_free ?>/<?= $slots_total ?></div><div class="stat-label">Slots Free</div></div>
    </div>

    <!-- Slot Overview -->
    <?php
    $types = ['bike'=>['🏍️','Bikes'],'car'=>['🚗','Cars'],'truck'=>['🚚','Trucks']];
    echo '<div class="slots-overview">';
    foreach ($types as $t => $info) {
        $free = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM parking_slots WHERE vehicle_type='$t' AND status='available'"))['c'];
        $total_t = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM parking_slots WHERE vehicle_type='$t'"))['c'];
        $pct = $total_t > 0 ? round(($free/$total_t)*100) : 0;
        echo "<div class='slot-type-card'>
            <h3>{$info[0]} {$info[1]}</h3>
            <div style='font-size:1.6rem;font-weight:800'>$free <span style='color:#64748b;font-size:1rem'>/ $total_t</span></div>
            <div class='slot-bar'><div class='slot-bar-fill' style='width:{$pct}%'></div></div>
            <div class='slot-nums'><span>0</span><span>Available</span><span>$total_t</span></div>
        </div>";
    }
    echo '</div>';
    ?>

    <!-- Tabs -->
    <div class="tabs">
        <div class="tab active" onclick="switchTab('bookings',this)">📋 Bookings</div>
        <div class="tab" onclick="switchTab('users',this)">👥 Users</div>
    </div>

    <!-- Bookings Tab -->
    <div id="tab-bookings" class="tab-content active">
        <div class="table-wrap">
            <div class="table-header"><h3>Recent Bookings</h3></div>
            <table>
                <tr>
                    <th>#</th><th>User</th><th>Phone</th><th>Slot</th><th>Vehicle</th><th>Check-in</th><th>Check-out</th><th>Amount</th><th>Payment</th><th>Status</th>
                </tr>
                <?php $i=1; while($b=mysqli_fetch_assoc($recent)): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($b['name']) ?></td>
                    <td><?= $b['phone'] ?></td>
                    <td><?= $b['slot_number'] ?></td>
                    <td><?= strtoupper($b['vehicle_type']) ?> <?= $b['vehicle_number'] ?></td>
                    <td><?= date('d M, h:i A', strtotime($b['check_in'])) ?></td>
                    <td><?= date('d M, h:i A', strtotime($b['check_out'])) ?></td>
                    <td>₹<?= $b['amount'] ?></td>
                    <td><span class="badge badge-<?= $b['payment_status'] ?>"><?= ucfirst($b['payment_status']) ?></span></td>
                    <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <!-- Users Tab -->
    <div id="tab-users" class="tab-content">
        <div class="table-wrap">
            <div class="table-header"><h3>Registered Users</h3></div>
            <table>
                <tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Registered</th><th>Bookings</th></tr>
                <?php $i=1; while($u=mysqli_fetch_assoc($users)):
                    $ub = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE user_id='{$u['user_id']}'"))['c'];
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= $u['email'] ?></td>
                    <td><?= $u['phone'] ?></td>
                    <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td><?= $ub ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</div>

<script>
function switchTab(id, el) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('tab-' + id).classList.add('active');
}
</script>
</body>
</html>
