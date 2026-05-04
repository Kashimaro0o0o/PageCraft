<?php
include "../config/db.php";

$ip        = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$stmt = $conn->prepare("INSERT INTO visitor_logs (site_id, ip_address, user_agent) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $site_id, $ip, $userAgent);
$stmt->execute();
$stmt->close();

$site_id = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;
if ($site_id <= 0) { header("Location: dashboard.php"); exit(); }

// Get site info
$site = $conn->query("SELECT * FROM sites WHERE id = $site_id")->fetch_assoc();
if (!$site) { header("Location: dashboard.php"); exit(); }

// Get page
$page = $conn->query("SELECT * FROM pages WHERE site_id = $site_id")->fetch_assoc();
if (!$page) { header("Location: dashboard.php"); exit(); }

$page_id = $page['id'];

// Get sections
$sections = $conn->query("SELECT * FROM sections WHERE page_id = $page_id AND is_archived = 0 ORDER BY position ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site['site_name']); ?></title>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #fff;
            color: #1a1a2e;
        }

        /* ── PREVIEW TOOLBAR ── */
        .preview-toolbar {
            background: #1a1a2e;
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .preview-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: rgba(255,255,255,0.7);
            font-weight: 600;
        }

        .preview-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #22c55e;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .preview-site-name {
            color: #fff;
            font-weight: 700;
            font-size: 14px;
        }

        .preview-toolbar-right { display: flex; gap: 10px; }

        .toolbar-btn {
            padding: 7px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-back-editor {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }

        .btn-back-editor:hover {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }

        .btn-back-dashboard {
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff;
            box-shadow: 0 2px 8px rgba(108,58,252,0.3);
        }

        .btn-back-dashboard:hover {
            transform: translateY(-1px);
            color: #fff;
        }

        /* ── SECTIONS ── */

        /* Hero */
        .section-hero {
            background: linear-gradient(135deg, #6c3afc 0%, #e040fb 50%, #ff6b6b 100%);
            color: #fff;
            padding: 100px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .section-hero::before {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            top: -200px; left: -100px;
        }

        .section-hero::after {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            bottom: -100px; right: -80px;
        }

        .section-hero h1 {
            font-size: 56px;
            font-weight: 900;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
            letter-spacing: -1px;
        }

        .section-hero p {
            font-size: 20px;
            opacity: 0.85;
            position: relative;
            z-index: 1;
        }

        /* Header */
        .section-header {
            background: #1a1a2e;
            color: #fff;
            padding: 20px 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-header .brand {
            font-size: 24px;
            font-weight: 900;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-header nav { display: flex; gap: 24px; }
        .section-header nav a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s;
        }
        .section-header nav a:hover { color: #fff; }

        /* Text */
        .section-text {
            max-width: 800px;
            margin: 0 auto;
            padding: 60px 40px;
            font-size: 17px;
            line-height: 1.8;
            color: #374151;
        }

        /* Image */
        .section-image {
            padding: 40px;
            text-align: center;
            background: #f8f9ff;
        }

        .section-image img {
            max-width: 100%;
            max-height: 500px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        }

        .section-image .img-placeholder {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 600px;
            height: 300px;
            background: linear-gradient(135deg, #f3f4f6, #e9edf5);
            border-radius: 16px;
            font-size: 64px;
            color: #9ca3af;
        }

        /* Divider */
        .section-divider { padding: 10px 48px; }
        .section-divider hr {
            border: none;
            height: 3px;
            background: linear-gradient(135deg, #6c3afc, #e040fb, #ff6b6b);
            border-radius: 999px;
        }

        /* Footer */
        .section-footer {
            background: #1a1a2e;
            color: rgba(255,255,255,0.6);
            padding: 32px 48px;
            text-align: center;
            font-size: 14px;
        }

        .section-footer strong {
            display: block;
            font-size: 20px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
        }

        /* Empty state */
        .empty-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            text-align: center;
            color: #9ca3af;
            padding: 40px;
        }

        .empty-preview .icon { font-size: 80px; margin-bottom: 20px; }
        .empty-preview h2 { font-size: 28px; font-weight: 800; color: #374151; margin-bottom: 10px; }
        .empty-preview p { font-size: 16px; margin-bottom: 24px; }

        .go-edit-btn {
            padding: 14px 28px;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff;
            border-radius: 14px;
            font-weight: 700;
            font-size: 15px;
            text-decoration: none;
            box-shadow: 0 4px 16px rgba(108,58,252,0.3);
            transition: all 0.2s;
        }

        .go-edit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(108,58,252,0.4);
            color: #fff;
        }
    </style>
</head>
<body>

<!-- Preview Toolbar -->
<div class="preview-toolbar">
    <div class="preview-label">
        <div class="preview-dot"></div>
        <span>Previewing:</span>
        <span class="preview-site-name"><?php echo htmlspecialchars($site['site_name']); ?></span>
    </div>
    <div class="preview-toolbar-right">
        <a href="editor.php?site_id=<?php echo $site_id; ?>" class="toolbar-btn btn-back-editor">✏️ Back to Editor</a>
        <a href="dashboard.php" class="toolbar-btn btn-back-dashboard">🏠 Dashboard</a>
    </div>
</div>

<!-- Render Sections -->
<?php if ($sections && $sections->num_rows > 0): ?>
    <?php while ($sec = $sections->fetch_assoc()): ?>

        <?php if ($sec['type'] === 'hero'): ?>
            <div class="section-hero">
                <h1><?php echo htmlspecialchars($sec['content']); ?></h1>
                <p>Welcome to <?php echo htmlspecialchars($site['site_name']); ?></p>
            </div>

        <?php elseif ($sec['type'] === 'header'): ?>
            <div class="section-header">
                <span class="brand"><?php echo htmlspecialchars($sec['content']); ?></span>
                <nav>
                    <a href="#">Home</a>
                    <a href="#">About</a>
                    <a href="#">Contact</a>
                </nav>
            </div>

        <?php elseif ($sec['type'] === 'text'): ?>
            <div class="section-text">
                <?php echo nl2br(htmlspecialchars($sec['content'])); ?>
            </div>

        <?php elseif ($sec['type'] === 'image'): ?>
            <div class="section-image">
                <?php if (!empty($sec['content'])): ?>
                    <img src="<?php echo htmlspecialchars($sec['content']); ?>" alt="Image">
                <?php else: ?>
                    <div class="img-placeholder">🖼️</div>
                <?php endif; ?>
            </div>

        <?php elseif ($sec['type'] === 'divider'): ?>
            <div class="section-divider">
                <hr>
            </div>

        <?php elseif ($sec['type'] === 'footer'): ?>
            <div class="section-footer">
                <strong><?php echo htmlspecialchars($sec['content']); ?></strong>
                Powered by PageCraft
            </div>

        <?php endif; ?>

    <?php endwhile; ?>

<?php else: ?>
    <div class="empty-preview">
        <div class="icon">🎨</div>
        <h2>This website has no content yet!</h2>
        <p>Go to the editor and start adding sections to build your website.</p>
        <a href="editor.php?site_id=<?php echo $site_id; ?>" class="go-edit-btn">✏️ Start Building</a>
    </div>
<?php endif; ?>

</body>
</html>