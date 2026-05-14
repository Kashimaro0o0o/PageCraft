<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";
$username = $_SESSION['username'] ?? 'Admin';

// Ensure is_archived column exists
$colCheck = $conn->query("SHOW COLUMNS FROM sites LIKE 'is_archived'");
if ($colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE sites ADD COLUMN is_archived TINYINT(1) DEFAULT 0");
}

$showArchived  = isset($_GET['view']) && $_GET['view'] === 'archived';
$archiveFilter = $showArchived ? "AND (is_archived = 1)" : "AND (is_archived = 0 OR is_archived IS NULL)";

$sites      = $conn->query("SELECT * FROM sites WHERE user_id = " . $_SESSION['user_id'] . " $archiveFilter ORDER BY created_at DESC");
$totalSites = $sites ? $sites->num_rows : 0;

$archivedCount = $conn->query("SELECT COUNT(*) as c FROM sites WHERE user_id = " . $_SESSION['user_id'] . " AND is_archived = 1")->fetch_assoc()['c'] ?? 0;

$sitePreviews = [];
$allSites = $conn->query("SELECT * FROM sites WHERE user_id = " . $_SESSION['user_id'] . " $archiveFilter ORDER BY created_at DESC");
if ($allSites) {
    while ($s = $allSites->fetch_assoc()) {
        $page = $conn->query("SELECT id FROM pages WHERE site_id = " . $s['id'])->fetch_assoc();
        $pid  = $page ? $page['id'] : 0;
        $secs = [];
        if ($pid) {
            $secRes = $conn->query("SELECT * FROM sections WHERE page_id = $pid AND is_archived = 0 ORDER BY position ASC LIMIT 4");
            if ($secRes) { while ($sec = $secRes->fetch_assoc()) $secs[] = $sec; }
        }
        $sitePreviews[$s['id']] = ['site' => $s, 'sections' => $secs];
    }
}

