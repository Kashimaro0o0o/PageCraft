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
            if ($password === $user['password']) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; background: #f5f6fa; }

        .left-panel {
            background: linear-gradient(135deg, #6c3afc 0%, #e040fb 50%, #ff6b6b 100%);
            min-height: 100vh;
        }

        .brand-logo {
            font-size: 48px;
            font-weight: 900;
            color: #fff;
            letter-spacing: -2px;
        }

        .brand-logo span {
            background: rgba(255,255,255,0.25);
            padding: 0 8px;
            border-radius: 8px;
        }

        .feature-icon {
            width: 40px; height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
        }

        .btn-login {
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            padding: 13px;
            box-shadow: 0 4px 20px rgba(108,58,252,0.35);
            transition: all 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(108,58,252,0.45);
        }

        .form-control {
            border-radius: 12px;
            padding: 13px 16px;
            border: 2px solid #e5e7eb;
            background: #f9fafb;
            font-size: 15px;
        }

        .form-control:focus {
            border-color: #6c3afc;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(108,58,252,0.1);
        }

        .form-label {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #374151;
        }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0 min-vh-100">

        <!-- Left Panel -->
        <div class="col-lg-6 left-panel d-flex flex-column align-items-center justify-content-center p-5">
            <div class="text-center mb-4">
                <div class="brand-logo mb-3">Page<span>Craft</span></div>
                <p class="text-white opacity-75 fs-5">Build beautiful websites with ease.<br>No coding required.</p>
            </div>
            <ul class="list-unstyled mt-3">
                <li class="d-flex align-items-center gap-3 mb-3 text-white fw-semibold">
                    <div class="feature-icon">🎨</div> Drag &amp; drop website builder
                </li>
                <li class="d-flex align-items-center gap-3 mb-3 text-white fw-semibold">
                    <div class="feature-icon">⚡</div> Lightning fast performance
                </li>
                <li class="d-flex align-items-center gap-3 mb-3 text-white fw-semibold">
                    <div class="feature-icon">📱</div> Mobile responsive designs
                </li>
                <li class="d-flex align-items-center gap-3 text-white fw-semibold">
                    <div class="feature-icon">🚀</div> Publish in one click
                </li>
            </ul>
        </div>

        <!-- Right Panel -->
        <div class="col-lg-6 d-flex align-items-center justify-content-center p-4 bg-white">
            <div class="w-100" style="max-width: 400px;">
                <h1 class="fw-bold fs-2 mb-1">Welcome back! 👋</h1>
                <p class="text-muted mb-4">Sign in to manage your websites</p>

                <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Enter your username" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                    <button type="submit" class="btn btn-login btn-primary w-100 text-white">
                        Sign In to PageCraft →
                    </button>
                </form>

                <hr class="my-4">
                <p class="text-center text-muted small">Powered by <strong class="text-primary">PageCraft CMS</strong></p>
            </div>
        </div>

    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
