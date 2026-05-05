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
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --accent: #6c3afc; --accent2: #e040fb; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: #0d0d14;
            color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.25;
            pointer-events: none;
        }
        .blob-1 { width: 500px; height: 500px; background: var(--accent); top: -150px; left: -100px; animation: drift 12s ease-in-out infinite alternate; }
        .blob-2 { width: 400px; height: 400px; background: var(--accent2); bottom: -100px; right: -80px; animation: drift 10s ease-in-out infinite alternate-reverse; }
        .blob-3 { width: 250px; height: 250px; background: #ff6b6b; top: 50%; left: 60%; animation: drift 8s ease-in-out infinite alternate; }
        @keyframes drift { from { transform: translate(0,0) scale(1); } to { transform: translate(30px,-40px) scale(1.08); } }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: linear-gradient(rgba(108,58,252,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(108,58,252,0.06) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }
        .card {
            position: relative;
            z-index: 10;
            text-align: center;
            max-width: 520px;
            padding: 0 24px;
            animation: rise 0.8s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        @keyframes rise { from { opacity: 0; transform: translateY(32px); } to { opacity: 1; transform: translateY(0); } }
        .lock-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 88px; height: 88px;
            background: linear-gradient(135deg, rgba(108,58,252,0.15), rgba(224,64,251,0.15));
            border: 1px solid rgba(108,58,252,0.3);
            border-radius: 28px;
            margin-bottom: 32px;
            font-size: 40px;
            box-shadow: 0 0 40px rgba(108,58,252,0.2), inset 0 1px 0 rgba(255,255,255,0.1);
        }
        .tag {
            display: inline-block;
            padding: 6px 14px;
            border: 1px solid rgba(108,58,252,0.4);
            background: rgba(108,58,252,0.1);
            color: #a78bfa;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(36px, 6vw, 52px);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #fff 30%, rgba(255,255,255,0.5));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        p { font-size: 17px; line-height: 1.7; color: rgba(255,255,255,0.5); margin-bottom: 40px; }
        p strong { color: rgba(255,255,255,0.85); font-weight: 600; }
        .actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn-primary {
            padding: 14px 28px;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff;
            border-radius: 14px;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            box-shadow: 0 4px 20px rgba(108,58,252,0.4);
            transition: all 0.2s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(108,58,252,0.5); color: #fff; }
        .btn-ghost {
            padding: 14px 28px;
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.7);
            border-radius: 14px;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.2s;
        }
        .btn-ghost:hover { background: rgba(255,255,255,0.1); color: #fff; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="card">
        <div class="lock-wrap">🔒</div>
        <div class="tag">Coming Soon</div>
        <h1>Not Published Yet</h1>
        <p><strong><?php echo htmlspecialchars($site['site_name']); ?></strong> is still being built.<br>This page will go live once the owner publishes it.</p>
        <div class="actions">
            <a href="dashboard.php" class="btn-primary">🏠 Go to Dashboard</a>
            <a href="editor.php?site_id=<?php echo $site_id; ?>" class="btn-ghost">✏️ Open Editor</a>
        </div>
    </div>
</body>
</html>
<?php
    exit();
}

$page = $conn->query("SELECT * FROM pages WHERE site_id = $site_id")->fetch_assoc();
if (!$page) { header("Location: dashboard.php"); exit(); }
$page_id = $page['id'];
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
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #fff; color: #1a1a2e; }
        .preview-toolbar { background: #1a1a2e; padding: 10px 24px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 999; }
        .preview-label { display: flex; align-items: center; gap: 10px; font-size: 13px; color: rgba(255,255,255,0.7); font-weight: 600; }
        .preview-dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        .preview-site-name { color: #fff; font-weight: 700; font-size: 14px; }
        .preview-toolbar-right { display: flex; gap: 10px; }
        .toolbar-btn { padding: 7px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: all 0.2s; }
        .btn-back-editor { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-back-editor:hover { background: rgba(255,255,255,0.2); color: #fff; }
        .btn-back-dashboard { background: linear-gradient(135deg, #6c3afc, #e040fb); color: #fff; box-shadow: 0 2px 8px rgba(108,58,252,0.3); }
        .btn-back-dashboard:hover { transform: translateY(-1px); color: #fff; }
        .section-hero { background: linear-gradient(135deg, #6c3afc 0%, #e040fb 50%, #ff6b6b 100%); color: #fff; padding: 100px 40px; text-align: center; position: relative; overflow: hidden; }
        .section-hero::before { content: ''; position: absolute; width: 600px; height: 600px; background: rgba(255,255,255,0.05); border-radius: 50%; top: -200px; left: -100px; }
        .section-hero::after { content: ''; position: absolute; width: 400px; height: 400px; background: rgba(255,255,255,0.05); border-radius: 50%; bottom: -100px; right: -80px; }
        .section-hero h1 { font-size: 56px; font-weight: 900; margin-bottom: 16px; position: relative; z-index: 1; letter-spacing: -1px; }
        .section-hero p { font-size: 20px; opacity: 0.85; position: relative; z-index: 1; }
        .section-header { background: #1a1a2e; color: #fff; padding: 20px 48px; display: flex; align-items: center; justify-content: space-between; }
        .section-header .brand { font-size: 24px; font-weight: 900; background: linear-gradient(135deg, #6c3afc, #e040fb); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .section-header nav { display: flex; gap: 24px; }
        .section-header nav a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; font-weight: 600; transition: color 0.2s; }
        .section-header nav a:hover { color: #fff; }
        .section-text { max-width: 800px; margin: 0 auto; padding: 60px 40px; font-size: 17px; line-height: 1.8; color: #374151; }
        .section-image { padding: 40px; text-align: center; background: #f8f9ff; }
        .section-image img { max-width: 100%; max-height: 500px; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); }
        .section-image .img-placeholder { display: inline-flex; align-items: center; justify-content: center; width: 100%; max-width: 600px; height: 300px; background: linear-gradient(135deg, #f3f4f6, #e9edf5); border-radius: 16px; font-size: 64px; color: #9ca3af; }
        .section-divider { padding: 10px 48px; }
        .section-divider hr { border: none; height: 3px; background: linear-gradient(135deg, #6c3afc, #e040fb, #ff6b6b); border-radius: 999px; }
        .section-footer { background: #1a1a2e; color: rgba(255,255,255,0.6); padding: 32px 48px; text-align: center; font-size: 14px; }
        .section-footer strong { display: block; font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 8px; }
        .empty-preview { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 60vh; text-align: center; color: #9ca3af; padding: 40px; }
        .empty-preview .icon { font-size: 80px; margin-bottom: 20px; }
        .empty-preview h2 { font-size: 28px; font-weight: 800; color: #374151; margin-bottom: 10px; }
        .empty-preview p { font-size: 16px; margin-bottom: 24px; }
        .go-edit-btn { padding: 14px 28px; background: linear-gradient(135deg, #6c3afc, #e040fb); color: #fff; border-radius: 14px; font-weight: 700; font-size: 15px; text-decoration: none; box-shadow: 0 4px 16px rgba(108,58,252,0.3); transition: all 0.2s; }
        .go-edit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(108,58,252,0.4); color: #fff; }
    </style>
</head>
<body>
<?php if ($isPreview): ?>
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
<?php endif; ?>

<?php if ($sections && $sections->num_rows > 0): ?>
    <?php while ($sec = $sections->fetch_assoc()): ?>
        <?php if ($sec['type'] === 'hero'): ?>
            <div class="section-hero">
                <h1><?php echo htmlspecialchars($sec['content']); ?></h1>
                <p>Welcome to <?php echo htmlspecialchars($site['site_name']); ?></p>
            </div>
        <?php elseif ($sec['type'] === 'header'): ?>
            <?php $style = json_decode($sec['style'] ?? '{}', true); ?>
            <div class="section-header" style="background: <?php echo $style['bg'] ?? '#1a1a2e'; ?>; color: <?php echo $style['color'] ?? '#ffffff'; ?>;">
                <span class="brand"><?php echo htmlspecialchars($sec['content']); ?></span>
                <nav><a href="#">Home</a><a href="#">About</a><a href="#">Contact</a></nav>
            </div>
        <?php elseif ($sec['type'] === 'text'): ?>
            <?php $style = json_decode($sec['style'] ?? '{}', true); ?>
            <div class="section-text" style="text-align: <?php echo $style['text_align'] ?? 'left'; ?>; font-size: <?php echo $style['font_size'] ?? '16px'; ?>; color: <?php echo $style['color'] ?? '#000'; ?>; font-weight: <?php echo $style['font_weight'] ?? 'normal'; ?>; font-family: <?php echo $style['font_family'] ?? 'Arial, sans-serif'; ?>;">
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
        <div class="icon">🎨</div>
        <h2>This website has no content yet!</h2>
        <p>Go to the editor and start adding sections to build your website.</p>
        <a href="editor.php?site_id=<?php echo $site_id; ?>" class="go-edit-btn">✏️ Start Building</a>
    </div>
<?php endif; ?>
</body>
</html>
