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

$totalLogs = $conn->query("SELECT COUNT(*) as total FROM visitor_logs")->fetch_assoc()['total'] ?? 0;
$totalSites = $conn->query("SELECT COUNT(DISTINCT site_id) as total FROM visitor_logs")->fetch_assoc()['total'] ?? 0;
$uniqueVisitors = $conn->query("SELECT COUNT(DISTINCT ip_address) as total FROM visitor_logs")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Logs | PageCraft</title>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f6fa; color: #1a1a2e; }

        .topbar {
            height: 65px; background: #fff;
            border-bottom: 1px solid #e9edf5;
            display: flex; align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            position: sticky; top: 0; z-index: 100;
        }

        .topbar-left { display: flex; align-items: center; gap: 16px; }

        .topbar-brand {
            font-size: 22px; font-weight: 900;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .back-btn {
            padding: 8px 14px; background: #f3f4f6;
            color: #374151; border-radius: 10px;
            font-size: 13px; font-weight: 700;
            text-decoration: none; transition: all 0.2s;
        }

        .back-btn:hover { background: #e9edf5; }

        .topbar-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff; display: flex; align-items: center;
            justify-content: center; font-weight: 700;
        }

        .main { padding: 36px 32px; max-width: 1200px; margin: 0 auto; }

        .page-title { font-size: 32px; font-weight: 800; margin-bottom: 4px; }
        .page-sub { font-size: 14px; color: #667085; margin-bottom: 28px; }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px; margin-bottom: 28px;
        }

        .stat-card {
            background: #fff; border-radius: 18px;
            padding: 22px 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }

        .stat-card:hover { transform: translateY(-3px); }
        .stat-card:nth-child(1) { border-left-color: #6c3afc; }
        .stat-card:nth-child(2) { border-left-color: #e040fb; }
        .stat-card:nth-child(3) { border-left-color: #ff6b6b; }

        .stat-label { font-size: 13px; font-weight: 700; color: #667085; text-transform: uppercase; letter-spacing: 0.5px; }

        .stat-value {
            font-size: 42px; font-weight: 800; margin: 8px 0 4px;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-sub { font-size: 13px; color: #9ca3af; }

        .content-card {
            background: #fff; border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        }

        .card-title { font-size: 18px; font-weight: 800; margin-bottom: 18px; }

        table { width: 100%; border-collapse: collapse; }

        th, td {
            text-align: left; padding: 13px 14px;
            border-bottom: 1px solid #f0f2f8;
            font-size: 14px;
        }

        th { color: #9ca3af; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }

        tbody tr:hover { background: #f8f9ff; }
        tbody tr:last-child td { border-bottom: none; }

        .site-badge {
            display: inline-block;
            background: linear-gradient(135deg, rgba(108,58,252,0.1), rgba(224,64,251,0.1));
            color: #6c3afc;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .ip-badge {
            display: inline-block;
            background: #f3f4f6;
            color: #374151;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            font-family: monospace;
        }

        .empty-state {
            text-align: center; padding: 60px 20px; color: #9ca3af;
        }

        .empty-icon { font-size: 64px; margin-bottom: 16px; }
        .empty-state h3 { font-size: 20px; font-weight: 700; color: #374151; margin-bottom: 8px; }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-left">
        <a href="dashboard.php" class="back-btn">← Back</a>
        <div class="topbar-brand">PageCraft</div>
    </div>
    <div class="topbar-avatar"><?php echo strtoupper($username[0]); ?></div>
</header>

<main class="main">
    <h1 class="page-title">📊 Visitor Logs</h1>
    <p class="page-sub">Track who visited your websites</p>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Total Visits</div>
            <div class="stat-value"><?php echo $totalLogs; ?></div>
            <div class="stat-sub">All time visits</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Unique Visitors</div>
            <div class="stat-value"><?php echo $uniqueVisitors; ?></div>
            <div class="stat-sub">Different IP addresses</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Sites Visited</div>
            <div class="stat-value"><?php echo $totalSites; ?></div>
            <div class="stat-sub">Across all your sites</div>
        </div>
    </div>

    <div class="content-card">
        <div class="card-title">Recent Visits</div>
        <?php if ($logs && $logs->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Website</th>
                    <th>IP Address</th>
                    <th>Browser/Device</th>
                    <th>Visited At</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($log = $logs->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><span class="site-badge">🌐 <?php echo htmlspecialchars($log['site_name'] ?? 'Unknown'); ?></span></td>
                    <td><span class="ip-badge"><?php echo htmlspecialchars($log['ip_address']); ?></span></td>
                    <td style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#667085; font-size:13px;">
                        <?php echo htmlspecialchars(substr($log['user_agent'], 0, 80)); ?>...
                    </td>
                    <td style="color:#667085; font-size:13px;">
                        <?php echo date("M d, Y h:i A", strtotime($log['visited_at'])); ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📊</div>
            <h3>No visits yet!</h3>
            <p>Share your website preview link to start tracking visitors</p>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>