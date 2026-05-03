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

// Get site info
$site = $conn->query("SELECT * FROM sites WHERE id = $site_id")->fetch_assoc();
if (!$site) { header("Location: dashboard.php"); exit(); }

// Get or create page
$page = $conn->query("SELECT * FROM pages WHERE site_id = $site_id")->fetch_assoc();
if (!$page) {
    $conn->query("INSERT INTO pages (site_id, title, slug) VALUES ($site_id, 'Home', 'home')");
    $page = $conn->query("SELECT * FROM pages WHERE site_id = $site_id")->fetch_assoc();
}
$page_id = $page['id'];

// Get sections
$sections = $conn->query("SELECT * FROM sections WHERE page_id = $page_id AND is_archived = 0 ORDER BY position ASC");
$totalSections = $sections ? $sections->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor | <?php echo htmlspecialchars($site['site_name']); ?></title>
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
            padding: 0 28px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-left { display: flex; align-items: center; gap: 16px; }

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

        .topbar-right { display: flex; align-items: center; gap: 10px; }

        .preview-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(108,58,252,0.3);
            transition: all 0.2s;
        }

        .preview-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(108,58,252,0.4);
            color: #fff;
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
        }

        /* ── LAYOUT ── */
        .editor-layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            min-height: calc(100vh - 65px);
        }

        /* ── LEFT PANEL ── */
        .left-panel {
            background: #fff;
            border-right: 1px solid #e9edf5;
            padding: 24px;
            overflow-y: auto;
        }

        .panel-title {
            font-size: 13px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        /* Section type buttons */
        .section-types {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 28px;
        }

        .type-btn {
            padding: 14px 10px;
            border: 2px solid #e9edf5;
            border-radius: 14px;
            background: #fafafa;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }

        .type-btn:hover {
            border-color: #6c3afc;
            background: rgba(108,58,252,0.05);
            color: #6c3afc;
            transform: translateY(-2px);
        }

        .type-btn.active {
            border-color: #6c3afc;
            background: linear-gradient(135deg, rgba(108,58,252,0.1), rgba(224,64,251,0.1));
            color: #6c3afc;
        }

        .type-icon { font-size: 24px; display: block; margin-bottom: 6px; }

        /* Add section form */
        .add-form { background: #f8f9ff; border-radius: 16px; padding: 18px; border: 1px solid #e0e7ff; }
        .add-form-title { font-size: 15px; font-weight: 700; color: #1a1a2e; margin-bottom: 14px; }

        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px; }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            background: #fff;
            transition: all 0.2s;
        }

        .form-group textarea { min-height: 100px; resize: vertical; }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #6c3afc;
            box-shadow: 0 0 0 3px rgba(108,58,252,0.1);
        }

        .add-btn {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #6c3afc, #e040fb);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(108,58,252,0.25);
        }

        .add-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(108,58,252,0.35); }

        /* ── RIGHT PANEL (Canvas) ── */
        .canvas-panel { padding: 28px; overflow-y: auto; }

        .canvas-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .canvas-title { font-size: 20px; font-weight: 800; color: #1a1a2e; }
        .section-count { font-size: 13px; color: #9ca3af; font-weight: 600; }

        .canvas {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            overflow: hidden;
            min-height: 400px;
            border: 1px solid #e9edf5;
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

        /* Section blocks */
        .section-block {
            border-bottom: 1px solid #f0f0f0;
            position: relative;
            transition: all 0.2s;
        }

        .section-block:last-child { border-bottom: none; }
        .section-block:hover { background: #fafaff; }

        .section-block-inner { padding: 20px 24px; }

        /* Section type styles */
        .section-hero {
            background: linear-gradient(135deg, #6c3afc 0%, #e040fb 50%, #ff6b6b 100%);
            color: #fff;
            padding: 48px 32px;
            text-align: center;
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
        }

        .section-header-block span { font-size: 22px; font-weight: 800; }

        .section-footer-block {
            background: #f3f4f6;
            padding: 20px 32px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }

        .section-text-block { padding: 24px 32px; font-size: 15px; line-height: 1.7; color: #374151; }

        .section-image-block { padding: 20px 32px; }
        .section-image-block img { max-width: 100%; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
        .section-image-block .img-placeholder {
            background: linear-gradient(135deg, #f3f4f6, #e9edf5);
            height: 200px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #9ca3af;
        }

        /* Section controls */
        .section-controls {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            gap: 6px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .section-block:hover .section-controls { opacity: 1; }

        .ctrl-btn {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .ctrl-up { background: #eef2ff; color: #4f46e5; }
        .ctrl-down { background: #eef2ff; color: #4f46e5; }
        .ctrl-delete { background: #fee2e2; color: #b91c1c; }

        .ctrl-btn:hover { transform: scale(1.1); }
    </style>
</head>
<body>

<!-- Topbar -->
<header class="topbar">
    <div class="topbar-left">
        <a href="dashboard.php" class="back-btn">← Back</a>
        <div class="topbar-brand">PageCraft</div>
        <span class="site-name-badge">🌐 <?php echo htmlspecialchars($site['site_name']); ?></span>
    </div>
    <div class="topbar-right">
        <a href="view.php?site_id=<?php echo $site_id; ?>" class="preview-btn" target="_blank">👁️ Preview</a>
        <div class="topbar-avatar"><?php echo strtoupper($username[0]); ?></div>
    </div>
</header>

<!-- Editor Layout -->
<div class="editor-layout">

    <!-- Left Panel: Add Sections -->
    <div class="left-panel">
        <div class="panel-title">Add Section</div>

        <!-- Section Type Selector -->
        <div class="section-types">
            <button class="type-btn active" onclick="selectType('text', this)">
                <span class="type-icon">📝</span>Text
            </button>
            <button class="type-btn" onclick="selectType('image', this)">
                <span class="type-icon">🖼️</span>Image
            </button>
            <button class="type-btn" onclick="selectType('hero', this)">
                <span class="type-icon">🚀</span>Hero
            </button>
            <button class="type-btn" onclick="selectType('header', this)">
                <span class="type-icon">🔝</span>Header
            </button>
            <button class="type-btn" onclick="selectType('footer', this)">
                <span class="type-icon">🔚</span>Footer
            </button>
            <button class="type-btn" onclick="selectType('divider', this)">
                <span class="type-icon">➖</span>Divider
            </button>
        </div>

        <!-- Add Form -->
        <div class="add-form">
            <div class="add-form-title" id="formTitle">📝 Add Text Section</div>
            <form action="../actions/save_section.php?site_id=<?php echo $site_id; ?>" method="POST">
                <input type="hidden" name="page_id" value="<?php echo $page_id; ?>">
                <input type="hidden" name="type" id="sectionType" value="text">

                <div class="form-group" id="contentGroup">
                    <label id="contentLabel">Text Content</label>
                    <textarea name="content" id="contentInput" placeholder="Enter your text content here..."></textarea>
                </div>

                <button type="submit" class="add-btn">+ Add Section</button>
            </form>
        </div>
    </div>

    <!-- Right Panel: Canvas -->
    <div class="canvas-panel">
        <div class="canvas-header">
            <div class="canvas-title">Page Canvas</div>
            <div class="section-count"><?php echo $totalSections; ?> section<?php echo $totalSections !== 1 ? 's' : ''; ?></div>
        </div>

        <div class="canvas">
            <?php
            // Re-query sections for display
            $sections = $conn->query("SELECT * FROM sections WHERE page_id = $page_id AND is_archived = 0 ORDER BY position ASC");
            ?>

            <?php if ($sections && $sections->num_rows > 0): ?>
                <?php while ($sec = $sections->fetch_assoc()): ?>
                <div class="section-block">
                    <?php if ($sec['type'] === 'hero'): ?>
                        <div class="section-hero">
                            <h2><?php echo htmlspecialchars($sec['content']); ?></h2>
                            <p>Your hero section</p>
                        </div>

                    <?php elseif ($sec['type'] === 'header'): ?>
                        <div class="section-header-block">
                            <span><?php echo htmlspecialchars($sec['content']); ?></span>
                            <span style="font-size:14px; opacity:0.7;">Navigation</span>
                        </div>

                    <?php elseif ($sec['type'] === 'footer'): ?>
                        <div class="section-footer-block">
                            <?php echo htmlspecialchars($sec['content']); ?>
                        </div>

                    <?php elseif ($sec['type'] === 'image'): ?>
                        <div class="section-image-block">
                            <?php if (!empty($sec['content'])): ?>
                                <img src="<?php echo htmlspecialchars($sec['content']); ?>" alt="Section Image">
                            <?php else: ?>
                                <div class="img-placeholder">🖼️</div>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($sec['type'] === 'divider'): ?>
                        <div style="padding: 16px 32px;">
                            <hr style="border: none; border-top: 3px solid; border-image: linear-gradient(135deg, #6c3afc, #e040fb) 1;">
                        </div>

                    <?php else: ?>
                        <div class="section-text-block">
                            <?php echo nl2br(htmlspecialchars($sec['content'])); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Controls -->
                    <div class="section-controls">
                        <a href="../actions/move.php?id=<?php echo $sec['id']; ?>&dir=up&site_id=<?php echo $site_id; ?>" class="ctrl-btn ctrl-up">⬆️</a>
                        <a href="../actions/move.php?id=<?php echo $sec['id']; ?>&dir=down&site_id=<?php echo $site_id; ?>" class="ctrl-btn ctrl-down">⬇️</a>
                        <a href="../actions/archive.php?id=<?php echo $sec['id']; ?>&site_id=<?php echo $site_id; ?>"
                           class="ctrl-btn ctrl-delete"
                           onclick="return confirm('Remove this section?');">🗑️</a>
                    </div>
                </div>
                <?php endwhile; ?>

            <?php else: ?>
                <div class="canvas-empty">
                    <div class="canvas-empty-icon">🎨</div>
                    <h3>Your canvas is empty!</h3>
                    <p>Add sections from the left panel to start building your website</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function selectType(type, btn) {
    // Update active button
    document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Update hidden input
    document.getElementById('sectionType').value = type;

    // Update form label and placeholder
    const labels = {
        text:    { title: '📝 Add Text Section',    label: 'Text Content',      placeholder: 'Enter your text content here...',  input: 'textarea' },
        image:   { title: '🖼️ Add Image Section',   label: 'Image URL',         placeholder: 'https://example.com/image.jpg',    input: 'text' },
        hero:    { title: '🚀 Add Hero Section',    label: 'Hero Title',        placeholder: 'Welcome to My Website!',           input: 'text' },
        header:  { title: '🔝 Add Header Section',  label: 'Site/Brand Name',   placeholder: 'My Awesome Website',               input: 'text' },
        footer:  { title: '🔚 Add Footer Section',  label: 'Footer Text',       placeholder: '© 2026 My Website. All rights reserved.', input: 'text' },
        divider: { title: '➖ Add Divider',          label: 'No content needed', placeholder: '',                                 input: 'none' },
    };

    const cfg = labels[type];
    document.getElementById('formTitle').textContent = cfg.title;
    document.getElementById('contentLabel').textContent = cfg.label;

    const contentGroup = document.getElementById('contentGroup');

    if (cfg.input === 'none') {
        contentGroup.style.display = 'none';
    } else {
        contentGroup.style.display = '';
        // Replace textarea with input or vice versa
        const current = document.getElementById('contentInput');
        if (cfg.input === 'text' && current.tagName === 'TEXTAREA') {
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'content';
            input.id = 'contentInput';
            input.placeholder = cfg.placeholder;
            current.replaceWith(input);
        } else if (cfg.input === 'textarea' && current.tagName === 'INPUT') {
            const textarea = document.createElement('textarea');
            textarea.name = 'content';
            textarea.id = 'contentInput';
            textarea.placeholder = cfg.placeholder;
            textarea.style.minHeight = '100px';
            current.replaceWith(textarea);
        } else {
            current.placeholder = cfg.placeholder;
        }
    }
}
</script>
</body>
</html>