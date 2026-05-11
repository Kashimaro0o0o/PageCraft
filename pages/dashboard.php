<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";
$username = $_SESSION['username'] ?? 'Admin';

// Ensure is_archived column exists in sites table
$colCheck = $conn->query("SHOW COLUMNS FROM sites LIKE 'is_archived'");
if ($colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE sites ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
}

$showArchived = isset($_GET['view']) && $_GET['view'] === 'archived';
$archiveFilter = $showArchived ? "AND (is_archived = 1)" : "AND (is_archived = 0 OR is_archived IS NULL)";

$sites = $conn->query("SELECT * FROM sites WHERE user_id = " . $_SESSION['user_id'] . " $archiveFilter ORDER BY created_at DESC");
$totalSites = $sites ? $sites->num_rows : 0;

$archivedCount = $conn->query("SELECT COUNT(*) as c FROM sites WHERE user_id = " . $_SESSION['user_id'] . " AND is_archived = 1")->fetch_assoc()['c'] ?? 0;

// Pre-load first section per site for thumbnail generation
$sitePreviews = [];
$allSites = $conn->query("SELECT * FROM sites WHERE user_id = " . $_SESSION['user_id'] . " $archiveFilter ORDER BY created_at DESC");
if ($allSites) {
    while ($s = $allSites->fetch_assoc()) {
        $page = $conn->query("SELECT id FROM pages WHERE site_id = " . $s['id'])->fetch_assoc();
        $pid = $page ? $page['id'] : 0;
        $secs = [];
        if ($pid) {
            $secRes = $conn->query("SELECT * FROM sections WHERE page_id = $pid AND is_archived = 0 ORDER BY position ASC LIMIT 4");
            if ($secRes) {
                while ($sec = $secRes->fetch_assoc()) {
                    $secs[] = $sec;
                }
            }
        }
        $sitePreviews[$s['id']] = [
            'site' => $s,
            'sections' => $secs
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PageCraft</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;900&family=Roboto:wght@300;400;700&family=Playfair+Display:wght@400;700;900&family=Montserrat:wght@300;400;600;700;900&family=Lato:wght@300;400;700&family=Open+Sans:wght@300;400;600;700&family=Raleway:wght@300;400;600;700;900&family=Merriweather:wght@300;400;700&family=Nunito:wght@300;400;600;700;900&family=Inter:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500;700&family=Space+Grotesk:wght@300;400;500;600;700&family=Bebas+Neue&family=Oswald:wght@300;400;600;700&family=Dancing+Script:wght@400;600;700&family=Pacifico&family=Lobster&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f6fa;
            color: #1a1a2e;
        }

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

        .topbar-right { display: flex; align-items: center; gap: 16px; }

        .topbar-user { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; color: #374151; }

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

        .logout-btn:hover { background: #fecaca; transform: translateY(-1px); }

        .main { padding: 36px 32px; max-width: 1200px; margin: 0 auto; }

        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; }

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

        .create-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(108,58,252,0.4); color: #fff; }

        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-bottom: 32px; }

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

        .section-title { font-size: 20px; font-weight: 800; color: #1a1a2e; margin-bottom: 18px; }

        .sites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 22px;
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

        /* ─── THUMBNAIL PREVIEW ─── */
        .site-card-preview {
            height: 160px;
            position: relative;
            overflow: hidden;
            background: #f5f5f8;
        }

        .site-thumbnail {
            width: 100%;
            height: 100%;
            transform-origin: top left;
            pointer-events: none;
        }

        .thumbnail-frame {
            position: absolute;
            inset: 0;
            overflow: hidden;
        }

        /* Scaled-down rendering of the site sections */
        .thumb-scale-wrap {
            width: 900px;
            transform: scale(0.333);
            transform-origin: top left;
            height: 480px;
            overflow: hidden;
        }

        .thumb-hero {
            background: linear-gradient(135deg, #6c3afc 0%, #e040fb 50%, #ff6b6b 100%);
            color: #fff;
            padding: 36px 28px;
            text-align: center;
        }

        .thumb-hero h2 { font-size: 32px; font-weight: 800; margin-bottom: 8px; }
        .thumb-hero p  { font-size: 14px; opacity: 0.85; }

        .thumb-header {
            padding: 18px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .thumb-header span { font-size: 20px; font-weight: 800; }
        .thumb-header small { font-size: 12px; opacity: 0.7; }

        .thumb-text { padding: 20px 28px; font-size: 14px; line-height: 1.6; color: #374151; }

        .thumb-footer { background: #f3f4f6; padding: 16px 28px; text-align: center; font-size: 13px; color: #6b7280; }

        .thumb-image { padding: 16px 28px; }
        .thumb-image img { max-width: 100%; border-radius: 8px; max-height: 100px; object-fit: cover; }
        .thumb-img-placeholder {
            height: 80px;
            background: linear-gradient(135deg, #f3f4f6, #e9edf5);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .thumb-divider { padding: 12px 28px; }
        .thumb-divider hr { border: none; border-top: 2px solid; border-image: linear-gradient(135deg,#6c3afc,#e040fb) 1; }

        /* Empty thumbnail */
        .thumb-empty {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f8f5ff 0%, #fdf0ff 50%, #fff5f5 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .thumb-empty-icon { font-size: 36px; }
        .thumb-empty-label { font-size: 12px; color: #9ca3af; font-weight: 600; }

        /* Published badge */
        .published-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #10b981;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 6px rgba(16,185,129,0.4);
        }

        .draft-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #f59e0b;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .site-card-body { padding: 18px; }

        .site-card-name { font-size: 17px; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }

        .site-card-date { font-size: 12px; color: #9ca3af; margin-bottom: 14px; }

        .site-card-actions { display: flex; gap: 8px; }

        .btn-edit {
            flex: 1; text-align: center; padding: 9px;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff; border-radius: 10px; font-size: 13px;
            font-weight: 700; text-decoration: none; transition: all 0.2s;
        }

        .btn-view {
            flex: 1; text-align: center; padding: 9px;
            background: #f3f4f6; color: #374151;
            border-radius: 10px; font-size: 13px;
            font-weight: 700; text-decoration: none; transition: all 0.2s;
        }

        .btn-delete {
            padding: 9px 12px;
            background: #fee2e2; color: #b91c1c;
            border-radius: 10px; font-size: 13px;
            font-weight: 700; text-decoration: none; transition: all 0.2s;
        }

        .btn-edit:hover { opacity: 0.9; transform: translateY(-1px); color: #fff; }
        .btn-view:hover { background: #e9edf5; transform: translateY(-1px); }
        .btn-delete:hover { background: #fecaca; transform: translateY(-1px); }

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

        .modal input:focus { border-color: #6c3afc; box-shadow: 0 0 0 4px rgba(108,58,252,0.1); }

        .modal-btns { display: flex; gap: 10px; }

        .modal-submit {
            flex: 1; padding: 13px;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff; border: none;
            border-radius: 12px; font-weight: 700;
            font-size: 15px; cursor: pointer;
        }

        .modal-cancel {
            padding: 13px 20px;
            background: #f3f4f6; color: #374151;
            border: none; border-radius: 12px;
            font-weight: 700; font-size: 15px; cursor: pointer;
        }

        .empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
        .empty-state .empty-icon { font-size: 64px; margin-bottom: 16px; }
        .empty-state h3 { font-size: 20px; font-weight: 700; color: #374151; margin-bottom: 8px; }
        .empty-state p { font-size: 14px; margin-bottom: 24px; }
    </style>
</head>
<body>

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

<main class="main">
    <div class="page-header">
        <div>
            <h1 class="page-title"><?php echo $showArchived ? '📦 Archived Websites' : 'My Websites 🌐'; ?></h1>
            <p class="page-sub"><?php echo $showArchived ? 'Websites you have archived' : 'Create and manage your websites'; ?></p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <?php if ($showArchived): ?>
                <a href="dashboard.php" class="create-btn" style="background:#6b7280;box-shadow:none;">← Active Sites</a>
            <?php else: ?>
                <?php if ($archivedCount > 0): ?>
                <a href="dashboard.php?view=archived" class="create-btn" style="background:#f3f4f6;color:#374151;box-shadow:none;font-size:13px;">
                    📦 Archived (<?php echo $archivedCount; ?>)
                </a>
                <?php endif; ?>
                <button class="create-btn" onclick="document.getElementById('createModal').classList.add('active')">
                    + Create New Website
                </button>
            <?php endif; ?>
        </div>
    </div>

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

    <div class="section-title">Your Websites</div>

    <?php if (!empty($sitePreviews)): ?>
    <div class="sites-grid">
        <?php foreach ($sitePreviews as $siteId => $data):
            $site = $data['site'];
            $sections = $data['sections'];
        ?>
        <div class="site-card">
            <!-- Thumbnail Preview -->
            <div class="site-card-preview">
                <?php if (!empty($sections)): ?>
                <div class="thumbnail-frame">
                    <div class="thumb-scale-wrap">
                        <?php foreach ($sections as $sec):
                            $style = json_decode($sec['style'] ?? '{}', true) ?: [];
                        ?>
                            <?php if ($sec['type'] === 'hero'): ?>
                                <div class="thumb-hero">
                                    <h2><?php echo htmlspecialchars($sec['content']); ?></h2>
                                    <p>Hero section</p>
                                </div>

                            <?php elseif ($sec['type'] === 'header'): ?>
                                <div class="thumb-header"
                                     style="background:<?php echo $style['bg'] ?? '#1a1a2e'; ?>;
                                            color:<?php echo $style['color'] ?? '#fff'; ?>;
                                            font-family:<?php echo $style['font_family'] ?? 'Arial, sans-serif'; ?>;">
                                    <span><?php echo htmlspecialchars($sec['content']); ?></span>
                                    <small>Nav</small>
                                </div>

                            <?php elseif ($sec['type'] === 'footer'): ?>
                                <div class="thumb-footer"><?php echo htmlspecialchars($sec['content']); ?></div>

                            <?php elseif ($sec['type'] === 'text'): ?>
                                <div class="thumb-text"
                                     style="text-align:<?php echo $style['text_align'] ?? 'left'; ?>;
                                            font-size:<?php echo $style['font_size'] ?? '14px'; ?>;
                                            color:<?php echo $style['color'] ?? '#374151'; ?>;
                                            font-weight:<?php echo $style['font_weight'] ?? 'normal'; ?>;
                                            font-family:<?php echo $style['font_family'] ?? 'Arial, sans-serif'; ?>;">
                                    <?php echo nl2br(htmlspecialchars(mb_substr($sec['content'], 0, 120))); ?>
                                </div>

                            <?php elseif ($sec['type'] === 'image'): ?>
                                <div class="thumb-image">
                                    <?php if (!empty($sec['content'])): ?>
                                        <img src="<?php echo htmlspecialchars($sec['content']); ?>" alt="">
                                    <?php else: ?>
                                        <div class="thumb-img-placeholder">🖼️</div>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($sec['type'] === 'divider'): ?>
                                <div class="thumb-divider"><hr></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="thumb-empty">
                    <div class="thumb-empty-icon">🎨</div>
                    <div class="thumb-empty-label">Empty canvas</div>
                </div>
                <?php endif; ?>

                <?php if (!empty($site['is_published'])): ?>
                    <span class="published-badge">✓ Live</span>
                <?php else: ?>
                    <span class="draft-badge">Draft</span>
                <?php endif; ?>
            </div>

            <div class="site-card-body">
                <div class="site-card-name"><?php echo htmlspecialchars($site['site_name']); ?></div>
                <div class="site-card-date">Created <?php echo date("M d, Y", strtotime($site['created_at'])); ?> · <?php echo count($sections); ?> section<?php echo count($sections) !== 1 ? 's' : ''; ?></div>
                <div class="site-card-actions">
                    <?php if ($showArchived): ?>
                        <a href="../actions/archive_site.php?id=<?php echo $site['id']; ?>&restore=1" class="btn-edit" style="background:#10b981;">♻️ Restore</a>
                        <a href="../actions/delete_site.php?id=<?php echo $site['id']; ?>"
                           class="btn-delete"
                           onclick="return confirm('Permanently delete this website? This cannot be undone.');">🗑️ Delete</a>
                    <?php else: ?>
                        <a href="editor.php?site_id=<?php echo $site['id']; ?>" class="btn-edit">✏️ Edit</a>
                        <a href="view.php?site_id=<?php echo $site['id']; ?>" class="btn-view" target="_blank">👁️ View</a>
                        <a href="../actions/archive_site.php?id=<?php echo $site['id']; ?>"
                           class="btn-delete" style="background:#fef3c7;color:#92400e;"
                           onclick="return confirm('Archive this website? You can restore it later.');" title="Archive">📦</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
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
document.getElementById('createModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
});
</script>
</body>
</html>
