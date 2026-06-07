<?php
session_start();
require 'config/db.php';

$error = "";

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);
    $query    = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

    if (!$query) {
        $error = "DB Error: " . mysqli_error($conn);
    } elseif (mysqli_num_rows($query) == 1) {
        $user = mysqli_fetch_assoc($query);
        if ($user['password'] === md5($password)) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit();
        } else {
            $error = "Wrong password!";
        }
    } else {
        $error = "Email not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | SmartPark</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#020617;min-height:100vh;display:flex;align-items:center;justify-content:center;}
.bg{position:fixed;inset:0;background:radial-gradient(ellipse at 20% 50%,rgba(59,130,246,0.15) 0%,transparent 60%),radial-gradient(ellipse at 80% 20%,rgba(99,102,241,0.1) 0%,transparent 50%);pointer-events:none}
.card{background:#0f172a;border:1px solid #1e293b;border-radius:20px;padding:44px 40px;width:100%;max-width:420px;box-shadow:0 0 80px rgba(59,130,246,0.12);position:relative;z-index:1;}
.logo{color:#3b82f6;font-size:2rem;font-weight:800;text-align:center;margin-bottom:4px;letter-spacing:-1px;}
.subtitle{color:#64748b;font-size:0.85rem;text-align:center;margin-bottom:30px;}
label{display:block;color:#94a3b8;font-size:0.78rem;font-weight:600;margin-bottom:6px;margin-top:18px;text-transform:uppercase;letter-spacing:0.5px;}
input{width:100%;padding:13px 16px;background:#1e293b;border:1px solid #334155;border-radius:10px;color:white;font-size:0.95rem;outline:none;transition:border 0.2s,box-shadow 0.2s;font-family:inherit;}
input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,0.15);}
.btn{width:100%;margin-top:26px;padding:14px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;font-weight:700;font-size:1rem;border:none;border-radius:10px;cursor:pointer;transition:all 0.2s;font-family:inherit;letter-spacing:0.3px;}
.btn:hover{transform:translateY(-1px);box-shadow:0 8px 25px rgba(59,130,246,0.4);}
.error{background:rgba(127,29,29,0.5);border:1px solid #991b1b;color:#fca5a5;padding:12px 16px;border-radius:10px;margin-top:16px;font-size:0.85rem;}
.link{text-align:center;margin-top:22px;color:#64748b;font-size:0.85rem;}
.link a{color:#3b82f6;text-decoration:none;font-weight:600;}
.link a:hover{text-decoration:underline;}
.admin-hint{background:#1e293b;border:1px solid #334155;border-radius:8px;padding:10px 14px;margin-top:16px;font-size:0.78rem;color:#64748b;text-align:center;}
.admin-hint b{color:#94a3b8;}
</style>
</head>
<body>
<div class="bg"></div>
<div class="card">
    <div class="logo">🚗 SmartPark</div>
    <div class="subtitle">Sign in to your account</div>

    <?php if ($error): ?>
        <div class="error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="your@email.com" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="Enter password" required>

        <button type="submit" class="btn">🔐 LOGIN</button>
    </form>

    <div class="admin-hint">
        Admin login: <b>admin@smartpark.com</b> / <b>admin123</b>
    </div>

    <div class="link">
        New user? <a href="register.php">Create account</a>
    </div>
</div>
</body>
</html>
