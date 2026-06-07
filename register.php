<?php
session_start();
require 'config/db.php';

$error = "";

if (isset($_SESSION['user_id'])) {
    header("Location: user/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email   = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone   = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email already exists. Please login.";
        } else {
            $hashed = md5($password);
            $insert = mysqli_query($conn, "
                INSERT INTO users (name,email,phone,password,role)
                VALUES ('$name','$email','$phone','$hashed','user')
            ");
            if ($insert) {
                $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE email='$email'"));
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = $user['role'];
                header("Location: user/dashboard.php");
                exit();
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | SmartPark</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#020617;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.bg{position:fixed;inset:0;background:radial-gradient(ellipse at 80% 50%,rgba(59,130,246,0.15) 0%,transparent 60%);pointer-events:none}
.card{background:#0f172a;border:1px solid #1e293b;border-radius:20px;padding:44px 40px;width:100%;max-width:440px;box-shadow:0 0 80px rgba(59,130,246,0.12);position:relative;z-index:1;}
.logo{color:#3b82f6;font-size:1.9rem;font-weight:800;text-align:center;margin-bottom:4px;letter-spacing:-1px;}
.subtitle{color:#64748b;font-size:0.85rem;text-align:center;margin-bottom:10px;}
.note{background:#0f2a4a;border:1px solid #1e4a7a;border-radius:10px;padding:10px 14px;font-size:0.82rem;color:#7dd3fc;text-align:center;margin-bottom:10px;}
label{display:block;color:#94a3b8;font-size:0.78rem;font-weight:600;margin-bottom:6px;margin-top:16px;text-transform:uppercase;letter-spacing:0.5px;}
input{width:100%;padding:13px 16px;background:#1e293b;border:1px solid #334155;border-radius:10px;color:white;font-size:0.95rem;outline:none;transition:border 0.2s,box-shadow 0.2s;font-family:inherit;}
input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,0.15);}
.btn{width:100%;margin-top:26px;padding:14px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;font-weight:700;font-size:1rem;border:none;border-radius:10px;cursor:pointer;transition:all 0.2s;font-family:inherit;}
.btn:hover{transform:translateY(-1px);box-shadow:0 8px 25px rgba(59,130,246,0.4);}
.error{background:rgba(127,29,29,0.5);border:1px solid #991b1b;color:#fca5a5;padding:12px 16px;border-radius:10px;margin-top:16px;font-size:0.85rem;}
.link{text-align:center;margin-top:22px;color:#64748b;font-size:0.85rem;}
.link a{color:#3b82f6;text-decoration:none;font-weight:600;}
</style>
</head>
<body>
<div class="bg"></div>
<div class="card">
    <div class="logo">🚗 SmartPark</div>
    <div class="subtitle">Create your account</div>
    <div class="note">👋 Fill in your details — you'll be logged in automatically!</div>

    <?php if ($error): ?>
        <div class="error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Full Name</label>
        <input type="text" name="name" placeholder="John Doe" required>

        <label>Email Address</label>
        <input type="email" name="email" placeholder="john@email.com" required>

        <label>Phone Number</label>
        <input type="text" name="phone" placeholder="9876543210">

        <label>Password</label>
        <input type="password" name="password" placeholder="Min 6 characters" required>

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" placeholder="Re-enter password" required>

        <button type="submit" class="btn">✅ CREATE ACCOUNT & LOGIN</button>
    </form>

    <div class="link">Already have an account? <a href="login.php">Login here</a></div>
</div>
</body>
</html>
