<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php"); exit();
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'config/db.php';
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // For now plain text password, we'll hash later
            if ($password === $user['password']) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                header("Location: pages/dashboard.php"); exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PageCraft</title>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            display: flex;
            font-family: 'Segoe UI', Arial, sans-serif;
            overflow: hidden;
        }

        /* ── Left Panel ── */
        .left-panel {
            width: 55%;
            background: linear-gradient(135deg, #6c3afc 0%, #e040fb 50%, #ff6b6b 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
            top: -100px; left: -100px;
        }

        .left-panel::after {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
            bottom: -80px; right: -80px;
        }

        .brand-logo {
            font-size: 52px;
            font-weight: 900;
            color: #fff;
            letter-spacing: -2px;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }

        .brand-logo span {
            background: rgba(255,255,255,0.25);
            padding: 0 8px;
            border-radius: 8px;
        }

        .brand-tagline {
            font-size: 20px;
            color: rgba(255,255,255,0.85);
            text-align: center;
            max-width: 380px;
            line-height: 1.6;
            position: relative;
            z-index: 1;
            margin-bottom: 40px;
        }

        .feature-list {
            list-style: none;
            position: relative;
            z-index: 1;
        }

        .feature-list li {
            color: rgba(255,255,255,0.9);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .feature-icon {
            width: 36px; height: 36px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        /* Floating shapes */
        .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.15;
            animation: floatShape ease-in-out infinite;
        }

        .shape-1 { width: 80px; height: 80px; background: #fff; top: 20%; left: 10%; animation-duration: 6s; }
        .shape-2 { width: 50px; height: 50px; background: #fff; top: 60%; left: 75%; animation-duration: 8s; animation-delay: 2s; }
        .shape-3 { width: 120px; height: 120px; background: #fff; top: 80%; left: 20%; animation-duration: 7s; animation-delay: 1s; }
        .shape-4 { width: 40px; height: 40px; background: #fff; top: 10%; left: 60%; animation-duration: 5s; animation-delay: 3s; }

        @keyframes floatShape {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        /* ── Right Panel ── */
        .right-panel {
            width: 45%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 50px;
        }

        .login-box { width: 100%; max-width: 380px; }

        .login-title {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 8px;
        }

        .login-sub {
            font-size: 15px;
            color: #667085;
            margin-bottom: 36px;
        }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 14px;
            font-size: 15px;
            outline: none;
            background: #f9fafb;
            color: #111827;
            transition: all 0.2s;
        }

        .form-group input:focus {
            border-color: #6c3afc;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(108,58,252,0.1);
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff;
            font-weight: 800;
            font-size: 16px;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            margin-top: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 20px rgba(108,58,252,0.35);
            letter-spacing: 0.3px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(108,58,252,0.45);
        }

        .login-btn:active { transform: translateY(0); }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border: 1px solid #fca5a5;
            color: #b91c1c;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: #9ca3af;
            font-size: 13px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .powered-by {
            text-align: center;
            margin-top: 32px;
            font-size: 13px;
            color: #9ca3af;
        }

        .powered-by span {
            font-weight: 700;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .left-panel { width: 100%; padding: 40px 24px; min-height: 300px; }
            .right-panel { width: 100%; padding: 40px 24px; }
            .brand-logo { font-size: 36px; }
        }
    </style>
</head>
<body>

<!-- Left Panel -->
<div class="left-panel">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    <div class="shape shape-4"></div>

    <div class="brand-logo">Page<span>Craft</span></div>
    <p class="brand-tagline">Build beautiful websites with ease. No coding required.</p>

    <ul class="feature-list">
        <li>
            <div class="feature-icon">🎨</div>
            Drag & drop website builder
        </li>
        <li>
            <div class="feature-icon">⚡</div>
            Lightning fast performance
        </li>
        <li>
            <div class="feature-icon">📱</div>
            Mobile responsive designs
        </li>
        <li>
            <div class="feature-icon">🚀</div>
            Publish in one click
        </li>
    </ul>
</div>

<!-- Right Panel -->
<div class="right-panel">
    <div class="login-box">
        <h1 class="login-title">Welcome back! 👋</h1>
        <p class="login-sub">Sign in to manage your websites</p>

        <?php if ($error): ?>
            <div class="alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter your username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="login-btn">Sign In to PageCraft →</button>
        </form>

        <div class="divider">PageCraft Website Builder</div>

        <div class="powered-by">
            Powered by <span>PageCraft CMS</span>
        </div>
    </div>
</div>

</body>
</html>