<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";

$username = $_SESSION['username'] ?? 'Admin';
$site_id  = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;

if ($site_id <= 0) {
    header("Location: dashboard.php"); exit();
}

$site = $conn->query("SELECT * FROM sites WHERE id = $site_id")->fetch_assoc();
if (!$site) { header("Location: dashboard.php"); exit(); }

$page = $conn->query("SELECT * FROM pages WHERE site_id = $site_id")->fetch_assoc();
if (!$page) {
    $conn->query("INSERT INTO pages (site_id, title, slug) VALUES ($site_id, 'Home', 'home')");
    $page = $conn->query("SELECT * FROM pages WHERE site_id = $site_id")->fetch_assoc();
}
$page_id = $page['id'];

$sections    = $conn->query("SELECT * FROM sections WHERE page_id = $page_id AND is_archived = 0 ORDER BY position ASC");
$totalSections = $sections ? $sections->num_rows : 0;

// Build site URL for publish popup
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = dirname(dirname($_SERVER['PHP_SELF']));
$siteUrl = $protocol . '://' . $host . $base . '/pages/view.php?site_id=' . $site_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor | <?php echo htmlspecialchars($site['site_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;900&family=Roboto:wght@300;400;700&family=Playfair+Display:wght@400;700;900&family=Montserrat:wght@300;400;600;700;900&family=Lato:wght@300;400;700&family=Open+Sans:wght@300;400;600;700&family=Raleway:wght@300;400;600;700;900&family=Merriweather:wght@300;400;700&family=Nunito:wght@300;400;600;700;900&family=Inter:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500;700&family=Space+Grotesk:wght@300;400;500;600;700&family=Josefin+Sans:wght@300;400;600;700&family=Bebas+Neue&family=Oswald:wght@300;400;600;700&family=Dancing+Script:wght@400;600;700&family=Pacifico&family=Lobster&family=Abril+Fatface&family=Righteous&family=Quicksand:wght@300;400;600;700&family=Exo+2:wght@300;400;600;700&family=Cinzel:wght@400;700;900&family=Comfortaa:wght@300;400;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f6fa;
            color: #1a1a2e;
            user-select: none;
        }

        .topbar {
            height: 65px;
            background: #fff;
            border-bottom: 1px solid #e9edf5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-left { display: flex; align-items: center; gap: 12px; }

        .back-btn {
            padding: 8px 14px;
            background: #f3f4f6;
            color: #374151;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
        }

        .back-btn:hover { background: #e9edf5; }

        .topbar-brand {
            font-size: 22px;
            font-weight: 900;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .site-name-badge {
            background: linear-gradient(135deg, rgba(108,58,252,0.1), rgba(224,64,251,0.1));
            color: #6c3afc;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid rgba(108,58,252,0.2);
        }

        .topbar-right { display: flex; align-items: center; gap: 8px; }

        .preview-btn {
            padding: 10px 18px;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(108,58,252,0.3);
            transition: all 0.2s;
        }

        .preview-btn:hover { transform: translateY(-1px); color: #fff; }

        .settings-btn {
            padding: 10px 18px;
            background: #f3f4f6;
            color: #374151;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .settings-btn:hover { background: #e9edf5; }

        .topbar-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .editor-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            min-height: calc(100vh - 65px);
        }

        /* ─── LEFT PANEL ─── */
        .left-panel {
            background: #fff;
            border-right: 1px solid #e9edf5;
            padding: 20px;
            overflow-y: auto;
            height: calc(100vh - 65px);
            position: sticky;
            top: 65px;
        }

        .panel-title {
            font-size: 11px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        /* ─── SECTION TYPE TILES (draggable) ─── */
        .section-types {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 20px;
        }

        .type-btn {
            padding: 12px 8px;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            background: #fafafa;
            cursor: grab;
            text-align: center;
            transition: all 0.2s;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            position: relative;
        }

        .type-btn:hover {
            border-color: #6c3afc;
            border-style: solid;
            background: rgba(108,58,252,0.05);
            color: #6c3afc;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108,58,252,0.15);
        }

        .type-btn.dragging-source {
            opacity: 0.4;
            transform: scale(0.95);
        }

        .drag-hint {
            font-size: 9px;
            color: #9ca3af;
            margin-top: 3px;
        }

        .type-icon { font-size: 22px; display: block; margin-bottom: 4px; }

        .add-form {
            background: #f8f9ff;
            border-radius: 14px;
            padding: 16px;
            border: 1px solid #e0e7ff;
        }

        .add-form-title { font-size: 14px; font-weight: 700; color: #1a1a2e; margin-bottom: 12px; }

        .form-group { margin-bottom: 12px; }

        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            background: #fff;
            transition: all 0.2s;
            font-family: inherit;
        }

        .form-group textarea { min-height: 80px; resize: vertical; }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus { border-color: #6c3afc; box-shadow: 0 0 0 3px rgba(108,58,252,0.1); }

        /* Font preview in select */
        select.font-select option { padding: 4px; }

        .upload-drop-zone {
            border: 2px dashed #c4b5fd;
            border-radius: 12px;
            padding: 20px 12px;
            text-align: center;
            cursor: pointer;
            background: rgba(108,58,252,0.03);
            transition: all 0.2s;
        }
        .upload-drop-zone:hover, .upload-drop-zone.drag-over {
            border-color: #6c3afc;
            background: rgba(108,58,252,0.08);
        }
        .upload-icon { font-size: 28px; margin-bottom: 6px; }
        .upload-text { font-size: 12px; font-weight: 600; color: #6c3afc; margin-bottom: 4px; }
        .upload-hint { font-size: 10px; color: #9ca3af; }

        .add-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(108,58,252,0.25);
        }

        .add-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(108,58,252,0.35); }

        /* ─── CANVAS ─── */
        .canvas-panel { padding: 28px; overflow-y: auto; }

        .canvas-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .canvas-title { font-size: 18px; font-weight: 800; color: #1a1a2e; }
        .section-count { font-size: 12px; color: #9ca3af; font-weight: 600; }

        .canvas {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            overflow: visible;
            min-height: 400px;
            border: 1px solid #e9edf5;
            position: relative;
        }

        /* Drop zone indicator when dragging from panel */
        .canvas.panel-drag-over {
            border: 2px dashed #6c3afc;
            background: rgba(108,58,252,0.02);
        }

        .canvas-drop-placeholder {
            display: none;
            padding: 20px;
            text-align: center;
            color: #6c3afc;
            font-weight: 700;
            font-size: 14px;
            background: rgba(108,58,252,0.05);
            border: 2px dashed #6c3afc;
            border-radius: 12px;
            margin: 8px;
        }

        .canvas-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 20px;
            text-align: center;
            color: #9ca3af;
        }

        .canvas-empty-icon { font-size: 64px; margin-bottom: 16px; }
        .canvas-empty h3 { font-size: 20px; font-weight: 700; color: #374151; margin-bottom: 8px; }
        .canvas-empty p { font-size: 14px; }

        /* ─── SECTION BLOCKS ─── */
        .section-block {
            border-bottom: 1px solid #f0f0f0;
            position: relative;
            transition: all 0.15s;
            cursor: default;
        }

        .section-block:last-child { border-bottom: none; }

        /* Drag handle */
        .drag-handle {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: grab;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 10;
            color: #9ca3af;
            font-size: 16px;
            background: linear-gradient(90deg, rgba(255,255,255,0.9), transparent);
        }

        .section-block:hover .drag-handle { opacity: 1; }

        .section-block.dragging {
            opacity: 0.5;
            border: 2px dashed #6c3afc;
        }

        .section-block.drag-over-top::before {
            content: '';
            position: absolute;
            top: -2px;
            left: 0; right: 0;
            height: 3px;
            background: #6c3afc;
            border-radius: 2px;
            z-index: 20;
        }

        .section-block.drag-over-bottom::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0; right: 0;
            height: 3px;
            background: #6c3afc;
            border-radius: 2px;
            z-index: 20;
        }

        /* Resize handle (non-image sections) */
        .resize-handle {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 8px;
            cursor: ns-resize;
            opacity: 0;
            transition: opacity 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 15;
        }

        .resize-handle::after {
            content: '⠿';
            font-size: 12px;
            color: #6c3afc;
            background: rgba(108,58,252,0.1);
            border-radius: 4px;
            padding: 1px 6px;
            border: 1px solid rgba(108,58,252,0.3);
        }

        .section-block:hover .resize-handle { opacity: 1; }
        .section-block.resizing { cursor: ns-resize; }

        /* ─── IMAGE RESIZER (8-handle, always freely positionable) ─── */
        .img-resizer-wrap {
            position: absolute;
            display: inline-block;
            line-height: 0;
            cursor: move;
            z-index: 10;
        }

        .img-resizer-wrap img { display: block; border-radius: 6px; }

        /* Show handles only when selected */
        .img-resizer-wrap .img-handle {
            display: none;
            position: absolute;
            width: 10px; height: 10px;
            background: #fff;
            border: 2px solid #6c3afc;
            border-radius: 2px;
            z-index: 20;
        }

        .img-resizer-wrap.selected { outline: 2px solid #6c3afc; outline-offset: 2px; }
        .img-resizer-wrap.selected .img-handle { display: block; }

        /* 8 handle positions */
        .img-handle.nw { top:-5px;  left:-5px;  cursor:nw-resize; }
        .img-handle.n  { top:-5px;  left:50%; transform:translateX(-50%); cursor:n-resize; }
        .img-handle.ne { top:-5px;  right:-5px; cursor:ne-resize; }
        .img-handle.e  { top:50%;   right:-5px; transform:translateY(-50%); cursor:e-resize; }
        .img-handle.se { bottom:-5px; right:-5px; cursor:se-resize; }
        .img-handle.s  { bottom:-5px; left:50%; transform:translateX(-50%); cursor:s-resize; }
        .img-handle.sw { bottom:-5px; left:-5px; cursor:sw-resize; }
        .img-handle.w  { top:50%;   left:-5px;  transform:translateY(-50%); cursor:w-resize; }

        /* Wrap toolbar */
        .img-wrap-toolbar {
            display: none;
            position: absolute;
            top: -112px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 10px;
            gap: 6px;
            z-index: 100;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            flex-direction: column;
            min-width: 220px;
        }
        .img-resizer-wrap.selected .img-wrap-toolbar { display: flex; }

        .wrap-toolbar-label {
            font-size: 10px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0 2px 4px;
            border-bottom: 1px solid #f0f0f0;
        }

        .wrap-toolbar-row {
            display: flex;
            gap: 4px;
        }

        .wrap-btn {
            background: #f8f9ff;
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            padding: 6px 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            transition: all 0.15s;
            flex: 1;
        }
        .wrap-btn:hover { background: #eef2ff; border-color: #c4b5fd; }
        .wrap-btn.active { background: #eef2ff; border-color: #6c3afc; }

        .wrap-btn-icon {
            width: 32px;
            height: 24px;
            position: relative;
        }

        .wrap-btn-label {
            font-size: 9px;
            font-weight: 700;
            color: #6b7280;
            text-align: center;
            white-space: nowrap;
        }
        .wrap-btn.active .wrap-btn-label { color: #6c3afc; }

        /* ─── SECTION RENDERINGS ─── */
        .section-hero {
            background: linear-gradient(135deg, #6c3afc 0%, #e040fb 50%, #ff6b6b 100%);
            color: #fff;
            padding: 48px 32px;
            text-align: center;
            min-height: 120px;
        }

        .section-hero h2 { font-size: 36px; font-weight: 800; margin-bottom: 12px; }
        .section-hero p { font-size: 16px; opacity: 0.9; }

        .section-header-block {
            background: #1a1a2e;
            color: #fff;
            padding: 20px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 60px;
        }

        .section-header-block span { font-size: 22px; font-weight: 800; }

        .section-footer-block {
            background: #f3f4f6;
            padding: 20px 32px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            min-height: 60px;
        }

        .section-text-block { padding: 24px 32px; font-size: 15px; line-height: 1.7; color: #374151; min-height: 60px; }

        .section-image-block { padding: 20px 32px; min-height: 200px; position: relative; overflow: visible; }
        .section-image-block img { max-width: 100%; border-radius: 12px; display: block; }
        .section-image-block .img-placeholder {
            background: linear-gradient(135deg, #f3f4f6, #e9edf5);
            height: 160px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #9ca3af;
        }

        .section-controls {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            gap: 5px;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 10;
            background: rgba(255,255,255,0.95);
            border-radius: 10px;
            padding: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .section-block:hover .section-controls { opacity: 1; }

        .ctrl-btn {
            width: 30px; height: 30px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .ctrl-up   { background: #eef2ff; color: #4f46e5; }
        .ctrl-down { background: #eef2ff; color: #4f46e5; }
        .ctrl-edit { background: #dcfce7; color: #15803d; }
        .ctrl-delete { background: #fee2e2; color: #b91c1c; }
        .ctrl-btn:hover { transform: scale(1.12); }

        /* ─── MODALS ─── */
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
            padding: 32px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.2);
        }

        .modal-title { font-size: 22px; font-weight: 800; margin-bottom: 6px; }
        .modal-sub { font-size: 14px; color: #667085; margin-bottom: 20px; }

        .modal textarea,
        .modal input[type="text"] {
            width: 100%;
            padding: 14px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            outline: none;
            margin-bottom: 16px;
            font-family: inherit;
            transition: all 0.2s;
        }

        .modal textarea { min-height: 120px; resize: vertical; }
        .modal textarea:focus,
        .modal input:focus { border-color: #6c3afc; box-shadow: 0 0 0 3px rgba(108,58,252,0.1); }

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

        /* Publish URL box */
        .publish-url-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8f9ff;
            border: 2px solid #e0e7ff;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .publish-url-text {
            flex: 1;
            font-size: 14px;
            color: #374151;
            word-break: break-all;
            font-family: monospace;
        }

        .copy-btn {
            padding: 8px 14px;
            background: #6c3afc;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
        }

        .copy-btn:hover { background: #5b2de0; }
        .copy-btn.copied { background: #10b981; }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-left">
        <a href="dashboard.php" class="back-btn">← Back</a>
        <div class="topbar-brand">PageCraft</div>
        <span class="site-name-badge">🌐 <?php echo htmlspecialchars($site['site_name']); ?></span>
    </div>
    <div class="topbar-right">
        <a href="logs.php" class="settings-btn">📊 Logs</a>
        <button onclick="openSettings()" class="settings-btn">⚙️ Settings</button>
        <a href="view.php?site_id=<?php echo $site_id; ?>&preview=1" class="preview-btn" target="_blank">👁️ Preview</a>
        <button onclick="doPublish()" class="preview-btn" style="border:none;cursor:pointer;">🚀 Publish</button>
        <div class="topbar-avatar"><?php echo strtoupper($username[0]); ?></div>
    </div>
</header>

<div class="editor-layout">

    <!-- Left Panel -->
    <div class="left-panel">
        <div class="panel-title">Drag to Canvas or Click to Select</div>

        <div class="section-types">
            <div class="type-btn" draggable="true" data-type="text" onclick="selectType('text', this)">
                <span class="type-icon">📝</span>Text
                <div class="drag-hint">drag or click</div>
            </div>
            <div class="type-btn" draggable="true" data-type="image" onclick="selectType('image', this)">
                <span class="type-icon">🖼️</span>Image
                <div class="drag-hint">drag or click</div>
            </div>
            <div class="type-btn" draggable="true" data-type="hero" onclick="selectType('hero', this)">
                <span class="type-icon">🚀</span>Hero
                <div class="drag-hint">drag or click</div>
            </div>
            <div class="type-btn" draggable="true" data-type="header" onclick="selectType('header', this)">
                <span class="type-icon">🔝</span>Header
                <div class="drag-hint">drag or click</div>
            </div>
            <div class="type-btn" draggable="true" data-type="footer" onclick="selectType('footer', this)">
                <span class="type-icon">🔚</span>Footer
                <div class="drag-hint">drag or click</div>
            </div>
            <div class="type-btn" draggable="true" data-type="divider" onclick="selectType('divider', this)">
                <span class="type-icon">➖</span>Divider
                <div class="drag-hint">drag or click</div>
            </div>
            <div class="type-btn" draggable="true" data-type="button" onclick="selectType('button', this)">
                <span class="type-icon">🔗</span>Button
                <div class="drag-hint">drag or click</div>
            </div>
        </div>

        <div class="add-form">
            <div class="add-form-title" id="formTitle">📝 Add Text Section</div>
            <form action="../actions/save_section.php?site_id=<?php echo $site_id; ?>" method="POST" enctype="multipart/form-data" id="sectionForm">
                <input type="hidden" name="page_id" value="<?php echo $page_id; ?>">
                <input type="hidden" name="site_id" value="<?php echo $site_id; ?>">
                <input type="hidden" name="type" id="sectionType" value="text">

                <div class="form-group" id="contentGroup">
                    <label id="contentLabel">Text Content</label>
                    <textarea name="content" id="contentInput" placeholder="Enter your text content here..."></textarea>
                </div>

                <div class="form-group" id="uploadGroup" style="display:none;">
                    <label>Upload Photo</label>
                    <div class="upload-drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
                        <div class="upload-icon">📁</div>
                        <div class="upload-text">Click to browse or drag & drop</div>
                        <div class="upload-hint">JPG, PNG, GIF, WEBP</div>
                    </div>
                    <input type="file" name="image_file" id="fileInput" accept="image/*" style="display:none;">
                    <img id="imagePreview" src="" alt="Preview" style="display:none; max-width:100%; border-radius:10px; margin-top:10px;">
                </div>

                <div id="headerColorOptions" style="display:none;">
                    <div class="form-group">
                        <label>Background Color</label>
                        <input type="color" name="header_bg" id="headerBgInput" value="#1a1a2e" style="width:100%;height:42px;border:1px solid #e5e7eb;border-radius:8px;cursor:pointer;padding:4px;">
                    </div>
                    <div class="form-group">
                        <label>Text Color</label>
                        <input type="color" name="header_color" id="headerColorInput" value="#ffffff" style="width:100%;height:42px;border:1px solid #e5e7eb;border-radius:8px;cursor:pointer;padding:4px;">
                    </div>
                </div>

                <div id="textStyleOptions">
                    <div class="form-group">
                        <label>Text Align</label>
                        <select name="text_align">
                            <option value="left">Left</option>
                            <option value="center">Center</option>
                            <option value="right">Right</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Font Size (px)</label>
                        <input type="number" name="font_size" placeholder="e.g. 24" min="8" max="120">
                    </div>
                    <div class="form-group">
                        <label>Text Color</label>
                        <input type="color" name="color">
                    </div>
                    <div class="form-group">
                        <label>Font Weight</label>
                        <select name="font_weight">
                            <option value="300">Light (300)</option>
                            <option value="normal" selected>Normal (400)</option>
                            <option value="600">Semi-Bold (600)</option>
                            <option value="bold">Bold (700)</option>
                            <option value="900">Black (900)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Font Family</label>
                        <select name="font_family" class="font-select" id="fontFamilySelect">
                            <optgroup label="Sans-Serif">
                                <option value="Arial, sans-serif">Arial</option>
                                <option value="'Segoe UI', sans-serif">Segoe UI</option>
                                <option value="'Inter', sans-serif">Inter</option>
                                <option value="'Poppins', sans-serif">Poppins</option>
                                <option value="'Roboto', sans-serif">Roboto</option>
                                <option value="'Montserrat', sans-serif">Montserrat</option>
                                <option value="'Lato', sans-serif">Lato</option>
                                <option value="'Open Sans', sans-serif">Open Sans</option>
                                <option value="'Raleway', sans-serif">Raleway</option>
                                <option value="'Nunito', sans-serif">Nunito</option>
                                <option value="'DM Sans', sans-serif">DM Sans</option>
                                <option value="'Space Grotesk', sans-serif">Space Grotesk</option>
                                <option value="'Josefin Sans', sans-serif">Josefin Sans</option>
                                <option value="'Quicksand', sans-serif">Quicksand</option>
                                <option value="'Exo 2', sans-serif">Exo 2</option>
                                <option value="'Comfortaa', sans-serif">Comfortaa</option>
                            </optgroup>
                            <optgroup label="Display / Bold">
                                <option value="'Bebas Neue', cursive">Bebas Neue</option>
                                <option value="'Oswald', sans-serif">Oswald</option>
                                <option value="'Abril Fatface', cursive">Abril Fatface</option>
                                <option value="'Righteous', cursive">Righteous</option>
                                <option value="'Orbitron', sans-serif">Orbitron</option>
                            </optgroup>
                            <optgroup label="Serif">
                                <option value="'Playfair Display', serif">Playfair Display</option>
                                <option value="'Merriweather', serif">Merriweather</option>
                                <option value="Georgia, serif">Georgia</option>
                                <option value="'Times New Roman', serif">Times New Roman</option>
                                <option value="'Cinzel', serif">Cinzel</option>
                            </optgroup>
                            <optgroup label="Script / Handwriting">
                                <option value="'Dancing Script', cursive">Dancing Script</option>
                                <option value="'Pacifico', cursive">Pacifico</option>
                                <option value="'Lobster', cursive">Lobster</option>
                            </optgroup>
                            <optgroup label="Monospace">
                                <option value="'Courier New', monospace">Courier New</option>
                                <option value="'Roboto Mono', monospace">Roboto Mono</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <button type="submit" class="add-btn">+ Add Section</button>
            </form>
        </div>
    </div>

    <!-- Canvas -->
    <div class="canvas-panel">
        <div class="canvas-header">
            <div class="canvas-title">Page Canvas</div>
            <div class="section-count" id="sectionCountBadge"><?php echo $totalSections; ?> section<?php echo $totalSections !== 1 ? 's' : ''; ?></div>
        </div>

        <div class="canvas" id="canvas">
            <div class="canvas-drop-placeholder" id="canvasDropPlaceholder">
                ✨ Drop here to add section
            </div>

            <?php
            $sections = $conn->query("SELECT * FROM sections WHERE page_id = $page_id AND is_archived = 0 ORDER BY position ASC");
            ?>

            <?php if ($sections && $sections->num_rows > 0): ?>
                <?php while ($sec = $sections->fetch_assoc()): ?>
                <div class="section-block" data-id="<?php echo $sec['id']; ?>" draggable="true">

                    <div class="drag-handle" title="Drag to reorder">⠿</div>

                    <?php if ($sec['type'] === 'hero'): ?>
                        <div class="section-hero" style="min-height:<?php echo !empty($sec['height']) ? $sec['height'].'px' : ''; ?>">
                            <h2><?php echo htmlspecialchars($sec['content']); ?></h2>
                            <p>Your hero section</p>
                        </div>

                    <?php elseif ($sec['type'] === 'header'): ?>
                        <?php $style = json_decode($sec['style'] ?? '{}', true); ?>
                        <div class="section-header-block live-header"
                             style="background: <?php echo $style['bg'] ?? '#1a1a2e'; ?>;
                                    color: <?php echo $style['color'] ?? '#ffffff'; ?>;
                                    text-align: <?php echo $style['text_align'] ?? 'left'; ?>;
                                    font-size: <?php echo $style['font_size'] ?? '24px'; ?>;
                                    font-weight: <?php echo $style['font_weight'] ?? 'bold'; ?>;
                                    font-family: <?php echo $style['font_family'] ?? 'Arial, sans-serif'; ?>;
                                    min-height:<?php echo !empty($sec['height']) ? $sec['height'].'px' : ''; ?>">
                            <span><?php echo htmlspecialchars($sec['content']); ?></span>
                            <span style="font-size:14px; opacity:0.7;">Navigation</span>
                        </div>

                    <?php elseif ($sec['type'] === 'footer'): ?>
                        <div class="section-footer-block" style="min-height:<?php echo !empty($sec['height']) ? $sec['height'].'px' : ''; ?>">
                            <?php echo htmlspecialchars($sec['content']); ?>
                        </div>

                    <?php elseif ($sec['type'] === 'image'): ?>
                        <?php
                            $imgStyle = json_decode($sec['style'] ?? '{}', true) ?: [];
                            $imgW    = !empty($imgStyle['img_width'])  ? (int)$imgStyle['img_width']  : null;
                            $imgH    = !empty($imgStyle['img_height']) ? (int)$imgStyle['img_height'] : null;
                            $imgX    = isset($imgStyle['img_x']) ? (int)$imgStyle['img_x'] : 20;
                            $imgY    = isset($imgStyle['img_y']) ? (int)$imgStyle['img_y'] : 20;
                            $wrapStyle = "left:{$imgX}px;top:{$imgY}px;";
                            if ($imgW) $wrapStyle .= "width:{$imgW}px;";
                            $imgInline = '';
                            if ($imgW) $imgInline .= "width:{$imgW}px;";
                            if ($imgH) $imgInline .= "height:{$imgH}px;";
                        ?>
                        <div class="section-image-block">
                            <?php if (!empty($sec['content'])): ?>
                            <div class="img-resizer-wrap"
                                 data-sec-id="<?php echo $sec['id']; ?>"
                                 data-img-x="<?php echo $imgX; ?>"
                                 data-img-y="<?php echo $imgY; ?>"
                                 style="<?php echo $wrapStyle; ?>">
                                <!-- 8 resize handles -->
                                <div class="img-handle nw"></div>
                                <div class="img-handle n"></div>
                                <div class="img-handle ne"></div>
                                <div class="img-handle e"></div>
                                <div class="img-handle se"></div>
                                <div class="img-handle s"></div>
                                <div class="img-handle sw"></div>
                                <div class="img-handle w"></div>
                                <img src="<?php echo htmlspecialchars($sec['content']); ?>" alt="Image"
                                     style="<?php echo $imgInline; ?>max-width:100%;">
                            </div>
                            <?php else: ?>
                                <div class="img-placeholder">🖼️</div>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($sec['type'] === 'divider'): ?>
                        <div style="padding: 16px 32px; min-height:<?php echo !empty($sec['height']) ? $sec['height'].'px' : '50px'; ?>; display:flex; align-items:center;">
                            <hr style="flex:1; border:none; border-top:3px solid; border-image:linear-gradient(135deg,#6c3afc,#e040fb) 1;">
                        </div>

                    <?php elseif ($sec['type'] === 'text'): ?>
                        <?php $style = json_decode($sec['style'] ?? '{}', true); ?>
                        <div class="section-text-block"
                             style="text-align: <?php echo $style['text_align'] ?? 'left'; ?>;
                                    font-size: <?php echo $style['font_size'] ?? '16px'; ?>;
                                    color: <?php echo $style['color'] ?? '#000'; ?>;
                                    font-weight: <?php echo $style['font_weight'] ?? 'normal'; ?>;
                                    font-family: <?php echo $style['font_family'] ?? 'Arial, sans-serif'; ?>;
                                    min-height:<?php echo !empty($sec['height']) ? $sec['height'].'px' : ''; ?>">
                            <?php echo nl2br(htmlspecialchars($sec['content'])); ?>
                        </div>

                    <?php elseif ($sec['type'] === 'button'): ?>
                        <?php $style = json_decode($sec['style'] ?? '{}', true); ?>
                        <div style="padding:24px 32px; text-align:<?php echo $style['text_align'] ?? 'center'; ?>; min-height:<?php echo !empty($sec['height']) ? $sec['height'].'px' : '80px'; ?>; display:flex; align-items:center; justify-content:<?php echo ($style['text_align'] ?? 'center') === 'left' ? 'flex-start' : (($style['text_align'] ?? 'center') === 'right' ? 'flex-end' : 'center'); ?>;">
                            <a href="<?php echo htmlspecialchars($style['url'] ?? '#'); ?>"
                               target="_blank"
                               style="display:inline-block;
                                      background:<?php echo $style['bg'] ?? '#6c3afc'; ?>;
                                      color:<?php echo $style['color'] ?? '#ffffff'; ?>;
                                      padding:<?php echo $style['padding'] ?? '14px 32px'; ?>;
                                      border-radius:<?php echo $style['radius'] ?? '12px'; ?>;
                                      font-size:<?php echo $style['font_size'] ?? '16px'; ?>;
                                      font-weight:<?php echo $style['font_weight'] ?? 'bold'; ?>;
                                      font-family:<?php echo $style['font_family'] ?? 'Arial, sans-serif'; ?>;
                                      text-decoration:none;
                                      box-shadow:0 4px 15px rgba(0,0,0,0.15);
                                      transition:all 0.2s;"
                               onmouseover="this.style.opacity='0.85';this.style.transform='translateY(-2px)'"
                               onmouseout="this.style.opacity='1';this.style.transform='translateY(0)'">
                                <?php echo htmlspecialchars($sec['content']); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="resize-handle" data-id="<?php echo $sec['id']; ?>" title="Drag to resize"></div>

                    <!-- Controls -->
                    <div class="section-controls">
                        <button onclick="moveSection(<?php echo $sec['id']; ?>, 'up', this)" class="ctrl-btn ctrl-up" title="Move Up">⬆️</button>
                        <button onclick="moveSection(<?php echo $sec['id']; ?>, 'down', this)" class="ctrl-btn ctrl-down" title="Move Down">⬇️</button>
                        <button onclick="openEditModal(<?php echo $sec['id']; ?>, '<?php echo addslashes(htmlspecialchars($sec['content'])); ?>', '<?php echo $sec['type']; ?>', <?php echo htmlspecialchars($sec['style'] ?? '{}'); ?>)" class="ctrl-btn ctrl-edit" title="Edit">✏️</button>
                        <a href="../actions/archive.php?id=<?php echo $sec['id']; ?>&site_id=<?php echo $site_id; ?>"
                           class="ctrl-btn ctrl-delete"
                           onclick="return confirm('Remove this section?');" title="Delete">🗑️</a>
                    </div>
                </div>
                <?php endwhile; ?>

            <?php else: ?>
                <div class="canvas-empty" id="canvasEmpty">
                    <div class="canvas-empty-icon">🎨</div>
                    <h3>Your canvas is empty!</h3>
                    <p>Drag sections from the left panel or click to select type, then click Add Section</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Section Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal" style="max-width:580px; max-height:90vh; overflow-y:auto;">
        <div class="modal-title">✏️ Edit Section</div>
        <div class="modal-sub" id="editModalSub">Update your section content</div>
        <form action="../actions/edit_section.php" method="POST" enctype="multipart/form-data" id="editForm">
            <input type="hidden" name="id" id="editSectionId">
            <input type="hidden" name="site_id" value="<?php echo $site_id; ?>">
            <input type="hidden" name="section_type" id="editSectionType">

            <!-- Text/Hero/Footer/Divider content -->
            <div id="editContentGroup" style="margin-bottom:14px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;" id="editContentLabel">Content</label>
                <textarea name="content" id="editSectionContent" style="width:100%;padding:12px 14px;border:1px solid #e5e7eb;border-radius:10px;font-size:14px;min-height:90px;resize:vertical;outline:none;font-family:inherit;"></textarea>
            </div>

            <!-- Image edit fields -->
            <div id="editImageGroup" style="display:none; margin-bottom:14px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Current Image</label>
                <img id="editImagePreview" src="" alt="Current image" style="max-width:100%;border-radius:10px;margin-bottom:12px;display:none;">
                <div id="editImgPlaceholder" style="background:#f3f4f6;border-radius:10px;height:80px;display:flex;align-items:center;justify-content:center;font-size:32px;margin-bottom:12px;">🖼️</div>

                <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Replace with New Photo</label>
                <div class="upload-drop-zone" id="editDropZone" onclick="document.getElementById('editFileInput').click()" style="border:2px dashed #c4b5fd;border-radius:12px;padding:20px 12px;text-align:center;cursor:pointer;background:rgba(108,58,252,0.03);">
                    <div style="font-size:28px;margin-bottom:6px;" id="editUploadIcon">📁</div>
                    <div style="font-size:12px;font-weight:600;color:#6c3afc;margin-bottom:4px;" id="editUploadText">Click to browse or drag & drop</div>
                    <div style="font-size:10px;color:#9ca3af;">JPG, PNG, GIF, WEBP</div>
                </div>
                <input type="file" name="image_file" id="editFileInput" accept="image/*" style="display:none;">
                <img id="editNewImagePreview" src="" alt="New preview" style="display:none;max-width:100%;border-radius:10px;margin-top:10px;">

                <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-top:12px;margin-bottom:6px;">Or use Image URL</label>
                <input type="text" name="content" id="editImageUrl" placeholder="https://example.com/image.jpg" style="width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
            </div>

            <!-- Text style fields -->
            <div id="editTextStyles">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Text Align</label>
                        <select name="text_align" id="editTextAlign" style="width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                            <option value="left">Left</option>
                            <option value="center">Center</option>
                            <option value="right">Right</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Font Size (px)</label>
                        <input type="number" name="font_size" id="editFontSize" placeholder="e.g. 16" min="8" max="120" style="width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Text Color</label>
                        <input type="color" name="color" id="editColor" style="width:100%;height:40px;border:1px solid #e5e7eb;border-radius:8px;cursor:pointer;padding:3px;">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Font Weight</label>
                        <select name="font_weight" id="editFontWeight" style="width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                            <option value="300">Light (300)</option>
                            <option value="normal">Normal (400)</option>
                            <option value="600">Semi-Bold (600)</option>
                            <option value="bold">Bold (700)</option>
                            <option value="900">Black (900)</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Font Family</label>
                    <select name="font_family" id="editFontFamily" style="width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                        <optgroup label="Sans-Serif">
                            <option value="Arial, sans-serif">Arial</option>
                            <option value="'Segoe UI', sans-serif">Segoe UI</option>
                            <option value="'Inter', sans-serif">Inter</option>
                            <option value="'Poppins', sans-serif">Poppins</option>
                            <option value="'Roboto', sans-serif">Roboto</option>
                            <option value="'Montserrat', sans-serif">Montserrat</option>
                            <option value="'Lato', sans-serif">Lato</option>
                            <option value="'Open Sans', sans-serif">Open Sans</option>
                            <option value="'Raleway', sans-serif">Raleway</option>
                            <option value="'Nunito', sans-serif">Nunito</option>
                            <option value="'DM Sans', sans-serif">DM Sans</option>
                            <option value="'Space Grotesk', sans-serif">Space Grotesk</option>
                            <option value="'Josefin Sans', sans-serif">Josefin Sans</option>
                            <option value="'Quicksand', sans-serif">Quicksand</option>
                            <option value="'Exo 2', sans-serif">Exo 2</option>
                            <option value="'Comfortaa', sans-serif">Comfortaa</option>
                        </optgroup>
                        <optgroup label="Display / Bold">
                            <option value="'Bebas Neue', cursive">Bebas Neue</option>
                            <option value="'Oswald', sans-serif">Oswald</option>
                            <option value="'Abril Fatface', cursive">Abril Fatface</option>
                            <option value="'Righteous', cursive">Righteous</option>
                            <option value="'Orbitron', sans-serif">Orbitron</option>
                        </optgroup>
                        <optgroup label="Serif">
                            <option value="'Playfair Display', serif">Playfair Display</option>
                            <option value="'Merriweather', serif">Merriweather</option>
                            <option value="Georgia, serif">Georgia</option>
                            <option value="'Times New Roman', serif">Times New Roman</option>
                            <option value="'Cinzel', serif">Cinzel</option>
                        </optgroup>
                        <optgroup label="Script / Handwriting">
                            <option value="'Dancing Script', cursive">Dancing Script</option>
                            <option value="'Pacifico', cursive">Pacifico</option>
                            <option value="'Lobster', cursive">Lobster</option>
                        </optgroup>
                        <optgroup label="Monospace">
                            <option value="'Courier New', monospace">Courier New</option>
                            <option value="'Roboto Mono', monospace">Roboto Mono</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <!-- Button-specific fields -->
            <div id="editButtonStyles" style="display:none;">
                <div style="margin-bottom:12px;">
                    <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Link URL</label>
                    <input type="text" name="btn_url" id="editBtnUrl" placeholder="https://facebook.com/yourpage" style="width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Button Color</label>
                        <input type="color" name="btn_bg" id="editBtnBg" value="#6c3afc" style="width:100%;height:40px;border:1px solid #e5e7eb;border-radius:8px;cursor:pointer;padding:3px;">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Text Color</label>
                        <input type="color" name="btn_color" id="editBtnColor" value="#ffffff" style="width:100%;height:40px;border:1px solid #e5e7eb;border-radius:8px;cursor:pointer;padding:3px;">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Alignment</label>
                        <select name="btn_align" id="editBtnAlign" style="width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                            <option value="center">Center</option>
                            <option value="left">Left</option>
                            <option value="right">Right</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Font Size (px)</label>
                        <input type="number" name="btn_font_size" id="editBtnFontSize" value="16" min="10" max="40" style="width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Border Radius</label>
                        <select name="btn_radius" id="editBtnRadius" style="width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                            <option value="4px">Sharp</option>
                            <option value="8px">Slightly Rounded</option>
                            <option value="12px" selected>Rounded</option>
                            <option value="999px">Pill</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Font Weight</label>
                        <select name="btn_font_weight" id="editBtnWeight" style="width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                            <option value="normal">Normal</option>
                            <option value="600">Semi-Bold</option>
                            <option value="bold" selected>Bold</option>
                            <option value="900">Black</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Header-specific style fields -->
            <div id="editHeaderStyles" style="display:none;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Background Color</label>
                        <input type="color" name="bg_color" id="editBgColor" value="#1a1a2e" style="width:100%;height:40px;border:1px solid #e5e7eb;border-radius:8px;cursor:pointer;padding:3px;">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Text Color</label>
                        <input type="color" name="text_color" id="editHeaderTextColor" value="#ffffff" style="width:100%;height:40px;border:1px solid #e5e7eb;border-radius:8px;cursor:pointer;padding:3px;">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Text Align</label>
                        <select name="text_align" id="editHeaderAlign" style="width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                            <option value="left">Left</option>
                            <option value="center">Center</option>
                            <option value="right">Right</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Font Size (px)</label>
                        <input type="number" name="font_size" id="editHeaderFontSize" placeholder="e.g. 24" style="width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Font Weight</label>
                        <select name="font_weight" id="editHeaderWeight" style="width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                            <option value="300">Light (300)</option>
                            <option value="normal">Normal (400)</option>
                            <option value="600">Semi-Bold (600)</option>
                            <option value="bold">Bold (700)</option>
                            <option value="900">Black (900)</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Font Family</label>
                        <select name="font_family" id="editHeaderFamily" style="width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                            <option value="Arial, sans-serif">Arial</option>
                            <option value="'Segoe UI', sans-serif">Segoe UI</option>
                            <option value="'Inter', sans-serif">Inter</option>
                            <option value="'Poppins', sans-serif">Poppins</option>
                            <option value="'Roboto', sans-serif">Roboto</option>
                            <option value="'Montserrat', sans-serif">Montserrat</option>
                            <option value="'Playfair Display', serif">Playfair Display</option>
                            <option value="'Bebas Neue', cursive">Bebas Neue</option>
                            <option value="'Oswald', sans-serif">Oswald</option>
                            <option value="'Dancing Script', cursive">Dancing Script</option>
                            <option value="'Raleway', sans-serif">Raleway</option>
                            <option value="'Orbitron', sans-serif">Orbitron</option>
                            <option value="Georgia, serif">Georgia</option>
                            <option value="'Courier New', monospace">Courier New</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-btns">
                <button type="submit" class="modal-submit">Save Changes</button>
                <button type="button" class="modal-cancel" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Publish Modal with URL -->
<div class="modal-overlay" id="publishModal">
    <div class="modal" style="text-align:center; max-width:460px;">
        <div style="font-size:64px; margin-bottom:16px;">🎉</div>
        <div class="modal-title" style="font-size:24px;">Site Published!</div>
        <div class="modal-sub" style="margin-bottom:20px;">
            <strong><?php echo htmlspecialchars($site['site_name']); ?></strong> is now live and visible to everyone.
        </div>
        <div style="text-align:left; margin-bottom:8px;">
            <label style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Your site URL</label>
        </div>
        <div class="publish-url-box">
            <span class="publish-url-text" id="siteUrlText"><?php echo htmlspecialchars($siteUrl); ?></span>
            <button class="copy-btn" id="copyUrlBtn" onclick="copyUrl()">📋 Copy</button>
        </div>
        <div class="modal-btns" style="justify-content:center;">
            <a href="view.php?site_id=<?php echo $site_id; ?>" class="modal-submit" style="text-decoration:none; text-align:center; padding:13px 24px;" target="_blank">👁️ View Live Site</a>
            <button type="button" class="modal-cancel" onclick="document.getElementById('publishModal').classList.remove('active')">Close</button>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div class="modal-overlay" id="settingsModal">
    <div class="modal">
        <div class="modal-title">⚙️ Site Settings</div>
        <div class="modal-sub">Rename your website</div>
        <form action="../actions/update_site.php" method="POST">
            <input type="hidden" name="site_id" value="<?php echo $site_id; ?>">
            <input type="text" name="site_name" value="<?php echo htmlspecialchars($site['site_name']); ?>" required>
            <div class="modal-btns">
                <button type="submit" class="modal-submit">Save Settings</button>
                <button type="button" class="modal-cancel" onclick="closeSettings()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// ─── PANEL → CANVAS DRAG & DROP ───────────────────────────────────────────────
let draggingType = null; // type from panel
let draggingBlock = null; // block being reordered
let dragOverBlock = null;

// Type buttons draggable
document.querySelectorAll('.type-btn[draggable]').forEach(btn => {
    btn.addEventListener('dragstart', e => {
        draggingType = btn.dataset.type;
        draggingBlock = null;
        btn.classList.add('dragging-source');
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('text/plain', draggingType);
    });
    btn.addEventListener('dragend', () => {
        btn.classList.remove('dragging-source');
        draggingType = null;
    });
});

// Canvas drop zone
const canvas = document.getElementById('canvas');
const dropPlaceholder = document.getElementById('canvasDropPlaceholder');

canvas.addEventListener('dragover', e => {
    e.preventDefault();
    if (draggingType) {
        canvas.classList.add('panel-drag-over');
        dropPlaceholder.style.display = 'block';
        e.dataTransfer.dropEffect = 'copy';
    }
});

canvas.addEventListener('dragleave', e => {
    if (!canvas.contains(e.relatedTarget)) {
        canvas.classList.remove('panel-drag-over');
        dropPlaceholder.style.display = 'none';
    }
});

canvas.addEventListener('drop', e => {
    e.preventDefault();
    canvas.classList.remove('panel-drag-over');
    dropPlaceholder.style.display = 'none';
    if (draggingType) {
        // Programmatically submit the form with that type selected
        selectType(draggingType, document.querySelector(`.type-btn[data-type="${draggingType}"]`));
        document.getElementById('sectionForm').submit();
    }
});

// ─── CANVAS SECTION REORDER DRAG & DROP ───────────────────────────────────────
function initSectionDrag() {
    document.querySelectorAll('.section-block').forEach(block => {
        block.addEventListener('dragstart', e => {
            if (e.target.classList.contains('type-btn')) return;
            draggingBlock = block;
            draggingType = null;
            block.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', block.dataset.id);
        });

        block.addEventListener('dragend', () => {
            block.classList.remove('dragging');
            document.querySelectorAll('.section-block').forEach(b => {
                b.classList.remove('drag-over-top', 'drag-over-bottom');
            });
            draggingBlock = null;
        });

        block.addEventListener('dragover', e => {
            if (!draggingBlock || draggingBlock === block) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            const rect = block.getBoundingClientRect();
            const mid = rect.top + rect.height / 2;
            block.classList.remove('drag-over-top', 'drag-over-bottom');
            block.classList.add(e.clientY < mid ? 'drag-over-top' : 'drag-over-bottom');
            dragOverBlock = block;
        });

        block.addEventListener('dragleave', () => {
            block.classList.remove('drag-over-top', 'drag-over-bottom');
        });

        block.addEventListener('drop', e => {
            e.preventDefault();
            if (!draggingBlock || draggingBlock === block) return;
            block.classList.remove('drag-over-top', 'drag-over-bottom');

            const rect = block.getBoundingClientRect();
            const insertBefore = e.clientY < rect.top + rect.height / 2;

            const parent = block.parentNode;
            if (insertBefore) {
                parent.insertBefore(draggingBlock, block);
            } else {
                parent.insertBefore(draggingBlock, block.nextSibling);
            }

            // Persist new order via AJAX
            const ids = [...document.querySelectorAll('.section-block[data-id]')].map(b => b.dataset.id);
            fetch('../actions/reorder_sections.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids })
            });
        });
    });
}

initSectionDrag();

// ─── RESIZE HANDLES (non-image sections) ─────────────────────────────────────
document.querySelectorAll('.resize-handle').forEach(handle => {
    let startY, startH, block, inner;

    handle.addEventListener('mousedown', e => {
        e.preventDefault();
        block = handle.closest('.section-block');
        inner = block.querySelector('[class*="section-"]:not(.section-controls):not(.section-block)') ||
                block.querySelector('div:not(.drag-handle):not(.section-controls):not(.resize-handle)');
        startY = e.clientY;
        startH = block.offsetHeight;
        block.classList.add('resizing');
        document.body.style.cursor = 'ns-resize';
        document.body.style.userSelect = 'none';

        const onMove = ev => {
            const newH = Math.max(40, startH + (ev.clientY - startY));
            if (inner) inner.style.minHeight = newH + 'px';
        };
        const onUp = ev => {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
            block.classList.remove('resizing');
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
            const newH = Math.max(40, startH + (ev.clientY - startY));
            fetch('../actions/save_height.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: handle.dataset.id, height: newH })
            });
        };
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    });
});

// ─── IMAGE 8-HANDLE RESIZER + FREE DRAG ──────────────────────────────────────
function initImageResizers() {

    function syncContainerHeight(wrap) {
        const container = wrap.closest('.section-image-block');
        const needed = wrap.offsetTop + wrap.offsetHeight + 20;
        container.style.minHeight = Math.max(200, needed) + 'px';
    }

    document.querySelectorAll('.img-resizer-wrap').forEach(wrap => {
        const img   = wrap.querySelector('img');
        const secId = wrap.dataset.secId;
        if (!img) return;

        // Set initial container height once image loads
        if (img.complete) syncContainerHeight(wrap);
        else img.addEventListener('load', () => syncContainerHeight(wrap));

        // Click to select (not on a handle)
        wrap.addEventListener('mousedown', e => {
            if (e.target.classList.contains('img-handle')) return;
            e.stopPropagation();
            document.querySelectorAll('.img-resizer-wrap.selected').forEach(w => w.classList.remove('selected'));
            wrap.classList.add('selected');
        });

        // Prevent the parent section-block's draggable from stealing events inside the image block
        wrap.closest('.section-image-block').addEventListener('mousedown', e => {
            e.stopPropagation();
        });

        // ── Drag to reposition (Canva-style) ──────────────────────────────
        img.addEventListener('mousedown', e => {
            if (e.target.classList.contains('img-handle')) return;
            e.preventDefault();
            e.stopPropagation();

            // Select on drag start too
            document.querySelectorAll('.img-resizer-wrap.selected').forEach(w => w.classList.remove('selected'));
            wrap.classList.add('selected');

            const container = wrap.closest('.section-image-block');
            const startX    = e.clientX;
            const startY    = e.clientY;
            const origLeft  = wrap.offsetLeft;
            const origTop   = wrap.offsetTop;

            document.body.style.userSelect = 'none';
            wrap.style.cursor = 'grabbing';

            const onMove = ev => {
                const newLeft = Math.max(0, origLeft + ev.clientX - startX);
                const newTop  = Math.max(0, origTop  + ev.clientY - startY);
                wrap.style.left = newLeft + 'px';
                wrap.style.top  = newTop  + 'px';
                // Auto-expand container if image is dragged lower
                const needed = newTop + wrap.offsetHeight + 20;
                if (needed > container.offsetHeight) {
                    container.style.minHeight = needed + 'px';
                }
            };

            const onUp = () => {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                document.body.style.userSelect = '';
                wrap.style.cursor = '';

                const newX = wrap.offsetLeft;
                const newY = wrap.offsetTop;
                wrap.dataset.imgX = newX;
                wrap.dataset.imgY = newY;
                syncContainerHeight(wrap);

                fetch('../actions/save_height.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id:     secId,
                        height: Math.round(img.offsetHeight),
                        width:  Math.round(img.offsetWidth),
                        x:      newX,
                        y:      newY,
                        type:   'image'
                    })
                });
            };

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });

        // ── 8-handle resize ───────────────────────────────────────────────
        wrap.querySelectorAll('.img-handle').forEach(handle => {
            handle.addEventListener('mousedown', e => {
                e.preventDefault();
                e.stopPropagation();
                // Keep selected during resize
                wrap.classList.add('selected');

                const dir    = [...handle.classList].find(c => ['nw','n','ne','e','se','s','sw','w'].includes(c));
                const startX = e.clientX;
                const startY = e.clientY;
                const startW = img.offsetWidth;
                const startH = img.offsetHeight;

                document.body.style.userSelect = 'none';

                const onMove = ev => {
                    let dx = ev.clientX - startX;
                    let dy = ev.clientY - startY;
                    let newW = startW, newH = startH;

                    if (dir === 'e' || dir === 'ne' || dir === 'se') newW = Math.max(40, startW + dx);
                    if (dir === 'w' || dir === 'nw' || dir === 'sw') newW = Math.max(40, startW - dx);
                    if (dir === 's' || dir === 'se' || dir === 'sw') newH = Math.max(30, startH + dy);
                    if (dir === 'n' || dir === 'ne' || dir === 'nw') newH = Math.max(30, startH - dy);

                    if (['nw','ne','se','sw'].includes(dir)) {
                        const scale = Math.max(newW / startW, newH / startH);
                        newW = Math.round(startW * scale);
                        newH = Math.round(startH * scale);
                    }

                    img.style.width  = newW + 'px';
                    img.style.height = newH + 'px';
                    wrap.style.width = newW + 'px';
                };

                const onUp = () => {
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                    document.body.style.userSelect = '';
                    syncContainerHeight(wrap);

                    fetch('../actions/save_height.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id:     secId,
                            height: Math.round(img.offsetHeight),
                            width:  Math.round(img.offsetWidth),
                            x:      wrap.offsetLeft,
                            y:      wrap.offsetTop,
                            type:   'image'
                        })
                    });
                };

                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            });
        });
    });

    // Click outside deselects
    document.addEventListener('mousedown', e => {
        if (!e.target.closest('.img-resizer-wrap')) {
            document.querySelectorAll('.img-resizer-wrap.selected').forEach(w => w.classList.remove('selected'));
        }
    });
}

initImageResizers();

// ─── SELECT TYPE ──────────────────────────────────────────────────────────────
function selectType(type, btn) {
    document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    document.getElementById('sectionType').value = type;

    const labels = {
        text:    { title: '📝 Text Section',   label: 'Text Content',        placeholder: 'Enter your text here...', input: 'textarea' },
        image:   { title: '🖼️ Image Section',  label: 'Image URL (optional)', placeholder: 'https://example.com/image.jpg', input: 'text' },
        hero:    { title: '🚀 Hero Section',   label: 'Hero Title',           placeholder: 'Welcome to My Website!', input: 'text' },
        header:  { title: '🔝 Header Section', label: 'Brand Name',           placeholder: 'My Awesome Website', input: 'text' },
        footer:  { title: '🔚 Footer Section', label: 'Footer Text',          placeholder: '© 2026 My Website.', input: 'text' },
        divider: { title: '➖ Divider',         label: 'No content needed',    placeholder: '', input: 'none' },
        button:  { title: '🔗 Button/Link',    label: 'Button Text',          placeholder: 'Click Here', input: 'text' },
    };

    const cfg = labels[type] || labels.text;
    document.getElementById('formTitle').textContent = cfg.title;

    const contentGroup  = document.getElementById('contentGroup');
    const uploadGroup   = document.getElementById('uploadGroup');
    const textStyleOpts = document.getElementById('textStyleOptions');
    const headerColorOpts = document.getElementById('headerColorOptions');

    if (type === 'image') {
        contentGroup.style.display = '';
        uploadGroup.style.display  = '';
        textStyleOpts.style.display = 'none';
        headerColorOpts.style.display = 'none';
        document.getElementById('contentLabel').textContent = 'Image URL (optional)';
        const current = document.getElementById('contentInput');
        if (current.tagName === 'TEXTAREA') {
            const input = document.createElement('input');
            input.type = 'text'; input.name = 'content';
            input.id = 'contentInput'; input.placeholder = cfg.placeholder;
            current.replaceWith(input);
        } else { current.placeholder = cfg.placeholder; }
    } else {
        uploadGroup.style.display = 'none';
        headerColorOpts.style.display = (type === 'header') ? '' : 'none';
        textStyleOpts.style.display = (type === 'hero' || type === 'divider' || type === 'footer' || type === 'button') ? 'none' : '';
        document.getElementById('contentLabel').textContent = cfg.label;

        if (cfg.input === 'none') {
            contentGroup.style.display = 'none';
        } else {
            contentGroup.style.display = '';
            const current = document.getElementById('contentInput');
            if (cfg.input === 'text' && current.tagName === 'TEXTAREA') {
                const input = document.createElement('input');
                input.type = 'text'; input.name = 'content';
                input.id = 'contentInput'; input.placeholder = cfg.placeholder;
                current.replaceWith(input);
            } else if (cfg.input === 'textarea' && current.tagName === 'INPUT') {
                const textarea = document.createElement('textarea');
                textarea.name = 'content'; textarea.id = 'contentInput';
                textarea.placeholder = cfg.placeholder;
                current.replaceWith(textarea);
            } else { current.placeholder = cfg.placeholder; }
        }
    }
}

// ─── FILE INPUT ───────────────────────────────────────────────────────────────
document.getElementById('fileInput').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('imagePreview');
        preview.src = e.target.result;
        preview.style.display = 'block';
        document.querySelector('.upload-drop-zone .upload-text').textContent = file.name;
        document.querySelector('.upload-drop-zone .upload-icon').textContent = '✅';
    };
    reader.readAsDataURL(file);
});

const dropZone = document.getElementById('dropZone');
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('fileInput').files = dt.files;
        document.getElementById('fileInput').dispatchEvent(new Event('change'));
    }
});

// ─── EDIT MODAL IMAGE UPLOAD ──────────────────────────────────────────────────
document.getElementById('editFileInput').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('editNewImagePreview');
        preview.src = e.target.result;
        preview.style.display = 'block';
        document.getElementById('editUploadText').textContent = file.name;
        document.getElementById('editUploadIcon').textContent = '✅';
        // Clear URL field when file is selected
        document.getElementById('editImageUrl').value = '';
    };
    reader.readAsDataURL(file);
});

const editDropZone = document.getElementById('editDropZone');
editDropZone.addEventListener('dragover', e => { e.preventDefault(); editDropZone.style.borderColor = '#6c3afc'; editDropZone.style.background = 'rgba(108,58,252,0.08)'; });
editDropZone.addEventListener('dragleave', () => { editDropZone.style.borderColor = '#c4b5fd'; editDropZone.style.background = 'rgba(108,58,252,0.03)'; });
editDropZone.addEventListener('drop', e => {
    e.preventDefault();
    editDropZone.style.borderColor = '#c4b5fd';
    editDropZone.style.background = 'rgba(108,58,252,0.03)';
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('editFileInput').files = dt.files;
        document.getElementById('editFileInput').dispatchEvent(new Event('change'));
    }
});

// ─── MOVE SECTION (AJAX) ─────────────────────────────────────────────────────
function moveSection(id, dir, btn) {
    const block = btn.closest('.section-block');
    const canvas = document.getElementById('canvas');
    const blocks = [...canvas.querySelectorAll('.section-block[data-id]')];
    const idx = blocks.indexOf(block);

    // Move in DOM immediately
    if (dir === 'up' && idx > 0) {
        canvas.insertBefore(block, blocks[idx - 1]);
    } else if (dir === 'down' && idx < blocks.length - 1) {
        canvas.insertBefore(blocks[idx + 1], block);
    } else {
        return; // already at edge
    }

    // Persist new order
    const newIds = [...canvas.querySelectorAll('.section-block[data-id]')].map(b => b.dataset.id);
    fetch('../actions/reorder_sections.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids: newIds })
    });
}

// ─── EDIT MODAL ───────────────────────────────────────────────────────────────
function openEditModal(id, content, type, styleObj) {
    document.getElementById('editSectionId').value   = id;
    document.getElementById('editSectionType').value = type;

    const textStyles   = document.getElementById('editTextStyles');
    const headerStyles = document.getElementById('editHeaderStyles');
    const buttonStyles = document.getElementById('editButtonStyles');
    const imageGroup   = document.getElementById('editImageGroup');
    const contentGroup = document.getElementById('editContentGroup');
    const sub          = document.getElementById('editModalSub');

    // Reset image upload fields
    document.getElementById('editFileInput').value = '';
    document.getElementById('editNewImagePreview').style.display = 'none';
    document.getElementById('editUploadIcon').textContent = '📁';
    document.getElementById('editUploadText').textContent = 'Click to browse or drag & drop';

    if (type === 'image') {
        contentGroup.style.display   = 'none';
        imageGroup.style.display     = '';
        textStyles.style.display     = 'none';
        headerStyles.style.display   = 'none';
        buttonStyles.style.display   = 'none';
        sub.textContent = 'Change or replace the image';

        // Show current image
        const urlInput = document.getElementById('editImageUrl');
        urlInput.value = content || '';
        const preview = document.getElementById('editImagePreview');
        const placeholder = document.getElementById('editImgPlaceholder');
        if (content) {
            preview.src = content;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            preview.style.display = 'none';
            placeholder.style.display = 'flex';
        }
    } else if (type === 'button') {
        contentGroup.style.display   = '';
        imageGroup.style.display     = 'none';
        textStyles.style.display     = 'none';
        headerStyles.style.display   = 'none';
        buttonStyles.style.display   = '';
        sub.textContent = 'Edit button text, link, and styles';
        document.getElementById('editSectionContent').value = content;
        document.getElementById('editBtnUrl').value         = styleObj.url        || '';
        document.getElementById('editBtnBg').value          = styleObj.bg         || '#6c3afc';
        document.getElementById('editBtnColor').value       = styleObj.color      || '#ffffff';
        document.getElementById('editBtnFontSize').value    = parseInt(styleObj.font_size) || 16;
        document.getElementById('editBtnWeight').value      = styleObj.font_weight || 'bold';
        document.getElementById('editBtnAlign').value       = styleObj.text_align  || 'center';
        setSelectValue('editBtnRadius', styleObj.radius || '12px');
    } else if (type === 'header') {
        contentGroup.style.display   = '';
        imageGroup.style.display     = 'none';
        textStyles.style.display     = 'none';
        headerStyles.style.display   = '';
        buttonStyles.style.display   = 'none';
        sub.textContent = 'Edit header brand name and styles';
        document.getElementById('editSectionContent').value = content;
        document.getElementById('editBgColor').value         = styleObj.bg         || '#1a1a2e';
        document.getElementById('editHeaderTextColor').value = styleObj.color       || '#ffffff';
        document.getElementById('editHeaderAlign').value     = styleObj.text_align  || 'left';
        document.getElementById('editHeaderFontSize').value  = parseInt(styleObj.font_size) || 24;
        document.getElementById('editHeaderWeight').value    = styleObj.font_weight || 'bold';
        setSelectValue('editHeaderFamily', styleObj.font_family || 'Arial, sans-serif');
    } else {
        contentGroup.style.display   = '';
        imageGroup.style.display     = 'none';
        textStyles.style.display     = (type === 'divider' || type === 'hero') ? 'none' : '';
        headerStyles.style.display   = 'none';
        buttonStyles.style.display   = 'none';
        sub.textContent = 'Update your section content and styles';
        document.getElementById('editSectionContent').value = content;
        if (styleObj) {
            document.getElementById('editTextAlign').value  = styleObj.text_align  || 'left';
            document.getElementById('editFontSize').value   = parseInt(styleObj.font_size) || 16;
            document.getElementById('editColor').value      = styleObj.color        || '#000000';
            document.getElementById('editFontWeight').value = styleObj.font_weight  || 'normal';
            setSelectValue('editFontFamily', styleObj.font_family || 'Arial, sans-serif');
        }
    }

    document.getElementById('editModal').classList.add('active');
}

function setSelectValue(id, value) {
    const sel = document.getElementById(id);
    for (let i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === value) { sel.selectedIndex = i; return; }
    }
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

// ─── SETTINGS ─────────────────────────────────────────────────────────────────
function openSettings() {
    document.getElementById('settingsModal').classList.add('active');
}

function closeSettings() {
    document.getElementById('settingsModal').classList.remove('active');
}

// ─── PUBLISH ──────────────────────────────────────────────────────────────────
function doPublish() {
    fetch('../actions/publish.php?site_id=<?php echo $site_id; ?>&ajax=1')
        .then(() => {
            document.getElementById('publishModal').classList.add('active');
        })
        .catch(() => {
            // Fallback: still show modal
            document.getElementById('publishModal').classList.add('active');
        });
}

function copyUrl() {
    const url = document.getElementById('siteUrlText').textContent.trim();
    navigator.clipboard.writeText(url).then(() => {
        const btn = document.getElementById('copyUrlBtn');
        btn.textContent = '✅ Copied!';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = '📋 Copy'; btn.classList.remove('copied'); }, 2000);
    });
}

// Close modals on backdrop click
['editModal', 'settingsModal', 'publishModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});

// Show publish modal if redirected with ?published=1
<?php if (isset($_GET['published'])): ?>
document.getElementById('publishModal').classList.add('active');
<?php endif; ?>
</script>
</body>
</html>
