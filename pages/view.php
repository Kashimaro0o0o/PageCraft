<?php
include "../config/db.php";

$site_id = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;
if ($site_id <= 0) { header("Location: dashboard.php"); exit(); }

$ip        = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

$stmt = $conn->prepare("INSERT INTO visitor_logs (site_id, ip_address, user_agent) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $site_id, $ip, $userAgent);
$stmt->execute();
$stmt->close();

$site = $conn->query("SELECT * FROM sites WHERE id = $site_id")->fetch_assoc();
if (!$site) { header("Location: dashboard.php"); exit(); }

$isPreview = isset($_GET['preview']);

if ($site['is_published'] == 0 && !$isPreview) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Not Published — PageCraft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0d0d14; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .lock-icon { font-size: 48px; background: rgba(108,58,252,0.15); border: 1px solid rgba(108,58,252,0.3); border-radius: 20px; width: 90px; height: 90px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; }
        h1 { font-size: 42px; font-weight: 800; color: #fff; }
        .btn-grad { background: linear-gradient(135deg, #6c3afc, #e040fb); border: none; color: #fff; border-radius: 12px; padding: 13px 28px; font-weight: 600; }
        .btn-grad:hover { color: #fff; transform: translateY(-2px); }
        .btn-ghost { background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.7); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 13px 28px; font-weight: 600; }
        .btn-ghost:hover { color: #fff; background: rgba(255,255,255,0.12); }
    </style>
</head>
<body>
<div class="text-center px-3">
    <div class="lock-icon">🔒</div>
    <span class="badge rounded-pill mb-3" style="background:rgba(108,58,252,0.2);color:#a78bfa;font-size:12px;letter-spacing:.08em;">COMING SOON</span>
    <h1 class="mb-3">Not Published Yet</h1>
    <p class="text-white-50 mb-4 fs-6">
        <strong class="text-white"><?php echo htmlspecialchars($site['site_name']); ?></strong> is still being built.<br>
        This page will go live once the owner publishes it.
    </p>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
        <a href="dashboard.php" class="btn btn-grad">🏠 Go to Dashboard</a>
        <a href="editor.php?site_id=<?php echo $site_id; ?>" class="btn btn-ghost">✏️ Open Editor</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
    exit();
}

$page = $conn->query("SELECT * FROM pages WHERE site_id = $site_id")->fetch_assoc();
if (!$page) { header("Location: dashboard.php"); exit(); }
$page_id  = $page['id'];
$sections = $conn->query("SELECT * FROM sections WHERE page_id = $page_id AND is_archived = 0 ORDER BY position ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site['site_name']); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; }

        /* Preview toolbar */
        .preview-toolbar { background: #1a1a2e; padding: 10px 24px; position: sticky; top: 0; z-index: 999; }
        .preview-dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

        /* Section styles */
        .section-hero {
            background: linear-gradient(135deg, #6c3afc 0%, #e040fb 50%, #ff6b6b 100%);
            color: #fff; padding: 100px 40px; text-align: center;
        }
        .section-hero h1 { font-size: clamp(36px, 6vw, 56px); font-weight: 900; letter-spacing: -1px; }

        .section-header { background: #1a1a2e; color: #fff; padding: 20px 48px; }
        .section-header .brand { font-size: 22px; font-weight: 900; background: linear-gradient(135deg,#6c3afc,#e040fb); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }

        .section-text { max-width: 800px; margin: 0 auto; padding: 60px 40px; font-size: 17px; line-height: 1.8; }

        .section-image { padding: 40px; background: #f8f9ff; position: relative; min-height: 200px; }
        .section-image img { border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); display: block; }

        .section-divider { padding: 10px 48px; }
        .section-divider hr { border: none; height: 3px; background: linear-gradient(135deg,#6c3afc,#e040fb,#ff6b6b); border-radius: 999px; }

        .section-footer { background: #1a1a2e; color: rgba(255,255,255,.6); padding: 32px 48px; text-align: center; font-size: 14px; }
        .section-footer strong { display: block; font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 8px; }

        .empty-preview { min-height: 60vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px; color: #9ca3af; }
    </style>
</head>
<body>

<?php if ($isPreview): ?>
<div class="preview-toolbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2 text-white-50 small fw-semibold">
        <div class="preview-dot"></div>
        <span>Previewing:</span>
        <span class="text-white fw-bold"><?php echo htmlspecialchars($site['site_name']); ?></span>
    </div>
    <div class="d-flex gap-2">
        <a href="editor.php?site_id=<?php echo $site_id; ?>" class="btn btn-sm btn-light fw-bold">✏️ Back to Editor</a>
        <a href="dashboard.php" class="btn btn-sm fw-bold text-white" style="background:linear-gradient(135deg,#6c3afc,#e040fb);border:none;">🏠 Dashboard</a>
    </div>
</div>
<?php endif; ?>

<?php if ($sections && $sections->num_rows > 0): ?>
    <?php while ($sec = $sections->fetch_assoc()): ?>

        <?php if ($sec['type'] === 'hero'): ?>
            <div class="section-hero">
                <div class="container">
                    <h1><?php echo htmlspecialchars($sec['content']); ?></h1>
                    <p class="fs-5 opacity-75 mt-2">Welcome to <?php echo htmlspecialchars($site['site_name']); ?></p>
                </div>
            </div>

        <?php elseif ($sec['type'] === 'header'): ?>
            <?php $style = json_decode($sec['style'] ?? '{}', true); ?>
            <div class="section-header d-flex align-items-center justify-content-between"
                 style="background:<?php echo $style['bg'] ?? '#1a1a2e'; ?>;color:<?php echo $style['color'] ?? '#fff'; ?>;">
                <span class="brand"><?php echo htmlspecialchars($sec['content']); ?></span>
                <nav class="d-flex gap-4">
                    <a href="#" class="text-white-50 text-decoration-none small fw-semibold">Home</a>
                    <a href="#" class="text-white-50 text-decoration-none small fw-semibold">About</a>
                    <a href="#" class="text-white-50 text-decoration-none small fw-semibold">Contact</a>
                </nav>
            </div>

        <?php elseif ($sec['type'] === 'text'): ?>
            <?php $style = json_decode($sec['style'] ?? '{}', true); ?>
            <div class="section-text"
                 style="text-align:<?php echo $style['text_align'] ?? 'left'; ?>;
                        font-size:<?php echo $style['font_size'] ?? '16px'; ?>;
                        color:<?php echo $style['color'] ?? '#000'; ?>;
                        font-weight:<?php echo $style['font_weight'] ?? 'normal'; ?>;
                        font-family:<?php echo $style['font_family'] ?? 'Arial,sans-serif'; ?>;">
                <?php echo nl2br(htmlspecialchars($sec['content'])); ?>
            </div>

        <?php elseif ($sec['type'] === 'image'): ?>
            <?php
                $imgStyle   = json_decode($sec['style'] ?? '{}', true) ?: [];
                $imgW       = !empty($imgStyle['img_width'])  ? (int)$imgStyle['img_width']  : null;
                $imgH       = !empty($imgStyle['img_height']) ? (int)$imgStyle['img_height'] : null;
                $imgX       = isset($imgStyle['img_x']) ? (int)$imgStyle['img_x'] : 20;
                $imgY       = isset($imgStyle['img_y']) ? (int)$imgStyle['img_y'] : 20;
                $containerH = max(200, $imgY + ($imgH ?: 300) + 20);
                $iStr       = "position:absolute;left:{$imgX}px;top:{$imgY}px;";
                if ($imgW) $iStr .= "width:{$imgW}px;";
                if ($imgH) $iStr .= "height:{$imgH}px;";
            ?>
            <div class="section-image" style="min-height:<?php echo $containerH; ?>px;">
                <?php if (!empty($sec['content'])): ?>
                    <img src="<?php echo htmlspecialchars($sec['content']); ?>" alt="Image" style="<?php echo $iStr; ?>">
                <?php else: ?>
                    <div class="text-center py-5 text-muted fs-1">🖼️</div>
                <?php endif; ?>
            </div>

        <?php elseif ($sec['type'] === 'divider'): ?>
            <div class="section-divider"><hr></div>

        <?php elseif ($sec['type'] === 'footer'): ?>
            <div class="section-footer">
                <strong><?php echo htmlspecialchars($sec['content']); ?></strong>
                Powered by PageCraft
            </div>
        <?php endif; ?>

    <?php endwhile; ?>
<?php else: ?>
    <div class="empty-preview">
        <div style="font-size:80px;">🎨</div>
        <h2 class="fw-bold text-dark mt-3">This website has no content yet!</h2>
        <p class="mb-4">Go to the editor and start adding sections to build your website.</p>
        <a href="editor.php?site_id=<?php echo $site_id; ?>"
           class="btn fw-bold text-white px-4 py-2"
           style="background:linear-gradient(135deg,#6c3afc,#e040fb);border:none;border-radius:12px;">
            ✏️ Start Building
        </a>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
