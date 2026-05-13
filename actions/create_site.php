<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}
include "../config/db.php";

$site_name = trim($_POST['site_name'] ?? '');
$template  = $_POST['template'] ?? 'blank';
$user_id   = $_SESSION['user_id'];

if ($site_name !== '') {
    // Create the site
    $stmt = $conn->prepare("INSERT INTO sites (user_id, site_name) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $site_name);
    $stmt->execute();
    $site_id = $stmt->insert_id;
    $stmt->close();

    // Create default page
    $stmt = $conn->prepare("INSERT INTO pages (site_id, title, slug) VALUES (?, 'Home', 'home')");
    $stmt->bind_param("i", $site_id);
    $stmt->execute();
    $page_id = $stmt->insert_id;
    $stmt->close();



    // Define templates
    $templates = [

        'blank' => [],

        'business' => [
            ['type'=>'header','content'=>$site_name,'style'=>json_encode(['bg'=>'#1a1a2e','color'=>'#ffffff','text_align'=>'left','font_size'=>'24px','font_weight'=>'bold','font_family'=>'Arial, sans-serif'])],
            ['type'=>'hero','content'=>'Welcome to '.$site_name,'style'=>'{}'],
            ['type'=>'text','content'=>'We are a professional business dedicated to delivering excellence. Our team of experts is here to help you achieve your goals.','style'=>json_encode(['text_align'=>'center','font_size'=>'18px','color'=>'#374151','font_weight'=>'normal','font_family'=>'Arial, sans-serif'])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'Our Services: Strategy | Consulting | Development | Support','style'=>json_encode(['text_align'=>'center','font_size'=>'16px','color'=>'#6c3afc','font_weight'=>'bold','font_family'=>'Arial, sans-serif'])],
            ['type'=>'footer','content'=>'© 2026 '.$site_name.'. All rights reserved.','style'=>'{}'],
        ],

        'shop' => [
            ['type'=>'header','content'=>$site_name.' Shop','style'=>json_encode(['bg'=>'#1a1a2e','color'=>'#ffffff','text_align'=>'left','font_size'=>'24px','font_weight'=>'bold','font_family'=>'Arial, sans-serif'])],
            ['type'=>'hero','content'=>'Shop at '.$site_name,'style'=>'{}'],
            ['type'=>'text','content'=>'🛒 Featured Products\n\nDiscover our amazing collection of products. Quality guaranteed, fast shipping, and great prices!','style'=>json_encode(['text_align'=>'center','font_size'=>'18px','color'=>'#374151','font_weight'=>'normal','font_family'=>'Arial, sans-serif'])],
            ['type'=>'image','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'Free Shipping on orders over ₱500 | Easy Returns | Secure Payment','style'=>json_encode(['text_align'=>'center','font_size'=>'16px','color'=>'#6c3afc','font_weight'=>'bold','font_family'=>'Arial, sans-serif'])],
            ['type'=>'footer','content'=>'© 2026 '.$site_name.' Shop. All rights reserved.','style'=>'{}'],
        ],

        'restaurant' => [
            ['type'=>'header','content'=>$site_name,'style'=>json_encode(['bg'=>'#7f1d1d','color'=>'#fff7ed','text_align'=>'left','font_size'=>'24px','font_weight'=>'bold','font_family'=>'Georgia, serif'])],
            ['type'=>'hero','content'=>'Welcome to '.$site_name,'style'=>'{}'],
            ['type'=>'text','content'=>'🍽️ Our Menu\n\nAppetizers | Main Course | Desserts | Drinks\n\nWe serve authentic, freshly prepared dishes made with the finest local ingredients. Dine in or take out!','style'=>json_encode(['text_align'=>'center','font_size'=>'18px','color'=>'#374151','font_weight'=>'normal','font_family'=>'Georgia, serif'])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'📍 Location: 123 Food Street\n⏰ Hours: Mon-Sun 10AM - 10PM\n📞 Reservations: +63 912 345 6789','style'=>json_encode(['text_align'=>'center','font_size'=>'16px','color'=>'#374151','font_weight'=>'normal','font_family'=>'Arial, sans-serif'])],
            ['type'=>'footer','content'=>'© 2026 '.$site_name.'. Taste the difference.','style'=>'{}'],
        ],

        'music' => [
            ['type'=>'header','content'=>$site_name,'style'=>json_encode(['bg'=>'#18181b','color'=>'#a855f7','text_align'=>'left','font_size'=>'24px','font_weight'=>'bold','font_family'=>'Arial, sans-serif'])],
            ['type'=>'hero','content'=>$site_name.' — Official Website','style'=>'{}'],
            ['type'=>'text','content'=>'🎵 Latest Album: "New Horizons"\n\nNow available on Spotify, Apple Music, and YouTube!\n\nTrack List:\n1. Opening Act\n2. Rise Up\n3. Midnight Dreams\n4. Final Encore','style'=>json_encode(['text_align'=>'center','font_size'=>'17px','color'=>'#374151','font_weight'=>'normal','font_family'=>'Arial, sans-serif'])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'🎤 Upcoming Shows\n\nMay 20 — Manila | June 5 — Cebu | June 20 — Davao\n\nGet your tickets now at ticketnet.com.ph','style'=>json_encode(['text_align'=>'center','font_size'=>'16px','color'=>'#374151','font_weight'=>'normal','font_family'=>'Arial, sans-serif'])],
            ['type'=>'footer','content'=>'© 2026 '.$site_name.'. All rights reserved.','style'=>'{}'],
        ],

        'event' => [
            ['type'=>'header','content'=>$site_name,'style'=>json_encode(['bg'=>'#4a044e','color'=>'#fdf4ff','text_align'=>'left','font_size'=>'24px','font_weight'=>'bold','font_family'=>'Georgia, serif'])],
            ['type'=>'hero','content'=>'You Are Invited to '.$site_name,'style'=>'{}'],
            ['type'=>'text','content'=>'📅 Event Details\n\nDate: June 15, 2026\nTime: 4:00 PM\nVenue: Grand Ballroom, Hotel Name\nDress Code: Formal','style'=>json_encode(['text_align'=>'center','font_size'=>'18px','color'=>'#374151','font_weight'=>'normal','font_family'=>'Georgia, serif'])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'💌 RSVP\n\nKindly confirm your attendance by June 1, 2026\n📞 Contact: +63 912 345 6789\n📧 Email: rsvp@event.com\n\n🎉 We look forward to celebrating with you!','style'=>json_encode(['text_align'=>'center','font_size'=>'16px','color'=>'#374151','font_weight'=>'normal','font_family'=>'Georgia, serif'])],
            ['type'=>'footer','content'=>'© 2026 '.$site_name.'. With love.','style'=>'{}'],
        ],

    ];

    // Insert sections for chosen template
    $sections = $templates[$template] ?? [];
    foreach ($sections as $pos => $sec) {
        $type    = $sec['type'];
        $content = $sec['content'];
        $style   = $sec['style'];
        $pos++;
        $stmt = $conn->prepare("INSERT INTO sections (page_id, type, content, style, position) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $page_id, $type, $content, $style, $pos);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: ../pages/dashboard.php");
exit();
?>
