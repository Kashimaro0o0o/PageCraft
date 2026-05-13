<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$site_name = trim($_POST['site_name'] ?? 'My New Website');
$template  = $_POST['template_type'] ?? 'blank';

$stmt = $conn->prepare("INSERT INTO sites (user_id, site_name, template_type) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $site_name, $template);
$stmt->execute();
$site_id = $stmt->insert_id;

$stmt = $conn->prepare("INSERT INTO pages (site_id, title, slug) VALUES (?, 'Home', 'home')");
$stmt->bind_param("i", $site_id);
$stmt->execute();
$page_id = $stmt->insert_id;

$starter_sections = [];

// ── Blank ─────────────────────────────────────────────────────────────────
if ($template === 'blank') {
    $starter_sections = []; // empty canvas

// ── Business ──────────────────────────────────────────────────────────────
} elseif ($template === 'business') {
    $starter_sections = [
        ['type' => 'header', 'content' => 'Professional Business Solutions', 'style' => ['bg' => '#004aad', 'color' => '#ffffff', 'font_size' => '28px', 'font_weight' => 'bold']],
        ['type' => 'text',   'content' => "Welcome to [Your Business Name]\n\nWe provide world-class solutions tailored to your needs. Whether you're a startup or an established enterprise, we help you grow with modern technology and expert strategies.\n\n📞 Contact us: info@yourbusiness.com\n📍 Address: [Your Address]\n⏰ Hours: Mon–Fri, 9AM–6PM", 'style' => ['font_size' => '16px', 'color' => '#374151']],
        ['type' => 'divider','content' => '', 'style' => []],
        ['type' => 'text',   'content' => "Our Services\n\n✅ Service 1 — Brief description of what you offer\n✅ Service 2 — Brief description of what you offer\n✅ Service 3 — Brief description of what you offer", 'style' => ['font_size' => '16px', 'color' => '#1f2937']],
        ['type' => 'footer', 'content' => '[Your Business Name] — All Rights Reserved', 'style' => []],
    ];

// ── Shop ──────────────────────────────────────────────────────────────────
} elseif ($template === 'shop') {
    $starter_sections = [
        ['type' => 'header', 'content' => '🛒 [Your Shop Name]', 'style' => ['bg' => '#134e4a', 'color' => '#ccfbf1', 'font_size' => '28px', 'font_weight' => 'bold']],
        ['type' => 'text',   'content' => "Welcome to [Your Shop Name]!\n\nWe sell high-quality [product type] at affordable prices. Browse our collection below and reach out to order.\n\n📦 Free delivery on orders over ₱500\n💳 We accept GCash, Maya, and Cash on Delivery", 'style' => ['text_align' => 'center', 'font_size' => '16px', 'color' => '#374151']],
        ['type' => 'divider','content' => '', 'style' => []],
        ['type' => 'text',   'content' => "🛍️ Featured Products\n\n🔹 Product 1 — ₱[price] | [Short description]\n🔹 Product 2 — ₱[price] | [Short description]\n🔹 Product 3 — ₱[price] | [Short description]\n🔹 Product 4 — ₱[price] | [Short description]\n\n📩 To order: shopname@email.com | 09XX-XXX-XXXX", 'style' => ['font_size' => '16px', 'color' => '#1f2937']],
        ['type' => 'footer', 'content' => '[Your Shop Name] — Shop with confidence 🛒', 'style' => []],
    ];

// ── Hotel ─────────────────────────────────────────────────────────────────
} elseif ($template === 'hotel') {
    $starter_sections = [
        ['type' => 'header', 'content' => '🏨 [Hotel Name] — Your Home Away From Home', 'style' => ['bg' => '#1c1917', 'color' => '#fef3c7', 'font_size' => '26px', 'font_weight' => 'bold']],
        ['type' => 'text',   'content' => "Welcome to [Hotel Name]\n\nNestled in the heart of [Location], we offer a luxurious and comfortable stay for travelers, families, and business guests alike.\n\n⭐ [Star Rating] | 📍 [Full Address] | 📞 [Phone Number]", 'style' => ['text_align' => 'center', 'font_size' => '16px', 'color' => '#374151']],
        ['type' => 'divider','content' => '', 'style' => []],
        ['type' => 'text',   'content' => "🛏️ Room Types\n\n• Deluxe Room — ₱[price]/night | [description]\n• Superior Room — ₱[price]/night | [description]\n• Suite — ₱[price]/night | [description]\n\n🏊 Amenities\nSwimming Pool | Free WiFi | Restaurant | Parking | 24/7 Front Desk\n\n📅 Reservations\nEmail: reservations@hotel.com | Call: 09XX-XXX-XXXX\nCheck-in: 2:00 PM | Check-out: 12:00 NN", 'style' => ['font_size' => '16px', 'color' => '#1f2937']],
        ['type' => 'footer', 'content' => '[Hotel Name] — [Location] | Experience comfort like never before', 'style' => []],
    ];

// ── Resume / CV ───────────────────────────────────────────────────────────
} elseif ($template === 'resume') {
    $starter_sections = [
        ['type' => 'header', 'content' => '[Your Full Name]', 'style' => ['bg' => '#18181b', 'color' => '#ffffff', 'font_size' => '32px', 'font_weight' => 'bold']],
        ['type' => 'text',   'content' => "[Job Title / Profession]\n📍 [City, Country]  |  📧 yourname@email.com  |  🔗 linkedin.com/in/yourname  |  🐙 github.com/yourname", 'style' => ['text_align' => 'center', 'font_size' => '15px', 'color' => '#6b7280']],
        ['type' => 'divider','content' => '', 'style' => []],
        ['type' => 'text',   'content' => "👤 About Me\n\nI am a passionate [profession] with [X] years of experience in [field]. I specialize in [skills/technologies] and am always eager to learn and take on new challenges.\n\n💼 Work Experience\n\n[Job Title] — [Company Name] | [Year] – Present\n• Responsibility or achievement 1\n• Responsibility or achievement 2\n\n[Job Title] — [Company Name] | [Year] – [Year]\n• Responsibility or achievement 1\n• Responsibility or achievement 2\n\n🎓 Education\n\n[Degree] in [Course] — [University] | [Year graduated]\n\n🛠️ Skills\nPHP | MySQL | JavaScript | HTML/CSS | Git | [Add more]\n\n🏆 Achievements\n• Achievement 1\n• Achievement 2", 'style' => ['font_size' => '16px', 'color' => '#1f2937']],
        ['type' => 'footer', 'content' => '[Your Name] — [Job Title] | yourname@email.com', 'style' => []],
    ];

// ── School ────────────────────────────────────────────────────────────────
} elseif ($template === 'school') {
    $starter_sections = [
        ['type' => 'header', 'content' => '🏫 [School Name]', 'style' => ['bg' => '#1e3a8a', 'color' => '#dbeafe', 'font_size' => '28px', 'font_weight' => 'bold']],
        ['type' => 'text',   'content' => "[School Motto or Tagline]\n\n📍 [School Address] | 📞 [Phone] | 📧 info@school.edu.ph", 'style' => ['text_align' => 'center', 'font_size' => '16px', 'color' => '#374151']],
        ['type' => 'divider','content' => '', 'style' => []],
        ['type' => 'text',   'content' => "📢 Announcements\n\n• [Announcement 1] — [Date]\n• [Announcement 2] — [Date]\n• [Announcement 3] — [Date]\n\nℹ️ About Our School\n\nFounded in [Year], [School Name] is dedicated to providing quality education to students in [Location]. We offer programs in [list of programs/strands].\n\n📅 School Calendar\n• Enrollment: [Date range]\n• First Day of Classes: [Date]\n• Holidays: [Date]\n\n🏅 Achievements\n• Achievement 1\n• Achievement 2", 'style' => ['font_size' => '16px', 'color' => '#1f2937']],
        ['type' => 'footer', 'content' => '[School Name] — Nurturing minds, shaping futures.', 'style' => []],
    ];

// ── Existing: Restaurant ──────────────────────────────────────────────────
} elseif ($template === 'restaurant') {
    $starter_sections = [
        ['type' => 'header', 'content' => 'Welcome to Our Restaurant', 'style' => ['bg' => '#1a1a2e', 'color' => '#ffffff']],
        ['type' => 'text',   'content' => 'Authentic flavors served daily. Check out our menu!', 'style' => ['text_align' => 'center']],
    ];

// ── Existing: Portfolio ───────────────────────────────────────────────────
} elseif ($template === 'portfolio') {
    $starter_sections = [
        ['type' => 'header', 'content' => 'My Creative Portfolio', 'style' => ['bg' => '#333', 'color' => '#fff']],
        ['type' => 'text',   'content' => 'I am a developer/designer based in Pasig.', 'style' => []],
    ];

// ── College Student Templates ─────────────────────────────────────────────

} elseif ($template === 'student_portfolio') {
    $starter_sections = [
        ['type' => 'header', 'content' => "Hi, I'm [Your Name] 👋", 'style' => ['bg' => '#1e1b4b', 'color' => '#ffffff', 'font_size' => '32px', 'font_weight' => 'bold']],
        ['type' => 'text',   'content' => "I'm a Computer Science student at [Your University]. I love building web apps, solving problems, and learning new technologies. Welcome to my portfolio!", 'style' => ['text_align' => 'center', 'font_size' => '17px', 'color' => '#374151']],
        ['type' => 'divider','content' => '', 'style' => []],
        ['type' => 'text',   'content' => "🚀 Projects\n\n• Project 1 — A web app built with PHP and MySQL\n• Project 2 — A Python data analysis script\n• Project 3 — A mobile-first landing page\n\nFeel free to reach out at yourname@email.com", 'style' => ['font_size' => '16px', 'color' => '#1f2937']],
        ['type' => 'footer', 'content' => '[Your Name] — Student Portfolio', 'style' => []],
    ];

} elseif ($template === 'study_blog') {
    $starter_sections = [
        ['type' => 'header', 'content' => '📚 Study Notes & Blog', 'style' => ['bg' => '#064e3b', 'color' => '#d1fae5', 'font_size' => '28px', 'font_weight' => 'bold']],
        ['type' => 'text',   'content' => 'Welcome to my study blog! Here I share notes, summaries, and insights from my college courses. Hope these help your studies too. 😊', 'style' => ['text_align' => 'center', 'font_size' => '16px', 'color' => '#374151']],
        ['type' => 'divider','content' => '', 'style' => []],
        ['type' => 'text',   'content' => "📝 Latest Notes\n\n📌 Topic: Introduction to Algorithms\nA quick summary of Big-O notation and sorting algorithms...\n\n📌 Topic: Organic Chemistry Basics\nKey functional groups and reaction types for your first exam...\n\n📌 Topic: Philippine History Review\nTimeline of major events from pre-colonial period to present...", 'style' => ['font_size' => '16px', 'color' => '#1f2937']],
        ['type' => 'footer', 'content' => 'Study Blog — Sharing knowledge, one note at a time.', 'style' => []],
    ];

} elseif ($template === 'org_club') {
    $starter_sections = [
        ['type' => 'header', 'content' => '🏫 [Your Org Name] — Official Website', 'style' => ['bg' => '#7c2d12', 'color' => '#fef3c7', 'font_size' => '28px', 'font_weight' => 'bold']],
        ['type' => 'text',   'content' => 'We are the [Org Name], a student organization at [University] dedicated to [mission/advocacy]. We welcome all students who share our passion!', 'style' => ['text_align' => 'center', 'font_size' => '16px', 'color' => '#374151']],
        ['type' => 'divider','content' => '', 'style' => []],
        ['type' => 'text',   'content' => "📅 Upcoming Events\n\n• General Assembly — [Date] @ [Venue]\n• Leadership Training — [Date] @ [Venue]\n• Community Outreach — [Date] @ [Venue]\n\n👥 Officers\nPresident: [Name] | VP: [Name] | Secretary: [Name]\n\n📩 Contact us: orgname@university.edu", 'style' => ['font_size' => '16px', 'color' => '#1f2937']],
        ['type' => 'footer', 'content' => '[Org Name] — [University] | AY 2025–2026', 'style' => []],
    ];

} elseif ($template === 'research_project') {
    $starter_sections = [
        ['type' => 'header', 'content' => '🔬 Research Project Title', 'style' => ['bg' => '#0c4a6e', 'color' => '#e0f2fe', 'font_size' => '28px', 'font_weight' => 'bold']],
        ['type' => 'text',   'content' => 'Presented by: [Student Names] | Adviser: [Professor Name] | [University] — [Department] | AY 2025–2026', 'style' => ['text_align' => 'center', 'font_size' => '15px', 'color' => '#6b7280']],
        ['type' => 'divider','content' => '', 'style' => []],
        ['type' => 'text',   'content' => "📄 Abstract\n\nThis study investigates [topic/problem]. The researchers aim to [objective]. Using [methodology], this paper presents findings that [summary of results].\n\n❓ Problem Statement\nDescribe the issue or gap your research addresses here.\n\n🎯 Objectives\n• Objective 1\n• Objective 2\n• Objective 3\n\n📊 Methodology\nDescribe your research design, data collection, and analysis approach.\n\n✅ Findings & Conclusion\nSummarize your key results and what they mean.", 'style' => ['font_size' => '16px', 'color' => '#1f2937']],
        ['type' => 'footer', 'content' => '[University] — [Department] | Research Presented AY 2025–2026', 'style' => []],
    ];

} elseif ($template === 'campus_event') {
    $starter_sections = [
        ['type' => 'header', 'content' => '🎤 [Event Name]', 'style' => ['bg' => '#4a044e', 'color' => '#fae8ff', 'font_size' => '30px', 'font_weight' => 'bold']],
        ['type' => 'text',   'content' => "📅 Date: [Event Date]\n🕐 Time: [Start Time] – [End Time]\n📍 Venue: [Venue Name], [University]\n🎟️ Admission: [Free / ₱XX]\n\nJoin us for an unforgettable night of [brief event description]!", 'style' => ['text_align' => 'center', 'font_size' => '16px', 'color' => '#374151']],
        ['type' => 'divider','content' => '', 'style' => []],
        ['type' => 'text',   'content' => "📋 Program\n\n• [Time] — Registration & Welcome\n• [Time] — Opening Ceremony\n• [Time] — [Activity/Speaker]\n• [Time] — [Activity/Speaker]\n• [Time] — Closing Remarks\n\n👥 Organized by: [Org/Committee Name]\n📩 RSVP / Inquiries: eventname@university.edu", 'style' => ['font_size' => '16px', 'color' => '#1f2937']],
        ['type' => 'footer', 'content' => '[Event Name] — [University] | [Date]', 'style' => []],
    ];
}

foreach ($starter_sections as $index => $sec) {
    $type      = $sec['type'];
    $content   = $sec['content'];
    $pos       = $index + 1;
    $styleJson = json_encode($sec['style']);

    $stmt = $conn->prepare("INSERT INTO sections (page_id, type, content, position, style) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issis", $page_id, $type, $content, $pos, $styleJson);
    $stmt->execute();
}

header("Location: ../pages/editor.php?site_id=$site_id");
exit();
?>
