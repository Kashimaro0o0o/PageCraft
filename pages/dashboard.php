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
                <form action="../actions/create_site.php" method="POST" id="createSiteForm">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Website Name</label>
                        <input type="text" name="site_name" class="form-control form-control-lg rounded-3"
                               placeholder="e.g. My Portfolio, Business Site..." required>
                    </div>
                    <input type="hidden" name="template" id="selectedTemplate" value="blank">

                    <!-- STEP 1: Category picker -->
                    <div id="step1">
                        <label class="form-label fw-bold mb-3">Choose a Template</label>
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-md-4">
                                <div class="template-card selected" onclick="selectCategory(this,'blank')">
                                    <div class="template-preview" style="background:#f8f9fa;">
                                        <div style="font-size:36px;">⬜</div>
                                    </div>
                                    <div class="template-label">Blank</div>
                                    <div class="template-sub">Empty canvas</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="template-card" onclick="selectCategory(this,'business')">
                                    <div class="template-preview" style="background:linear-gradient(135deg,#1a1a2e,#16213e);">
                                        <div style="font-size:36px;">🏢</div>
                                    </div>
                                    <div class="template-label">Business</div>
                                    <div class="template-sub">Professional company</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="template-card" onclick="selectCategory(this,'shop')">
                                    <div class="template-preview" style="background:linear-gradient(135deg,#064e3b,#065f46);">
                                        <div style="font-size:36px;">🛒</div>
                                    </div>
                                    <div class="template-label">Shop</div>
                                    <div class="template-sub">Product listings</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="template-card" onclick="selectCategory(this,'restaurant')">
                                    <div class="template-preview" style="background:linear-gradient(135deg,#7f1d1d,#991b1b);">
                                        <div style="font-size:36px;">🍽️</div>
                                    </div>
                                    <div class="template-label">Restaurant</div>
                                    <div class="template-sub">Menu & dining</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="template-card" onclick="selectCategory(this,'music')">
                                    <div class="template-preview" style="background:linear-gradient(135deg,#18181b,#27272a);">
                                        <div style="font-size:36px;">🎵</div>
                                    </div>
                                    <div class="template-label">Music / Band</div>
                                    <div class="template-sub">Artist profile & shows</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="template-card" onclick="selectCategory(this,'event')">
                                    <div class="template-preview" style="background:linear-gradient(135deg,#4a044e,#6b21a8);">
                                        <div style="font-size:36px;">🎉</div>
                                    </div>
                                    <div class="template-label">Event</div>
                                    <div class="template-sub">Schedule & RSVP</div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" id="step1NextBtn" class="btn btn-create flex-fill py-2 fw-bold" onclick="goToStep2()">Next: Pick a Style →</button>
                            <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>

                    <!-- STEP 2: Sub-template picker (hidden until a non-blank category is chosen) -->
                    <div id="step2" style="display:none;">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-light fw-bold" onclick="goBackToStep1()">← Back</button>
                            <span class="fw-bold" id="step2Title">Choose a Business Style</span>
                        </div>
                        <div class="row g-3 mb-4" id="subTemplateGrid"></div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-create flex-fill py-2 fw-bold">Create Website →</button>
                            <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                        </div>
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
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}
.template-preview-mini {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
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
const SUB_TEMPLATES = {
    business: [
        {
            id: 'business_corporate',
            label: 'Corporate',
            sub: 'Formal & professional',
            preview: `<div class="template-preview-mini">
                <div style="background:#1a1a2e;padding:6px 10px;display:flex;align-items:center;justify-content:space-between;">
                    <span style="color:#fff;font-weight:900;font-size:9px;">NEXUS CORP</span>
                    <span style="color:#a78bfa;font-size:7px;">Home · About · Services</span>
                </div>
                <div style="background:linear-gradient(135deg,#1e3a5f,#2563eb);padding:12px;text-align:center;flex:1;">
                    <div style="color:#fff;font-weight:900;font-size:10px;">BUILDING THE FUTURE</div>
                    <div style="color:#93c5fd;font-size:7px;margin-top:2px;">Enterprise solutions for modern business</div>
                    <div style="background:#2563eb;border:1px solid #60a5fa;color:#fff;font-size:7px;padding:3px 8px;border-radius:4px;display:inline-block;margin-top:4px;">Get Started</div>
                </div>
                <div style="background:#f8faff;padding:6px 10px;display:flex;gap:6px;justify-content:center;">
                    <div style="background:#e0e7ff;border-radius:4px;padding:3px 6px;font-size:7px;color:#3730a3;font-weight:700;">Strategy</div>
                    <div style="background:#e0e7ff;border-radius:4px;padding:3px 6px;font-size:7px;color:#3730a3;font-weight:700;">Consulting</div>
                    <div style="background:#e0e7ff;border-radius:4px;padding:3px 6px;font-size:7px;color:#3730a3;font-weight:700;">Growth</div>
                </div>
            </div>`
        },
        {
            id: 'business_agency',
            label: 'Creative Agency',
            sub: 'Bold & modern',
            preview: `<div class="template-preview-mini">
                <div style="background:#fff;padding:5px 10px;display:flex;align-items:center;justify-content:space-between;border-bottom:2px solid #f0f0f0;">
                    <span style="font-weight:900;font-size:9px;background:linear-gradient(135deg,#6c3afc,#e040fb);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">STUDIO</span>
                    <span style="font-size:7px;color:#888;">Work · About · Contact</span>
                </div>
                <div style="background:linear-gradient(135deg,#6c3afc,#e040fb);padding:14px;text-align:center;flex:1;">
                    <div style="color:#fff;font-weight:900;font-size:11px;">WE CREATE.</div>
                    <div style="color:rgba(255,255,255,0.8);font-size:7px;margin-top:2px;">Design · Branding · Digital</div>
                </div>
                <div style="background:#1a1a2e;padding:5px 10px;display:flex;gap:4px;align-items:center;justify-content:center;">
                    <div style="width:16px;height:10px;background:#6c3afc;border-radius:2px;"></div>
                    <div style="width:16px;height:10px;background:#e040fb;border-radius:2px;"></div>
                    <div style="width:16px;height:10px;background:#ff6b6b;border-radius:2px;"></div>
                </div>
            </div>`
        }
    ],
    shop: [
        {
            id: 'shop_boutique',
            label: 'Boutique Store',
            sub: 'Elegant product showcase',
            preview: `<div class="template-preview-mini">
                <div style="background:#fff;padding:5px 10px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f0f0f0;">
                    <span style="font-weight:900;font-size:9px;color:#1a1a2e;">✦ ÉLAN</span>
                    <span style="font-size:7px;color:#888;">🛒 Cart (0)</span>
                </div>
                <div style="background:linear-gradient(180deg,#fdf6ec,#fce7c8);padding:10px;text-align:center;">
                    <div style="font-size:8px;color:#92400e;font-weight:700;letter-spacing:1px;">NEW COLLECTION 2026</div>
                    <div style="font-size:11px;font-weight:900;color:#1a1a2e;margin-top:2px;">WEAR YOUR STORY</div>
                </div>
                <div style="display:flex;gap:3px;padding:5px 8px;background:#fff;">
                    <div style="flex:1;background:#f3f4f6;border-radius:4px;height:22px;display:flex;align-items:center;justify-content:center;font-size:7px;color:#6b7280;">👗 Top</div>
                    <div style="flex:1;background:#f3f4f6;border-radius:4px;height:22px;display:flex;align-items:center;justify-content:center;font-size:7px;color:#6b7280;">👠 Shoes</div>
                    <div style="flex:1;background:#f3f4f6;border-radius:4px;height:22px;display:flex;align-items:center;justify-content:center;font-size:7px;color:#6b7280;">👜 Bags</div>
                </div>
            </div>`
        },
        {
            id: 'shop_tech',
            label: 'Tech Store',
            sub: 'Gadgets & electronics',
            preview: `<div class="template-preview-mini">
                <div style="background:#0f172a;padding:5px 10px;display:flex;align-items:center;justify-content:space-between;">
                    <span style="color:#38bdf8;font-weight:900;font-size:9px;">⚡ TECHPULSE</span>
                    <span style="font-size:7px;color:#64748b;">🛒</span>
                </div>
                <div style="background:linear-gradient(135deg,#0f172a,#1e3a5f);padding:10px;text-align:center;">
                    <div style="color:#38bdf8;font-size:7px;font-weight:700;letter-spacing:1px;">NEXT-GEN GADGETS</div>
                    <div style="color:#fff;font-size:11px;font-weight:900;margin-top:2px;">POWER UP YOUR LIFE</div>
                    <div style="background:#38bdf8;color:#0f172a;font-size:7px;padding:2px 8px;border-radius:3px;display:inline-block;margin-top:4px;font-weight:700;">Shop Now</div>
                </div>
                <div style="background:#1e293b;padding:5px 8px;display:flex;gap:3px;justify-content:center;">
                    <div style="background:#334155;border-radius:3px;padding:2px 5px;font-size:6px;color:#94a3b8;">📱 Phones</div>
                    <div style="background:#334155;border-radius:3px;padding:2px 5px;font-size:6px;color:#94a3b8;">💻 Laptops</div>
                    <div style="background:#334155;border-radius:3px;padding:2px 5px;font-size:6px;color:#94a3b8;">🎧 Audio</div>
                </div>
            </div>`
        }
    ],
    restaurant: [
        {
            id: 'restaurant_finedining',
            label: 'Fine Dining',
            sub: 'Upscale & elegant',
            preview: `<div class="template-preview-mini">
                <div style="background:#1c1208;padding:6px 10px;text-align:center;border-bottom:1px solid #8B6914;">
                    <span style="color:#d4af37;font-weight:900;font-size:9px;letter-spacing:2px;">LE MAISON</span>
                </div>
                <div style="background:linear-gradient(180deg,#1c1208,#2d1f0e);padding:12px;text-align:center;flex:1;">
                    <div style="color:#d4af37;font-size:7px;letter-spacing:2px;">EST. 2010</div>
                    <div style="color:#fff;font-weight:900;font-size:10px;margin-top:3px;">EXQUISITE CUISINE</div>
                    <div style="color:#d4af37;font-size:7px;margin-top:2px;">Farm to Table · Award Winning</div>
                </div>
                <div style="background:#f9f5ec;padding:5px 10px;display:flex;gap:6px;justify-content:center;">
                    <span style="font-size:7px;color:#78350f;font-weight:700;">Starters</span>
                    <span style="font-size:7px;color:#d4af37;">•</span>
                    <span style="font-size:7px;color:#78350f;font-weight:700;">Mains</span>
                    <span style="font-size:7px;color:#d4af37;">•</span>
                    <span style="font-size:7px;color:#78350f;font-weight:700;">Desserts</span>
                </div>
            </div>`
        },
        {
            id: 'restaurant_casual',
            label: 'Casual Eatery',
            sub: 'Vibrant & family-friendly',
            preview: `<div class="template-preview-mini">
                <div style="background:#dc2626;padding:5px 10px;display:flex;align-items:center;gap:4px;">
                    <span style="font-size:10px;">🍔</span>
                    <span style="color:#fff;font-weight:900;font-size:9px;">GRUB HUB</span>
                </div>
                <div style="background:#fef9c3;padding:8px;text-align:center;flex:1;">
                    <div style="font-size:10px;font-weight:900;color:#92400e;">REAL FOOD. REAL FAST.</div>
                    <div style="font-size:7px;color:#b45309;margin-top:2px;">Burgers · Pasta · Sides · Drinks</div>
                    <div style="background:#dc2626;color:#fff;font-size:7px;padding:2px 8px;border-radius:999px;display:inline-block;margin-top:4px;font-weight:700;">Order Now 🛵</div>
                </div>
                <div style="background:#fff7ed;padding:5px 8px;font-size:7px;color:#7c3aed;text-align:center;font-weight:700;">
                    📍 Open Daily 10AM–11PM · Dine-in & Delivery
                </div>
            </div>`
        }
    ],
    music: [
        {
            id: 'music_band',
            label: 'Rock Band',
            sub: 'Dark, loud & electric',
            preview: `<div class="template-preview-mini">
                <div style="background:#09090b;padding:5px 10px;display:flex;align-items:center;justify-content:space-between;">
                    <span style="color:#a855f7;font-weight:900;font-size:9px;letter-spacing:1px;">VOID STATIC</span>
                    <span style="font-size:7px;color:#52525b;">Music · Tour · Merch</span>
                </div>
                <div style="background:linear-gradient(135deg,#09090b,#3b0764);padding:12px;text-align:center;flex:1;position:relative;">
                    <div style="color:#a855f7;font-size:18px;">🎸</div>
                    <div style="color:#fff;font-weight:900;font-size:9px;margin-top:2px;">NEW ALBUM: "STATIC"</div>
                    <div style="color:#a855f7;font-size:7px;">Out Now on All Platforms</div>
                </div>
                <div style="background:#18181b;padding:5px 10px;display:flex;gap:5px;justify-content:center;">
                    <div style="background:#27272a;border-radius:3px;padding:2px 5px;font-size:6px;color:#a855f7;">🎵 Spotify</div>
                    <div style="background:#27272a;border-radius:3px;padding:2px 5px;font-size:6px;color:#a855f7;">🍎 Apple</div>
                    <div style="background:#27272a;border-radius:3px;padding:2px 5px;font-size:6px;color:#a855f7;">▶ YouTube</div>
                </div>
            </div>`
        },
        {
            id: 'music_solo',
            label: 'Solo Artist',
            sub: 'Minimal & aesthetic',
            preview: `<div class="template-preview-mini">
                <div style="background:#fff;padding:5px 10px;text-align:center;border-bottom:1px solid #f0f0f0;">
                    <span style="font-weight:900;font-size:9px;color:#1a1a2e;letter-spacing:2px;">LUNA</span>
                </div>
                <div style="background:linear-gradient(180deg,#fdf2f8,#f3e8ff);padding:10px;text-align:center;flex:1;">
                    <div style="font-size:14px;">🎤</div>
                    <div style="font-size:8px;color:#7e22ce;font-weight:700;margin-top:2px;">SINGER · SONGWRITER</div>
                    <div style="font-size:9px;color:#1a1a2e;font-weight:900;margin-top:2px;">"Echoes" — New Single</div>
                </div>
                <div style="background:#fff;padding:5px 8px;display:flex;gap:4px;justify-content:center;">
                    <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:999px;padding:2px 6px;font-size:6px;color:#7e22ce;">Tour Dates</div>
                    <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:999px;padding:2px 6px;font-size:6px;color:#7e22ce;">Stream Now</div>
                </div>
            </div>`
        }
    ],
    event: [
        {
            id: 'event_wedding',
            label: 'Wedding',
            sub: 'Romantic & elegant',
            preview: `<div class="template-preview-mini">
                <div style="background:#fff;padding:5px 10px;text-align:center;border-bottom:1px solid #fce7f3;">
                    <span style="color:#be185d;font-size:7px;letter-spacing:2px;font-weight:700;">SAVE THE DATE</span>
                </div>
                <div style="background:linear-gradient(180deg,#fff1f5,#fce7f3);padding:10px;text-align:center;flex:1;">
                    <div style="font-size:12px;">💍</div>
                    <div style="font-size:10px;font-weight:900;color:#1a1a2e;margin-top:2px;">Sofia & Marco</div>
                    <div style="font-size:7px;color:#be185d;margin-top:2px;">June 15, 2026 · 4:00 PM</div>
                    <div style="font-size:7px;color:#9d174d;margin-top:1px;">Grand Ballroom, Manila Hotel</div>
                </div>
                <div style="background:#fff;padding:5px 8px;text-align:center;">
                    <div style="background:#fce7f3;border-radius:999px;padding:2px 10px;font-size:7px;color:#be185d;font-weight:700;display:inline-block;">RSVP by June 1</div>
                </div>
            </div>`
        },
        {
            id: 'event_conference',
            label: 'Conference',
            sub: 'Professional & structured',
            preview: `<div class="template-preview-mini">
                <div style="background:#1e3a5f;padding:5px 10px;display:flex;align-items:center;justify-content:space-between;">
                    <span style="color:#fff;font-weight:900;font-size:8px;">SUMMIT 2026</span>
                    <span style="background:#2563eb;color:#fff;font-size:6px;padding:1px 5px;border-radius:3px;">Register</span>
                </div>
                <div style="background:linear-gradient(135deg,#1e3a5f,#1e40af);padding:10px;text-align:center;flex:1;">
                    <div style="color:#93c5fd;font-size:7px;letter-spacing:1px;">ANNUAL TECH SUMMIT</div>
                    <div style="color:#fff;font-size:10px;font-weight:900;margin-top:2px;">INNOVATE · CONNECT · GROW</div>
                    <div style="color:#bfdbfe;font-size:7px;margin-top:2px;">Aug 20–22, 2026 · SMX Manila</div>
                </div>
                <div style="background:#eff6ff;padding:4px 8px;display:flex;gap:3px;justify-content:center;">
                    <div style="background:#dbeafe;border-radius:3px;padding:1px 5px;font-size:6px;color:#1d4ed8;font-weight:700;">Speakers</div>
                    <div style="background:#dbeafe;border-radius:3px;padding:1px 5px;font-size:6px;color:#1d4ed8;font-weight:700;">Schedule</div>
                    <div style="background:#dbeafe;border-radius:3px;padding:1px 5px;font-size:6px;color:#1d4ed8;font-weight:700;">Tickets</div>
                </div>
            </div>`
        }
    ]
};

let selectedCategory = 'blank';

function selectCategory(el, category) {
    document.querySelectorAll('#step1 .template-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    selectedCategory = category;
    document.getElementById('selectedTemplate').value = category; // blank goes direct
}

function goToStep2() {
    if (selectedCategory === 'blank') {
        document.getElementById('createSiteForm').submit();
        return;
    }
    const subs = SUB_TEMPLATES[selectedCategory];
    if (!subs) { document.getElementById('createSiteForm').submit(); return; }

    const categoryNames = { business:'Business', shop:'Shop', restaurant:'Restaurant', music:'Music / Band', event:'Event' };
    document.getElementById('step2Title').textContent = 'Choose a ' + (categoryNames[selectedCategory] || selectedCategory) + ' Style';

    const grid = document.getElementById('subTemplateGrid');
    grid.innerHTML = '';
    subs.forEach((tpl, i) => {
        const col = document.createElement('div');
        col.className = 'col-6 col-md-6';
        col.innerHTML = `<div class="template-card${i===0?' selected':''}" onclick="selectSubTemplate(this,'${tpl.id}')">
            <div class="template-preview">${tpl.preview}</div>
            <div class="template-label">${tpl.label}</div>
            <div class="template-sub">${tpl.sub}</div>
        </div>`;
        grid.appendChild(col);
    });
    // Pre-select first
    document.getElementById('selectedTemplate').value = subs[0].id;

    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = '';
}

function goBackToStep1() {
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step1').style.display = '';
    document.getElementById('selectedTemplate').value = selectedCategory;
}

function selectSubTemplate(el, id) {
    document.querySelectorAll('#subTemplateGrid .template-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('selectedTemplate').value = id;
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