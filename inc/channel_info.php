<?php
// ==============================
// CHANNEL MANAGEMENT FUNCTIONS
// ==============================

function show_channel_info($chat_id) {
    $message = "📢 <b>Join Our Channels</b>\n\n";
    
    $message .= "🔥 <b>Channels:</b>\n";
    $message .= "🍿 Main: @EntertainmentTadka786\n";
    $message .= "📥 Request: @EntertainmentTadka7860\n";
    $message .= "🎭 Theater: @threater_print_movies\n";
    $message .= "📂 Backup: @ETBackup\n";
    $message .= "📺 Serial: @Entertainment_Tadka_Serial_786\n\n";
    
    $message .= "🎯 <b>How to Use:</b>\n";
    $message .= "• Simply type movie name to search\n";
    $message .= "• Use /request for missing movies\n\n";
    
    $message .= "🔔 <b>Don't forget to join all channels!</b>";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🍿 Main', 'url' => 'https://t.me/EntertainmentTadka786'],
                ['text' => '📥 Request', 'url' => 'https://t.me/EntertainmentTadka7860']
            ],
            [
                ['text' => '🎭 Theater', 'url' => 'https://t.me/threater_print_movies'],
                ['text' => '📂 Backup', 'url' => 'https://t.me/ETBackup']
            ],
            [
                ['text' => '📺 Serial', 'url' => 'https://t.me/Entertainment_Tadka_Serial_786']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
}

function show_main_channel_info($chat_id) {
    $message = "🍿 <b>Main Channel - " . MAIN_CHANNEL . "</b>\n\n";
    
    $message .= "🎬 <b>What you get:</b>\n";
    $message .= "• Latest Bollywood & Hollywood movies\n";
    $message .= "• Daily new uploads\n";
    $message .= "• Fast direct downloads\n\n";
    
    $message .= "📊 <b>Current Stats:</b>\n";
    $stats = get_stats();
    $message .= "• Total Movies: " . ($stats['total_movies'] ?? 0) . "\n\n";
    
    $message .= "🔔 <b>Join now for latest movies!</b>";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🍿 Join Main Channel', 'url' => 'https://t.me/EntertainmentTadka786']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
}

function show_request_channel_info($chat_id) {
    $message = "📥 <b>Requests Channel - " . REQUEST_GROUP . "</b>\n\n";
    
    $message .= "🎯 <b>How to request movies:</b>\n";
    $message .= "1. Join this channel first\n";
    $message .= "2. Use <code>/request movie_name</code> in bot\n";
    $message .= "3. We'll add within 24 hours\n\n";
    
    $message .= "📝 <b>Also available:</b>\n";
    $message .= "• Bug reports & issues\n";
    $message .= "• Feature suggestions\n";
    $message .= "• Bot help & guidance\n\n";
    
    $message .= "⚠️ <b>Please check these before requesting:</b>\n";
    $message .= "• Search in bot first\n";
    $message .= "• Check spelling\n";
    $message .= "• Use correct movie name\n\n";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📥 Join Requests Channel', 'url' => 'https://t.me/EntertainmentTadka7860']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
}

function show_theater_channel_info($chat_id) {
    $message = "🎭 <b>Theater Prints - " . THEATER_CHANNEL . "</b>\n\n";
    
    $message .= "🎥 <b>What you get:</b>\n";
    $message .= "• Latest theater prints\n";
    $message .= "• HD screen recordings\n";
    $message .= "• Fast uploads after release\n\n";
    
    $message .= "📥 <b>How to access:</b>\n";
    $message .= "1. Join " . THEATER_CHANNEL . "\n";
    $message .= "2. Search in bot\n";
    $message .= "3. Get movie info\n\n";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🎭 Join Theater Channel', 'url' => 'https://t.me/threater_print_movies']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
}

function show_serial_channel_info($chat_id) {
    $message = "📺 <b>Serial Channel - " . SERIAL_CHANNEL . "</b>\n\n";
    
    $message .= "📺 <b>What you get:</b>\n";
    $message .= "• Latest web series\n";
    $message .= "• TV serial episodes\n";
    $message .= "• All seasons available\n";
    $message .= "• Regular updates\n\n";
    
    $message .= "🔥 <b>Popular Series:</b>\n";
    $message .= "• Squid Game All Seasons\n";
    $message .= "• Now You See Me All Parts\n";
    $message .= "• Taskaree S01 (2025)\n\n";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📺 Join Serial Channel', 'url' => 'https://t.me/Entertainment_Tadka_Serial_786']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
}

function show_backup_channel_info($chat_id) {
    $message = "🔒 <b>Backup Channel - " . BACKUP_CHANNEL_USERNAME . "</b>\n\n";
    
    $message .= "🛡️ <b>Purpose:</b>\n";
    $message .= "• Secure data backups\n";
    $message .= "• Database protection\n";
    $message .= "• Disaster prevention\n\n";
    
    $message .= "💾 <b>What's backed up:</b>\n";
    $message .= "• Movies database\n";
    $message .= "• Users data\n";
    $message .= "• Bot statistics\n\n";
    
    $message .= "🔐 <b>Note:</b> This is a private channel for admin use only.";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🔒 ' . BACKUP_CHANNEL_USERNAME, 'url' => 'https://t.me/ETBackup']
            ]
        ]
    ];
    
    if ($chat_id == ADMIN_ID) {
        sendMessage($chat_id, $message, $keyboard, 'HTML');
    } else {
        sendMessage($chat_id, "🔒 <b>Backup Channel</b>\n\nThis is a private admin-only channel for data protection.", null, 'HTML');
    }
}
?>