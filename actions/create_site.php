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

        // ── BUSINESS ──────────────────────────────────────────────────────────
        'business_corporate' => [
            ['type'=>'header','content'=>$site_name,'style'=>json_encode(['bg'=>'#1e3a5f','color'=>'#ffffff','text_align'=>'left','font_size'=>'22px','font_weight'=>'bold','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'hero','content'=>'Building the Future with '.$site_name,'style'=>'{}'],
            ['type'=>'image','content'=>'https://images.unsplash.com/photo-1497366216548-37526070297c?w=1200&q=80','style'=>json_encode(['img_width'=>0,'img_height'=>400,'img_x'=>0,'img_y'=>0])],
            ['type'=>'text','content'=>$site_name.' is a leading enterprise consulting firm specializing in strategy, operations, and digital transformation. We partner with Fortune-class companies to drive measurable growth and lasting impact.','style'=>json_encode(['text_align'=>'center','font_size'=>'17px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'OUR SERVICES','style'=>json_encode(['text_align'=>'center','font_size'=>'22px','color'=>'#1e3a5f','font_weight'=>'bold','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'text','content'=>'📊 Business Strategy   |   💼 Management Consulting   |   🔧 Process Optimization   |   🌐 Digital Transformation','style'=>json_encode(['text_align'=>'center','font_size'=>'15px','color'=>'#2563eb','font_weight'=>'600','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'📞 Get in Touch\n\n📧 contact@'.strtolower(str_replace(' ','',$site_name)).'.com\n☎️ +1 (800) 123-4567\n🏢 123 Enterprise Blvd, Business District','style'=>json_encode(['text_align'=>'center','font_size'=>'16px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'button','content'=>'Schedule a Consultation','style'=>json_encode(['url'=>'#','bg'=>'#2563eb','color'=>'#ffffff','text_align'=>'center','font_size'=>'17px','font_weight'=>'bold','radius'=>'10px'])],
            ['type'=>'footer','content'=>'© 2026 '.$site_name.'. All Rights Reserved.','style'=>'{}'],
        ],

        'business_agency' => [
            ['type'=>'header','content'=>$site_name.' Studio','style'=>json_encode(['bg'=>'#0f0f0f','color'=>'#ffffff','text_align'=>'left','font_size'=>'24px','font_weight'=>'900','font_family'=>"'Montserrat', sans-serif"])],
            ['type'=>'hero','content'=>'We Create. We Build. We Grow.','style'=>'{}'],
            ['type'=>'text','content'=>$site_name.' is a full-service creative agency. We turn bold ideas into unforgettable brands, digital experiences, and campaigns that move people.','style'=>json_encode(['text_align'=>'center','font_size'=>'18px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Montserrat', sans-serif"])],
            ['type'=>'image','content'=>'https://images.unsplash.com/photo-1552664730-d307ca884978?w=1200&q=80','style'=>json_encode(['img_width'=>0,'img_height'=>400,'img_x'=>0,'img_y'=>0])],
            ['type'=>'text','content'=>'WHAT WE DO','style'=>json_encode(['text_align'=>'center','font_size'=>'24px','color'=>'#6c3afc','font_weight'=>'900','font_family'=>"'Montserrat', sans-serif"])],
            ['type'=>'text','content'=>'🎨 Branding & Identity   ✦   💻 Web Design & Development   ✦   📱 Social Media   ✦   📢 Campaigns','style'=>json_encode(['text_align'=>'center','font_size'=>'15px','color'=>'#374151','font_weight'=>'600','font_family'=>"'Montserrat', sans-serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'"We don\'t just deliver work — we deliver results that matter."','style'=>json_encode(['text_align'=>'center','font_size'=>'20px','color'=>'#6c3afc','font_weight'=>'bold','font_family'=>"'Montserrat', sans-serif"])],
            ['type'=>'button','content'=>'Start a Project With Us →','style'=>json_encode(['url'=>'#','bg'=>'#6c3afc','color'=>'#ffffff','text_align'=>'center','font_size'=>'17px','font_weight'=>'bold','radius'=>'999px'])],
            ['type'=>'footer','content'=>'© 2026 '.$site_name.' Studio. Crafted with passion.','style'=>'{}'],
        ],

        // ── SHOP ──────────────────────────────────────────────────────────────
        'shop_boutique' => [
            ['type'=>'header','content'=>'✦ '.$site_name,'style'=>json_encode(['bg'=>'#1a1a2e','color'=>'#f9d77e','text_align'=>'left','font_size'=>'22px','font_weight'=>'bold','font_family'=>"'Playfair Display', serif"])],
            ['type'=>'hero','content'=>$site_name.' — New Collection 2026','style'=>'{}'],
            ['type'=>'image','content'=>'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1200&q=80','style'=>json_encode(['img_width'=>0,'img_height'=>400,'img_x'=>0,'img_y'=>0])],
            ['type'=>'text','content'=>'Discover curated pieces crafted for the modern wardrobe. Every item at '.$site_name.' is handpicked for quality, style, and timeless elegance.','style'=>json_encode(['text_align'=>'center','font_size'=>'17px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Playfair Display', serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'SHOP BY CATEGORY','style'=>json_encode(['text_align'=>'center','font_size'=>'22px','color'=>'#1a1a2e','font_weight'=>'bold','font_family'=>"'Playfair Display', serif"])],
            ['type'=>'text','content'=>'👗 Women\'s   |   👔 Men\'s   |   👟 Footwear   |   👜 Accessories   |   🎁 Gift Sets','style'=>json_encode(['text_align'=>'center','font_size'=>'15px','color'=>'#78350f','font_weight'=>'600','font_family'=>"'Playfair Display', serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'✨ Free Shipping on orders over ₱1,500\n🔄 30-Day Free Returns\n🔒 Secure Payment · Cash on Delivery Available','style'=>json_encode(['text_align'=>'center','font_size'=>'15px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Playfair Display', serif"])],
            ['type'=>'button','content'=>'Shop the Collection →','style'=>json_encode(['url'=>'#','bg'=>'#1a1a2e','color'=>'#f9d77e','text_align'=>'center','font_size'=>'17px','font_weight'=>'bold','radius'=>'4px'])],
            ['type'=>'footer','content'=>'© 2026 '.$site_name.'. Style that speaks.','style'=>'{}'],
        ],

        'shop_tech' => [
            ['type'=>'header','content'=>'⚡ '.$site_name,'style'=>json_encode(['bg'=>'#0f172a','color'=>'#38bdf8','text_align'=>'left','font_size'=>'22px','font_weight'=>'bold','font_family'=>"'Orbitron', sans-serif"])],
            ['type'=>'hero','content'=>$site_name.' — Next-Gen Gadgets & Tech','style'=>'{}'],
            ['type'=>'image','content'=>'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1200&q=80','style'=>json_encode(['img_width'=>0,'img_height'=>400,'img_x'=>0,'img_y'=>0])],
            ['type'=>'text','content'=>'Power up your life with the latest in technology. '.$site_name.' brings you cutting-edge gadgets, accessories, and electronics at unbeatable prices.','style'=>json_encode(['text_align'=>'center','font_size'=>'17px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'FEATURED CATEGORIES','style'=>json_encode(['text_align'=>'center','font_size'=>'22px','color'=>'#0f172a','font_weight'=>'bold','font_family'=>"'Orbitron', sans-serif"])],
            ['type'=>'text','content'=>'📱 Smartphones   |   💻 Laptops & PCs   |   🎧 Audio   |   📷 Cameras   |   🎮 Gaming','style'=>json_encode(['text_align'=>'center','font_size'=>'15px','color'=>'#0284c7','font_weight'=>'600','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'🚀 Same-Day Delivery Available\n✅ Official Warranty on All Products\n💳 0% Installment · GCash · Credit Card','style'=>json_encode(['text_align'=>'center','font_size'=>'15px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'button','content'=>'Explore Products ⚡','style'=>json_encode(['url'=>'#','bg'=>'#38bdf8','color'=>'#0f172a','text_align'=>'center','font_size'=>'17px','font_weight'=>'bold','radius'=>'8px'])],
            ['type'=>'footer','content'=>'© 2026 '.$site_name.'. The future is here.','style'=>'{}'],
        ],

        // ── RESTAURANT ────────────────────────────────────────────────────────
        'restaurant_finedining' => [
            ['type'=>'header','content'=>$site_name,'style'=>json_encode(['bg'=>'#1c1208','color'=>'#d4af37','text_align'=>'center','font_size'=>'26px','font_weight'=>'bold','font_family'=>"'Playfair Display', serif"])],
            ['type'=>'hero','content'=>$site_name.' — Exquisite Cuisine','style'=>'{}'],
            ['type'=>'image','content'=>'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&q=80','style'=>json_encode(['img_width'=>0,'img_height'=>400,'img_x'=>0,'img_y'=>0])],
            ['type'=>'text','content'=>'Experience the art of fine dining at '.$site_name.'. Our award-winning chefs craft each dish with seasonal, locally sourced ingredients — where every meal tells a story.','style'=>json_encode(['text_align'=>'center','font_size'=>'18px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Playfair Display', serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'OUR MENU','style'=>json_encode(['text_align'=>'center','font_size'=>'24px','color'=>'#1c1208','font_weight'=>'bold','font_family'=>"'Playfair Display', serif"])],
            ['type'=>'text','content'=>'🥗 Starters & Salads   ·   🥩 Prime Mains   ·   🍮 Desserts   ·   🍷 Wine & Cocktails\n\nTasting Menu available every Friday & Saturday evening.','style'=>json_encode(['text_align'=>'center','font_size'=>'16px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Playfair Display', serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'📍 12 Heritage Lane, BGC, Manila\n⏰ Tuesday – Sunday · 6:00 PM – 11:00 PM\n📞 Reservations: +63 917 555 1234','style'=>json_encode(['text_align'=>'center','font_size'=>'16px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Playfair Display', serif"])],
            ['type'=>'button','content'=>'Reserve a Table','style'=>json_encode(['url'=>'#','bg'=>'#1c1208','color'=>'#d4af37','text_align'=>'center','font_size'=>'17px','font_weight'=>'bold','radius'=>'4px'])],
            ['type'=>'footer','content'=>'© 2026 '.$site_name.'. Taste the extraordinary.','style'=>'{}'],
        ],

        'restaurant_casual' => [
            ['type'=>'header','content'=>'🍔 '.$site_name,'style'=>json_encode(['bg'=>'#dc2626','color'=>'#ffffff','text_align'=>'left','font_size'=>'24px','font_weight'=>'900','font_family'=>"'Nunito', sans-serif"])],
            ['type'=>'hero','content'=>$site_name.' — Real Food. Real Fast.','style'=>'{}'],
            ['type'=>'image','content'=>'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=1200&q=80','style'=>json_encode(['img_width'=>0,'img_height'=>400,'img_x'=>0,'img_y'=>0])],
            ['type'=>'text','content'=>'Fresh ingredients. Bold flavors. Lightning-fast service. Whether you\'re dining in or ordering for delivery, '.$site_name.' has something delicious for everyone!','style'=>json_encode(['text_align'=>'center','font_size'=>'17px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Nunito', sans-serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'WHAT\'S ON THE MENU','style'=>json_encode(['text_align'=>'center','font_size'=>'22px','color'=>'#dc2626','font_weight'=>'900','font_family'=>"'Nunito', sans-serif"])],
            ['type'=>'text','content'=>'🍔 Smash Burgers   |   🍝 Pasta & Bowls   |   🍟 Sides & Fries   |   🥤 Drinks & Shakes\n\nDaily specials · Family bundles · Party trays available!','style'=>json_encode(['text_align'=>'center','font_size'=>'15px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Nunito', sans-serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'📍 Open Daily 10:00 AM – 11:00 PM\n🛵 Delivery via GrabFood & FoodPanda\n📞 Call to Order: +63 912 888 9999','style'=>json_encode(['text_align'=>'center','font_size'=>'15px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Nunito', sans-serif"])],
            ['type'=>'button','content'=>'Order Now 🛵','style'=>json_encode(['url'=>'#','bg'=>'#dc2626','color'=>'#ffffff','text_align'=>'center','font_size'=>'17px','font_weight'=>'bold','radius'=>'999px'])],
            ['type'=>'footer','content'=>'© 2026 '.$site_name.'. Come hungry. Leave happy.','style'=>'{}'],
        ],

        // ── MUSIC ─────────────────────────────────────────────────────────────
        'music_band' => [
            ['type'=>'header','content'=>$site_name,'style'=>json_encode(['bg'=>'#09090b','color'=>'#a855f7','text_align'=>'left','font_size'=>'24px','font_weight'=>'900','font_family'=>"'Bebas Neue', cursive"])],
            ['type'=>'hero','content'=>$site_name.' — Official Site','style'=>'{}'],
            ['type'=>'image','content'=>'https://images.unsplash.com/photo-1501386761578-eaa54b9d9e8f?w=1200&q=80','style'=>json_encode(['img_width'=>0,'img_height'=>400,'img_x'=>0,'img_y'=>0])],
            ['type'=>'text','content'=>$site_name.' is a 4-piece rock band from Manila blending heavy riffs, atmospheric synths, and raw emotion into music that hits hard and stays with you.','style'=>json_encode(['text_align'=>'center','font_size'=>'17px','color'=>'#d4d4d8','font_weight'=>'normal','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'🎵 LATEST RELEASE: "STATIC" — OUT NOW','style'=>json_encode(['text_align'=>'center','font_size'=>'22px','color'=>'#a855f7','font_weight'=>'900','font_family'=>"'Bebas Neue', cursive"])],
            ['type'=>'text','content'=>"Track List:\n1. Ignition\n2. Neon Collapse\n3. Frequency\n4. Ghost Signal\n5. Static (Title Track)\n\nAvailable on Spotify · Apple Music · YouTube Music","style"=>json_encode(['text_align'=>'center','font_size'=>'15px','color'=>'#d4d4d8','font_weight'=>'normal','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>"🎤 UPCOMING SHOWS\n\nJune 7 — Saguijo Cafe, Makati\nJune 21 — 123 Block, BGC\nJuly 4 — Music Museum, QC\n\nGet tickets at ticketnet.com.ph",'style'=>json_encode(['text_align'=>'center','font_size'=>'15px','color'=>'#d4d4d8','font_weight'=>'normal','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'button','content'=>'🎟️ Get Tickets','style'=>json_encode(['url'=>'#','bg'=>'#a855f7','color'=>'#ffffff','text_align'=>'center','font_size'=>'17px','font_weight'=>'bold','radius'=>'8px'])],
            ['type'=>'footer','content'=>'© 2026 '.$site_name.'. All rights reserved. Loud & Proud.','style'=>'{}'],
        ],

        'music_solo' => [
            ['type'=>'header','content'=>$site_name,'style'=>json_encode(['bg'=>'#ffffff','color'=>'#1a1a2e','text_align'=>'center','font_size'=>'24px','font_weight'=>'bold','font_family'=>"'Dancing Script', cursive"])],
            ['type'=>'hero','content'=>$site_name.' — Singer · Songwriter','style'=>'{}'],
            ['type'=>'image','content'=>'https://images.unsplash.com/photo-1516280440614-37939bbacd81?w=1200&q=80','style'=>json_encode(['img_width'=>0,'img_height'=>400,'img_x'=>0,'img_y'=>0])],
            ['type'=>'text','content'=>'"Echoes" — my debut single — is finally here. Written at 2am, born from silence, and made for everyone who has ever felt too much. Stream it now.','style'=>json_encode(['text_align'=>'center','font_size'=>'18px','color'=>'#7e22ce','font_weight'=>'normal','font_family'=>"'Dancing Script', cursive"])],
            ['type'=>'button','content'=>'▶ Stream "Echoes" Now','style'=>json_encode(['url'=>'#','bg'=>'linear-gradient(135deg,#7e22ce,#c026d3)','color'=>'#ffffff','text_align'=>'center','font_size'=>'17px','font_weight'=>'bold','radius'=>'999px'])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'TOUR DATES','style'=>json_encode(['text_align'=>'center','font_size'=>'22px','color'=>'#7e22ce','font_weight'=>'bold','font_family'=>"'Dancing Script', cursive"])],
            ['type'=>'text','content'=>"🗓️ July 12 — Teatrino, Greenhills\n🗓️ July 26 — 19 East, Sucat\n🗓️ August 9 — Arete, Ateneo\n\nTickets available at KTX.ph",'style'=>json_encode(['text_align'=>'center','font_size'=>'16px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'Follow Along\n\n📸 Instagram: @'.strtolower(str_replace(' ','',$site_name))."\n🎵 TikTok: @".strtolower(str_replace(' ','',$site_name))."\n🎧 Spotify: Search \"$site_name\"",'style'=>json_encode(['text_align'=>'center','font_size'=>'15px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'footer','content'=>'© 2026 '.$site_name.'. Made with love and late nights.','style'=>'{}'],
        ],

        // ── EVENT ─────────────────────────────────────────────────────────────
        'event_wedding' => [
            ['type'=>'header','content'=>'💍 '.$site_name,'style'=>json_encode(['bg'=>'#fff1f5','color'=>'#be185d','text_align'=>'center','font_size'=>'24px','font_weight'=>'bold','font_family'=>"'Playfair Display', serif"])],
            ['type'=>'hero','content'=>'Together Forever — '.$site_name,'style'=>'{}'],
            ['type'=>'image','content'=>'https://images.unsplash.com/photo-1519741497674-611481863552?w=1200&q=80','style'=>json_encode(['img_width'=>0,'img_height'=>400,'img_x'=>0,'img_y'=>0])],
            ['type'=>'text','content'=>'With hearts full of joy, we invite you to celebrate the beginning of our forever. Please join us as we exchange our vows and begin this beautiful journey together.','style'=>json_encode(['text_align'=>'center','font_size'=>'18px','color'=>'#9d174d','font_weight'=>'normal','font_family'=>"'Playfair Display', serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'WEDDING DETAILS','style'=>json_encode(['text_align'=>'center','font_size'=>'24px','color'=>'#be185d','font_weight'=>'bold','font_family'=>"'Playfair Display', serif"])],
            ['type'=>'text','content'=>"💍 Ceremony: June 15, 2026 · 3:00 PM\n🥂 Reception: 6:00 PM onwards\n📍 The Grand Ballroom, Manila Hotel\n👗 Dress Code: Formal / Black Tie\n🌸 Motif: Dusty Rose & Gold",'style'=>json_encode(['text_align'=>'center','font_size'=>'17px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Playfair Display', serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>"💌 RSVP\n\nKindly confirm your attendance by June 1, 2026\n📧 rsvp@".strtolower(str_replace(' ','',$site_name)).".com\n📞 +63 917 333 4455\n\n🎉 We can't wait to celebrate with you!",'style'=>json_encode(['text_align'=>'center','font_size'=>'16px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Playfair Display', serif"])],
            ['type'=>'button','content'=>'💌 RSVP Now','style'=>json_encode(['url'=>'#','bg'=>'#be185d','color'=>'#ffffff','text_align'=>'center','font_size'=>'17px','font_weight'=>'bold','radius'=>'999px'])],
            ['type'=>'footer','content'=>'© 2026 '.$site_name.'. With love. ♥','style'=>'{}'],
        ],

        'event_conference' => [
            ['type'=>'header','content'=>$site_name.' 2026','style'=>json_encode(['bg'=>'#1e3a5f','color'=>'#ffffff','text_align'=>'left','font_size'=>'22px','font_weight'=>'bold','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'hero','content'=>$site_name.' — Innovate. Connect. Grow.','style'=>'{}'],
            ['type'=>'image','content'=>'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1200&q=80','style'=>json_encode(['img_width'=>0,'img_height'=>400,'img_x'=>0,'img_y'=>0])],
            ['type'=>'text','content'=>'Join industry leaders, innovators, and change-makers at '.$site_name.' 2026 — the region\'s premier annual summit for technology, business, and leadership.','style'=>json_encode(['text_align'=>'center','font_size'=>'17px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>'EVENT DETAILS','style'=>json_encode(['text_align'=>'center','font_size'=>'22px','color'=>'#1e3a5f','font_weight'=>'bold','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'text','content'=>"📅 Date: August 20–22, 2026\n⏰ Time: 8:00 AM – 6:00 PM\n📍 SMX Convention Center, Manila\n🎤 50+ Speakers · 30+ Sessions · 1,200+ Attendees",'style'=>json_encode(['text_align'=>'center','font_size'=>'16px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'divider','content'=>'','style'=>'{}'],
            ['type'=>'text','content'=>"🗓️ AGENDA HIGHLIGHTS\n\nDay 1: Opening Keynotes · Industry Panels\nDay 2: Workshops · Startup Pitch Competition\nDay 3: Networking Day · Awards Night\n\n📧 info@".strtolower(str_replace(' ','',$site_name)).".com   ·   📞 +63 2 8888 0000",'style'=>json_encode(['text_align'=>'center','font_size'=>'15px','color'=>'#374151','font_weight'=>'normal','font_family'=>"'Inter', sans-serif"])],
            ['type'=>'button','content'=>'Register Now →','style'=>json_encode(['url'=>'#','bg'=>'#2563eb','color'=>'#ffffff','text_align'=>'center','font_size'=>'17px','font_weight'=>'bold','radius'=>'10px'])],
            ['type'=>'footer','content'=>'© 2026 '.$site_name.'. All rights reserved.','style'=>'{}'],
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