$secCount = $conn->query("SELECT COUNT(*) as total FROM sections WHERE is_archived = 0")->fetch_assoc()['total'] ?? 0;
$pgCount  = $conn->query("SELECT COUNT(*) as total FROM pages")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PageCraft</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
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
            justify-content: center; font-weight: 700; font-size: 15px;
        }

        .stat-card { border: none; border-radius: 18px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(0,0,0,0.1); }
        .stat-card.c1 { border-left: 4px solid #6c3afc; }
        .stat-card.c2 { border-left: 4px solid #e040fb; }
        .stat-card.c3 { border-left: 4px solid #ff6b6b; }

        .stat-value {
            font-size: 40px; font-weight: 800;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-create {
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            border: none; border-radius: 12px;
            font-weight: 700; color: #fff;
            box-shadow: 0 4px 15px rgba(108,58,252,0.3);
            transition: all 0.2s;
        }
        .btn-create:hover { transform: translateY(-2px); color: #fff; box-shadow: 0 8px 24px rgba(108,58,252,0.4); }

        .site-card { border: none; border-radius: 18px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); overflow: hidden; transition: all 0.3s; }
        .site-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.1); }

        .site-preview {
            height: 160px; background: #f0f2f8;
            overflow: hidden; position: relative;
        }

        .site-preview-inner {
            position: absolute;
            top: 0; left: 0;
            width: 960px;
            min-height: 768px;
            transform-origin: top left;
            pointer-events: none;
            background: #fff;
        }

        .site-preview-empty {
            height: 160px; background: #f0f2f8;
            display: flex; align-items: center; justify-content: center;
            font-size: 48px; position: relative;
        }

        .site-preview-inner .sp-hero {
            background: linear-gradient(135deg, #6c3afc 0%, #e040fb 50%, #ff6b6b 100%);
            color: #fff; padding: 80px 40px; text-align: center;
        }
        .site-preview-inner .sp-hero h1 { font-size: 52px; font-weight: 900; margin: 0 0 12px; }
        .site-preview-inner .sp-hero p  { font-size: 22px; opacity: .75; margin: 0; }

        .site-preview-inner .sp-header {
            background: #1a1a2e; color: #fff;
            padding: 28px 48px; display: flex; align-items: center; justify-content: space-between;
        }
        .site-preview-inner .sp-header .brand {
            font-size: 26px; font-weight: 900;
            background: linear-gradient(135deg,#6c3afc,#e040fb);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }

        .site-preview-inner .sp-text {
            padding: 60px 80px; font-size: 20px; line-height: 1.8; color: #333;
        }

        .site-preview-inner .sp-image {
            padding: 40px; background: #f8f9ff; min-height: 240px;
            display: flex; align-items: center; justify-content: center;
        }
        .site-preview-inner .sp-image img {
            max-width: 100%; border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,.12);
        }

        .site-preview-inner .sp-divider { padding: 10px 48px; }
        .site-preview-inner .sp-divider hr {
            border: none; height: 6px;
            background: linear-gradient(135deg,#6c3afc,#e040fb,#ff6b6b); border-radius: 999px;
        }

        .site-preview-inner .sp-button {
            padding: 40px 48px; text-align: center;
        }
        .site-preview-inner .sp-btn-el {
            display: inline-block; padding: 18px 48px;
            border-radius: 12px; font-size: 20px; font-weight: bold;
            color: #fff; background: #6c3afc; text-decoration: none;
        }

        .site-preview-inner .sp-footer {
            background: #1a1a2e; color: rgba(255,255,255,.6);
            padding: 40px 48px; text-align: center; font-size: 18px;
        }
        .site-preview-inner .sp-footer strong { display: block; font-size: 26px; font-weight: 800; color: #fff; margin-bottom: 8px; }

        .badge-live { position: absolute; top: 10px; right: 10px; }

        .empty-state { background: #fff; border-radius: 20px; padding: 60px 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top px-4">
    <a class="navbar-brand" href="#">PageCraft</a>
    <div class="ms-auto d-flex align-items-center gap-3">
        <a href="logs.php" class="btn btn-light btn-sm fw-bold">
            <i class="bi bi-bar-chart-line"></i> Visitor Logs
        </a>
        <span class="fw-semibold text-muted small"><?php echo htmlspecialchars($username); ?></span>
        <div class="avatar"><?php echo strtoupper($username[0]); ?></div>
        <a href="../logout.php" class="btn btn-danger btn-sm fw-bold">Logout</a>
    </div>
</nav>

<div class="container py-4" style="max-width:1200px;">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold fs-3 mb-0">
                <?php echo $showArchived ? '📦 Archived Sites' : '🌐 My Websites'; ?>
            </h1>
            <p class="text-muted small mt-1">
                <?php echo $showArchived ? 'Restore or permanently delete archived sites' : 'Manage and build your websites'; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($showArchived): ?>
                <a href="dashboard.php" class="btn btn-secondary fw-bold">← Active Sites</a>
            <?php else: ?>
                <?php if ($archivedCount > 0): ?>
                    <a href="dashboard.php?view=archived" class="btn btn-outline-secondary fw-bold btn-sm">
                        📦 Archived (<?php echo $archivedCount; ?>)
                    </a>
                <?php endif; ?>
                <button class="btn btn-create px-4 py-2" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="bi bi-plus-lg"></i> Create New Website
                </button>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card c1 p-4">
                <div class="text-muted small fw-bold text-uppercase">Total Websites</div>
                <div class="stat-value"><?php echo $totalSites; ?></div>
                <div class="text-muted small">All your sites</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card c2 p-4">
                <div class="text-muted small fw-bold text-uppercase">Sections Added</div>
                <div class="stat-value"><?php echo $secCount; ?></div>
                <div class="text-muted small">Active content blocks</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card c3 p-4">
                <div class="text-muted small fw-bold text-uppercase">Pages Created</div>
                <div class="stat-value"><?php echo $pgCount; ?></div>
                <div class="text-muted small">Across all sites</div>
            </div>
        </div>
    </div>

    
    <h5 class="fw-bold mb-3">Your Websites</h5>

    <?php if (!empty($sitePreviews)): ?>
    <div class="row g-4">
        <?php foreach ($sitePreviews as $siteId => $data):
            $site     = $data['site'];
            $sections = $data['sections'];
        ?>
        <div class="col-sm-6 col-lg-4">
            <div class="card site-card h-100">
                <?php if (!empty($sections)): ?>
                <div class="site-preview">
                    <div class="site-preview-inner">
                        <?php foreach ($sections as $sec):
                            $style = json_decode($sec['style'] ?? '{}', true) ?: [];
                        ?>
                            <?php if ($sec['type'] === 'hero'): ?>
                                <div class="sp-hero">
                                    <h1><?php echo htmlspecialchars($sec['content']); ?></h1>
                                    <p>Welcome to <?php echo htmlspecialchars($site['site_name']); ?></p>
                                </div>

                            <?php elseif ($sec['type'] === 'header'): ?>
                                <div class="sp-header" style="background:<?php echo htmlspecialchars($style['bg'] ?? '#1a1a2e'); ?>;">
                                    <span class="brand"><?php echo htmlspecialchars($sec['content']); ?></span>
                                    <nav style="display:flex;gap:32px;">
                                        <span style="color:rgba(255,255,255,.5);font-size:18px;">Home</span>
                                        <span style="color:rgba(255,255,255,.5);font-size:18px;">About</span>
                                        <span style="color:rgba(255,255,255,.5);font-size:18px;">Contact</span>
                                    </nav>
                                </div>

                            <?php elseif ($sec['type'] === 'text'): ?>
                                <div class="sp-text"
                                     style="text-align:<?php echo htmlspecialchars($style['text_align'] ?? 'left'); ?>;
                                            color:<?php echo htmlspecialchars($style['color'] ?? '#333'); ?>;">
                                    <?php echo nl2br(htmlspecialchars(mb_substr($sec['content'], 0, 200))); ?>
                                </div>

                            <?php elseif ($sec['type'] === 'image'): ?>
                                <div class="sp-image">
                                    <?php if (!empty($sec['content'])): ?>
                                        <img src="<?php echo htmlspecialchars($sec['content']); ?>" alt="Image" style="max-height:200px;">
                                    <?php else: ?>
                                        <span style="font-size:64px;">🖼️</span>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($sec['type'] === 'divider'): ?>
                                <div class="sp-divider"><hr></div>

                            <?php elseif ($sec['type'] === 'button'): ?>
                                <div class="sp-button" style="text-align:<?php echo htmlspecialchars($style['text_align'] ?? 'center'); ?>;">
                                    <span class="sp-btn-el"
                                          style="background:<?php echo htmlspecialchars($style['bg'] ?? '#6c3afc'); ?>;
                                                 color:<?php echo htmlspecialchars($style['color'] ?? '#fff'); ?>;
                                                 border-radius:<?php echo htmlspecialchars($style['radius'] ?? '12px'); ?>;">
                                        <?php echo htmlspecialchars($sec['content']); ?>
                                    </span>
                                </div>

                            <?php elseif ($sec['type'] === 'footer'): ?>
                                <div class="sp-footer">
                                    <strong><?php echo htmlspecialchars($sec['content']); ?></strong>
                                    Powered by PageCraft
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($site['is_published'])): ?>
                        <span class="badge bg-success badge-live">✓ Live</span>
                    <?php else: ?>
                        <span class="badge bg-secondary badge-live">Draft</span>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="site-preview-empty">
                    🌐
                    <?php if (!empty($site['is_published'])): ?>
                        <span class="badge bg-success badge-live">✓ Live</span>
                    <?php else: ?>
                        <span class="badge bg-secondary badge-live">Draft</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="card-body">
                    <h6 class="card-title fw-bold"><?php echo htmlspecialchars($site['site_name']); ?></h6>
                    <p class="text-muted small mb-3">
                        Created <?php echo date("M d, Y", strtotime($site['created_at'])); ?> &middot;
                        <?php echo count($sections); ?> section<?php echo count($sections) !== 1 ? 's' : ''; ?>
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if ($showArchived): ?>
                            <a href="../actions/archive_site.php?id=<?php echo $site['id']; ?>&restore=1"
                               class="btn btn-success btn-sm fw-bold">♻️ Restore</a>
                            <a href="../actions/delete_site.php?id=<?php echo $site['id']; ?>"
                               class="btn btn-danger btn-sm fw-bold"
                               onclick="return confirm('Permanently delete this website? This cannot be undone.');">🗑️ Delete</a>
                        <?php else: ?>
                            <a href="editor.php?site_id=<?php echo $site['id']; ?>"
                               class="btn btn-primary btn-sm fw-bold">✏️ Edit</a>
                            <a href="view.php?site_id=<?php echo $site['id']; ?>"
                               class="btn btn-outline-primary btn-sm fw-bold" target="_blank">👁️ View</a>
                            <a href="../actions/archive_site.php?id=<?php echo $site['id']; ?>"
                               class="btn btn-warning btn-sm fw-bold"
                               onclick="return confirm('Archive this website? You can restore it later.');"
                               title="Archive">📦</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="empty-state text-center">
        <div style="font-size:64px;">🌐</div>
        <h4 class="fw-bold mt-3">No websites yet!</h4>
        <p class="text-muted">Create your first website to get started</p>
        <button class="btn btn-create px-4 py-2 mt-2" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-lg"></i> Create New Website
        </button>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div>
                    <h5 class="modal-title fw-bold fs-4">🌐 Create New Website</h5>
                    <p class="text-muted small mb-0">Choose a template to get started quickly!</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-4">
                <form action="../actions/create_site.php" method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Website Name</label>
                        <input type="text" name="site_name" class="form-control form-control-lg rounded-3"
                               placeholder="e.g. My Portfolio, Business Site..." required>
                    </div>

                    <label class="form-label fw-bold mb-3">Choose a Template</label>
                    <input type="hidden" name="template" id="selectedTemplate" value="blank">

                    <div class="row g-3 mb-4">

                        
                        <div class="col-6 col-md-4">
                            <div class="template-card selected" onclick="selectTemplate(this,'blank')">
                                <div class="template-preview" style="background:#f8f9fa;">
                                    <div style="font-size:36px;">⬜</div>
                                </div>
                                <div class="template-label">Blank</div>
                                <div class="template-sub">Empty canvas</div>
                            </div>
                        </div>

                        
                        <div class="col-6 col-md-4">
                            <div class="template-card" onclick="selectTemplate(this,'business')">
                                <div class="template-preview" style="background:linear-gradient(135deg,#1a1a2e,#16213e);">
                                    <div style="font-size:36px;">🏢</div>
                                </div>
                                <div class="template-label">Business</div>
                                <div class="template-sub">Professional company</div>
                            </div>
                        </div>

                        
                        <div class="col-6 col-md-4">
                            <div class="template-card" onclick="selectTemplate(this,'shop')">
                                <div class="template-preview" style="background:linear-gradient(135deg,#064e3b,#065f46);">
                                    <div style="font-size:36px;">🛒</div>
                                </div>
                                <div class="template-label">Shop</div>
                                <div class="template-sub">Product listings</div>
                            </div>
                        </div>

                        
                        <div class="col-6 col-md-4">
                            <div class="template-card" onclick="selectTemplate(this,'restaurant')">
                                <div class="template-preview" style="background:linear-gradient(135deg,#7f1d1d,#991b1b);">
                                    <div style="font-size:36px;">🍽️</div>
                                </div>
                                <div class="template-label">Restaurant</div>
                                <div class="template-sub">Menu & dining</div>
                            </div>
                        </div>

                        
                        <div class="col-6 col-md-4">
                            <div class="template-card" onclick="selectTemplate(this,'music')">
                                <div class="template-preview" style="background:linear-gradient(135deg,#18181b,#27272a);">
                                    <div style="font-size:36px;">🎵</div>
                                </div>
                                <div class="template-label">Music / Band</div>
                                <div class="template-sub">Artist profile & shows</div>
                            </div>
                        </div>

                        
                        <div class="col-6 col-md-4">
                            <div class="template-card" onclick="selectTemplate(this,'event')">
                                <div class="template-preview" style="background:linear-gradient(135deg,#4a044e,#6b21a8);">
                                    <div style="font-size:36px;">🎉</div>
                                </div>
                                <div class="template-label">Event</div>
                                <div class="template-sub">Schedule & RSVP</div>
                            </div>
                        </div>

                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-create flex-fill py-2 fw-bold">Create Website →</button>
                        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.template-card {
    border: 2px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s;
    background: #fff;
}
.template-card:hover {
    border-color: #6c3afc;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(108,58,252,0.15);
}
.template-card.selected {
    border-color: #6c3afc;
    box-shadow: 0 0 0 3px rgba(108,58,252,0.2);
}
.template-preview {
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.template-label {
    font-size: 13px;
    font-weight: 700;
    color: #1a1a2e;
    padding: 8px 10px 2px;
}
.template-sub {
    font-size: 11px;
    color: #9ca3af;
    padding: 0 10px 8px;
}
</style>

<script>
function selectTemplate(el, value) {
    document.querySelectorAll('.template-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('selectedTemplate').value = value;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function scalePreviewInners() {
    document.querySelectorAll('.site-preview').forEach(function(preview) {
        var inner = preview.querySelector('.site-preview-inner');
        if (!inner) return;
        var scale = Math.max(preview.offsetWidth / 960, preview.offsetHeight / 768);
        inner.style.transform = 'scale(' + scale + ')';
    });
}
document.addEventListener('DOMContentLoaded', scalePreviewInners);
window.addEventListener('resize', scalePreviewInners);
</script>
</body>
</html>