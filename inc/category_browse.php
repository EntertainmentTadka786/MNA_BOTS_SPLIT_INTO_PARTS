<?php
// ==============================
// CATEGORY BROWSE SYSTEM
// ==============================

function show_category_menu($chat_id) {
    $all_movies = get_all_movies_list();
    
    $counts = [
        'main' => 0,
        'theater' => 0,
        'serial' => 0,
        'private' => 0,
        'backup' => 0
    ];
    
    foreach ($all_movies as $movie) {
        $type = $movie['channel_type'] ?? 'main';
        if (isset($counts[$type])) {
            $counts[$type]++;
        } else {
            $counts['private']++;
        }
    }
    
    $message = "📁 <b>BROWSE MOVIES</b>\n\n";
    $message .= "🍿 Main Channel      (" . $counts['main'] . ")\n";
    $message .= "🎭 Theater           (" . $counts['theater'] . ")\n";
    $message .= "📺 Serial            (" . $counts['serial'] . ")\n";
    $message .= "🔒 Backup/Private    (" . ($counts['backup'] + $counts['private']) . ")\n\n";
    $message .= "🔍 <i>Click any category to browse</i>";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🍿 Main Channel', 'callback_data' => 'cat_main_1'],
                ['text' => '🎭 Theater', 'callback_data' => 'cat_theater_1']
            ],
            [
                ['text' => '📺 Serial', 'callback_data' => 'cat_serial_1'],
                ['text' => '🔒 Backup/Private', 'callback_data' => 'cat_private_1']
            ],
            [
                ['text' => '🔍 Search Movies', 'switch_inline_query_current_chat' => ''],
                ['text' => '📝 Request Movie', 'callback_data' => 'cmd_request']
            ],
            [
                ['text' => '🔙 Back to Help', 'callback_data' => 'help_command']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
}

function show_category_movies($chat_id, $category, $page = 1) {
    $all_movies = get_all_movies_list();
    $filtered_movies = [];
    
    foreach ($all_movies as $movie) {
        $type = $movie['channel_type'] ?? 'main';
        
        if ($category == 'main' && $type == 'main') {
            $filtered_movies[] = $movie;
        } elseif ($category == 'theater' && $type == 'theater') {
            $filtered_movies[] = $movie;
        } elseif ($category == 'serial' && $type == 'serial') {
            $filtered_movies[] = $movie;
        } elseif ($category == 'private' && ($type == 'backup' || $type == 'private' || $type == 'private2')) {
            $filtered_movies[] = $movie;
        }
    }
    
    $total = count($filtered_movies);
    $items_per_page = 10;
    $total_pages = ceil($total / $items_per_page);
    $page = max(1, min($page, $total_pages));
    $start = ($page - 1) * $items_per_page;
    $movies_page = array_slice($filtered_movies, $start, $items_per_page);
    
    $category_names = [
        'main' => '🍿 MAIN CHANNEL',
        'theater' => '🎭 THEATER',
        'serial' => '📺 SERIAL',
        'private' => '🔒 BACKUP/PRIVATE'
    ];
    
    $icon = get_channel_icon($category);
    $name = $category_names[$category] ?? 'MOVIES';
    
    $message = $icon . " <b>" . $name . "</b> (Page " . $page . "/" . $total_pages . ")\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    if (empty($movies_page)) {
        $message .= "📭 No movies found in this category!";
    } else {
        $i = $start + 1;
        foreach ($movies_page as $movie) {
            $movie_icon = get_channel_icon($movie['channel_type'] ?? 'main');
            $message .= $i . ". " . $movie_icon . " " . htmlspecialchars($movie['movie_name']) . "\n";
            $i++;
        }
    }
    
    $message .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $nav_buttons = [];
    if ($page > 1) {
        $nav_buttons[] = ['text' => '◀️ PREV', 'callback_data' => 'cat_' . $category . '_' . ($page - 1)];
    }
    $nav_buttons[] = ['text' => $page . '/' . $total_pages, 'callback_data' => 'noop'];
    if ($page < $total_pages) {
        $nav_buttons[] = ['text' => 'NEXT ▶️', 'callback_data' => 'cat_' . $category . '_' . ($page + 1)];
    }
    
    $keyboard = [
        'inline_keyboard' => [
            $nav_buttons,
            [
                ['text' => '📥 SEND ALL', 'callback_data' => 'send_category_' . $category . '_' . $page],
                ['text' => '🔙 BACK', 'callback_data' => 'back_to_categories']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
}

function send_category_all_movies($chat_id, $category, $page) {
    $all_movies = get_all_movies_list();
    $filtered_movies = [];
    
    foreach ($all_movies as $movie) {
        $type = $movie['channel_type'] ?? 'main';
        
        if ($category == 'main' && $type == 'main') {
            $filtered_movies[] = $movie;
        } elseif ($category == 'theater' && $type == 'theater') {
            $filtered_movies[] = $movie;
        } elseif ($category == 'serial' && $type == 'serial') {
            $filtered_movies[] = $movie;
        } elseif ($category == 'private' && ($type == 'backup' || $type == 'private' || $type == 'private2')) {
            $filtered_movies[] = $movie;
        }
    }
    
    $items_per_page = 10;
    $start = ($page - 1) * $items_per_page;
    $movies_page = array_slice($filtered_movies, $start, $items_per_page);
    
    $total = count($movies_page);
    $success = 0;
    $failed = 0;
    
    $progress_msg = sendMessage($chat_id, "📦 Sending " . $total . " movies...\n\nProgress: 0%");
    $progress_id = $progress_msg['result']['message_id'];
    
    foreach ($movies_page as $index => $movie) {
        $result = deliver_item_to_chat($chat_id, $movie);
        if ($result) {
            $success++;
        } else {
            $failed++;
        }
        
        if (($index + 1) % 2 == 0) {
            $progress = round((($index + 1) / $total) * 100);
            editMessage($chat_id, $progress_id, "📦 Sending " . $total . " movies...\n\nProgress: " . $progress . "%\n✅ Sent: " . $success . "\n❌ Failed: " . $failed);
        }
        usleep(300000);
    }
    
    editMessage($chat_id, $progress_id, "✅ Complete!\n\n📊 Sent: " . $success . "/" . $total . " movies\n❌ Failed: " . $failed);
}
?>