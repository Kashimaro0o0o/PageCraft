<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";

$username = $_SESSION['username'] ?? 'Admin';

$logs = $conn->query("
    SELECT visitor_logs.*, sites.site_name
    FROM visitor_logs
    LEFT JOIN sites ON visitor_logs.site_id = sites.id
    ORDER BY visitor_logs.visited_at DESC
    LIMIT 100
");

$totalLogs      = $conn->query("SELECT COUNT(*) as total FROM visitor_logs")->fetch_assoc()['total'] ?? 0;
$totalSites     = $conn->query("SELECT COUNT(DISTINCT site_id) as total FROM visitor_logs")->fetch_assoc()['total'] ?? 0;
$uniqueVisitors = $conn->query("SELECT COUNT(DISTINCT ip_address) as total FROM visitor_logs")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Logs | PageCraft</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f5f6fa; font-family: 'Segoe UI', sans-serif; }

        .navbar-brand {
            font-size: 22px; font-weight: 900;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .navbar { box-shadow: 0 2px 12px rgba(0,0,0,0.07); }

        .avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff; display: flex; align-items: center;
            justify-content: center; font-weight: 700;
        }

        .stat-card { border: none; border-radius: 18px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card.c1 { border-left: 4px solid #6c3afc; }
        .stat-card.c2 { border-left: 4px solid #e040fb; }
        .stat-card.c3 { border-left: 4px solid #ff6b6b; }

        .stat-value {
            font-size: 40px; font-weight: 800;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .table-card { border: none; border-radius: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); overflow: hidden; }
        .table thead th { background: #f8f9ff; color: #9ca3af; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border: none; }
        .table tbody tr:hover { background: #f8f9ff; }
        .table td { vertical-align: middle; border-color: #f0f2f8; font-size: 14px; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-light bg-white sticky-top px-4">
    <div class="d-flex align-items-center gap-3">
        <a href="dashboard.php" class="btn btn-light btn-sm fw-bold">← Back</a>
        <a class="navbar-brand mb-0" href="#">PageCraft</a>
    </div>
    <div class="avatar"><?php echo strtoupper($username[0]); ?></div>
</nav>

<div class="container py-4" style="max-width:1200px;">

    <h1 class="fw-bold fs-3 mb-1">📊 Visitor Logs</h1>
    <p class="text-muted small mb-4">Track who visited your websites</p>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card c1 p-4">
                <div class="text-muted small fw-bold text-uppercase">Total Visits</div>
                <div class="stat-value"><?php echo $totalLogs; ?></div>
                <div class="text-muted small">All time visits</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card c2 p-4">
                <div class="text-muted small fw-bold text-uppercase">Unique Visitors</div>
                <div class="stat-value"><?php echo $uniqueVisitors; ?></div>
                <div class="text-muted small">Different IP addresses</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card c3 p-4">
                <div class="text-muted small fw-bold text-uppercase">Sites Visited</div>
                <div class="stat-value"><?php echo $totalSites; ?></div>
                <div class="text-muted small">Across all your sites</div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card table-card">
        <div class="card-body p-0">
            <div class="p-4 pb-2 fw-bold fs-6">Recent Visits</div>
            <?php if ($logs && $logs->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Website</th>
                            <th>IP Address</th>
                            <th>Browser / Device</th>
                            <th>Visited At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($log = $logs->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4 text-muted"><?php echo $i++; ?></td>
                            <td>
                                <span class="badge rounded-pill" style="background:rgba(108,58,252,0.1);color:#6c3afc;font-size:12px;font-weight:700;padding:6px 12px;">
                                    🌐 <?php echo htmlspecialchars($log['site_name'] ?? 'Unknown'); ?>
                                </span>
                            </td>
                            <td>
                                <code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($log['ip_address']); ?></code>
                            </td>
                            <td class="text-muted" style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px;">
                                <?php echo htmlspecialchars(substr($log['user_agent'], 0, 80)); ?>...
                            </td>
                            <td class="text-muted small">
                                <?php echo date("M d, Y h:i A", strtotime($log['visited_at'])); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <div style="font-size:64px;">📊</div>
                <h5 class="fw-bold text-dark mt-3">No visits yet!</h5>
                <p>Share your website preview link to start tracking visitors</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
