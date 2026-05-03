<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";
$username = $_SESSION['username'] ?? 'Admin';

$sites = $conn->query("SELECT * FROM sites WHERE user_id = " . $_SESSION['user_id'] . " ORDER BY created_at DESC");
$totalSites = $sites ? $sites->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PageCraft</title>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f6fa;
            color: #1a1a2e;
        }

        /* ── TOPBAR ── */
        .topbar {
            height: 65px;
            background: #fff;
            border-bottom: 1px solid #e9edf5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-brand {
            font-size: 24px;
            font-weight: 900;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }

        .topbar-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
        }

        .logout-btn {
            padding: 8px 18px;
            background: #fee2e2;
            color: #b91c1c;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: #fecaca;
            transform: translateY(-1px);
        }

        /* ── MAIN ── */
        .main { padding: 36px 32px; max-width: 1200px; margin: 0 auto; }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
        }

        .page-title { font-size: 32px; font-weight: 800; color: #1a1a2e; }
        .page-sub { font-size: 14px; color: #667085; margin-top: 4px; }

        .create-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 24px;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(108,58,252,0.3);
            transition: all 0.2s;
        }

        .create-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(108,58,252,0.4);
            color: #fff;
        }

        /* ── STATS ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: #fff;
            border-radius: 18px;
            padding: 22px 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }

        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(0,0,0,0.1); }
        .stat-card:nth-child(1) { border-left-color: #6c3afc; }
        .stat-card:nth-child(2) { border-left-color: #e040fb; }
        .stat-card:nth-child(3) { border-left-color: #ff6b6b; }

        .stat-label { font-size: 13px; font-weight: 700; color: #667085; text-transform: uppercase; letter-spacing: 0.5px; }

        .stat-value {
            font-size: 42px;
            font-weight: 800;
            margin: 8px 0 4px;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-sub { font-size: 13px; color: #9ca3af; }

        /* ── SITES GRID ── */
        .section-title {
            font-size: 20px;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 18px;
        }

        .sites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .site-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            transition: all 0.3s;
            border: 1px solid #f0f0f0;
        }

        .site-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(108,58,252,0.15);
            border-color: #c4b5fd;
        }

        .site-card-preview {
            height: 140px;
            background: linear-gradient(135deg, #6c3afc 0%, #e040fb 50%, #ff6b6b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
        }

        .site-card-body { padding: 18px; }

        .site-card-name {
            font-size: 17px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 6px;
        }

        .site-card-date {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 14px;
        }

        .site-card-actions {
            display: flex;
            gap: 8px;
        }

        .btn-edit {
            flex: 1;
            text-align: center;
            padding: 9px;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-view {
            flex: 1;
            text-align: center;
            padding: 9px;
            background: #f3f4f6;
            color: #374151;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-delete {
            padding: 9px 12px;
            background: #fee2e2;
            color: #b91c1c;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-edit:hover { opacity: 0.9; transform: translateY(-1px); color: #fff; }
        .btn-view:hover { background: #e9edf5; transform: translateY(-1px); }
        .btn-delete:hover { background: #fecaca; transform: translateY(-1px); }

        /* ── CREATE MODAL ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active { display: flex; }

        .modal {
            background: #fff;
            border-radius: 24px;
            padding: 36px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.2);
        }

        .modal-title { font-size: 24px; font-weight: 800; margin-bottom: 6px; }
        .modal-sub { font-size: 14px; color: #667085; margin-bottom: 24px; }

        .modal input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            outline: none;
            margin-bottom: 16px;
            transition: all 0.2s;
        }

        .modal input:focus {
            border-color: #6c3afc;
            box-shadow: 0 0 0 4px rgba(108,58,252,0.1);
        }

        .modal-btns { display: flex; gap: 10px; }

        .modal-submit {
            flex: 1;
            padding: 13px;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .modal-cancel {
            padding: 13px 20px;
            background: #f3f4f6;
            color: #374151;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .empty-state .empty-icon { font-size: 64px; margin-bottom: 16px; }
        .empty-state h3 { font-size: 20px; font-weight: 700; color: #374151; margin-bottom: 8px; }
        .empty-state p { font-size: 14px; margin-bottom: 24px; }
    </style>
</head>
<body>

<!-- Topbar -->
<header class="topbar">
    <div class="topbar-brand">PageCraft</div>
    <div class="topbar-right">
        <div class="topbar-user">
            <div class="topbar-avatar"><?php echo strtoupper($username[0]); ?></div>
            <span><?php echo htmlspecialchars($username); ?></span>
        </div>
        <a href="../logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</header>

<!-- Main -->
<main class="main">
    <div class="page-header">
        <div>
            <h1 class="page-title">My Websites 🌐</h1>
            <p class="page-sub">Create and manage your websites</p>
        </div>
        <button class="create-btn" onclick="document.getElementById('createModal').classList.add('active')">
            + Create New Website
        </button>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Total Websites</div>
            <div class="stat-value"><?php echo $totalSites; ?></div>
            <div class="stat-sub">All your sites</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Sections Added</div>
            <div class="stat-value">
                <?php
                $sec = $conn->query("SELECT COUNT(*) as total FROM sections WHERE is_archived = 0");
                echo $sec ? $sec->fetch_assoc()['total'] : 0;
                ?>
            </div>
            <div class="stat-sub">Active content blocks</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pages Created</div>
            <div class="stat-value">
                <?php
                $pg = $conn->query("SELECT COUNT(*) as total FROM pages");
                echo $pg ? $pg->fetch_assoc()['total'] : 0;
                ?>
            </div>
            <div class="stat-sub">Across all sites</div>
        </div>
    </div>

    <!-- Sites Grid -->
    <div class="section-title">Your Websites</div>

    <?php
    // Re-query sites for display
    $sites = $conn->query("SELECT * FROM sites WHERE user_id = " . $_SESSION['user_id'] . " ORDER BY created_at DESC");
    ?>

    <?php if ($sites && $sites->num_rows > 0): ?>
    <div class="sites-grid">
        <?php while ($site = $sites->fetch_assoc()): ?>
        <div class="site-card">
            <div class="site-card-preview">🌐</div>
            <div class="site-card-body">
                <div class="site-card-name"><?php echo htmlspecialchars($site['site_name']); ?></div>
                <div class="site-card-date">Created: <?php echo date("M d, Y", strtotime($site['created_at'])); ?></div>
                <div class="site-card-actions">
                    <a href="editor.php?site_id=<?php echo $site['id']; ?>" class="btn-edit">✏️ Edit</a>
                    <a href="view.php?site_id=<?php echo $site['id']; ?>" class="btn-view">👁️ View</a>
                    <a href="../actions/delete_site.php?id=<?php echo $site['id']; ?>"
                       class="btn-delete"
                       onclick="return confirm('Delete this website?');">🗑️</a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">🌐</div>
        <h3>No websites yet!</h3>
        <p>Create your first website to get started</p>
        <button class="create-btn" onclick="document.getElementById('createModal').classList.add('active')">
            + Create New Website
        </button>
    </div>
    <?php endif; ?>
</main>

<!-- Create Modal -->
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <h2 class="modal-title">🌐 Create New Website</h2>
        <p class="modal-sub">Give your website a name to get started!</p>
        <form action="../actions/create_site.php" method="POST">
            <input type="text" name="site_name" placeholder="e.g. My Portfolio, Business Site..." required>
            <div class="modal-btns">
                <button type="submit" class="modal-submit">Create Website →</button>
                <button type="button" class="modal-cancel" onclick="document.getElementById('createModal').classList.remove('active')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Close modal on overlay click
document.getElementById('createModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
});
</script>
</body>
</html>