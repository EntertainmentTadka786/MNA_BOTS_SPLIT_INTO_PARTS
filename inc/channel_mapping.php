<?php
// ==============================
// CHANNEL MAPPING FUNCTIONS
// ==============================

function get_channel_id_by_username($username) {
    $username = strtolower(trim($username));
    
    $channel_map = [
        '@entertainmenttadka786' => MAIN_CHANNEL_ID,
        '@threater_print_movies' => THEATER_CHANNEL_ID,
        '@entertainment_tadka_serial_786' => SERIAL_CHANNEL_ID,
        '@etbackup' => BACKUP_CHANNEL_ID,
        '@entertainmenttadka7860' => REQUEST_GROUP_ID,
        'entertainmenttadka786' => MAIN_CHANNEL_ID,
        'threater_print_movies' => THEATER_CHANNEL_ID,
        'entertainment_tadka_serial_786' => SERIAL_CHANNEL_ID,
        'etbackup' => BACKUP_CHANNEL_ID,
        'entertainmenttadka7860' => REQUEST_GROUP_ID,
    ];
    
    return $channel_map[$username] ?? null;
}

function get_channel_type_by_id($channel_id) {
    $channel_id = strval($channel_id);
    
    if ($channel_id == MAIN_CHANNEL_ID) return 'main';
    if ($channel_id == THEATER_CHANNEL_ID) return 'theater';
    if ($channel_id == SERIAL_CHANNEL_ID) return 'serial';
    if ($channel_id == BACKUP_CHANNEL_ID) return 'backup';
    if ($channel_id == PRIVATE_CHANNEL_1_ID) return 'private';
    if ($channel_id == PRIVATE_CHANNEL_2_ID) return 'private2';
    if ($channel_id == REQUEST_GROUP_ID) return 'request_group';
    
    return 'other';
}

function get_channel_display_name($channel_type) {
    $names = [
        'main' => '🍿 Main Channel',
        'theater' => '🎭 Theater Prints',
        'serial' => '📺 Serial Channel',
        'backup' => '🔒 Backup Channel',
        'private' => '🔐 Private Channel',
        'private2' => '🔐 Private Channel 2',
        'request_group' => '📥 Request Group',
        'other' => '📢 Other Channel'
    ];
    
    return $names[$channel_type] ?? '📢 Unknown Channel';
}

function get_direct_channel_link($message_id, $channel_id) {
    if (empty($channel_id)) {
        return "Channel ID not available";
    }
    
    $channel_id_clean = str_replace('-100', '', $channel_id);
    return "https://t.me/c/" . $channel_id_clean . "/" . $message_id;
}

function get_channel_username_link($channel_type) {
    switch ($channel_type) {
        case 'main':
            return "https://t.me/" . ltrim(MAIN_CHANNEL, '@');
        case 'theater':
            return "https://t.me/" . ltrim(THEATER_CHANNEL, '@');
        case 'serial':
            return "https://t.me/" . ltrim(SERIAL_CHANNEL, '@');
        case 'backup':
            return "https://t.me/" . ltrim(BACKUP_CHANNEL_USERNAME, '@');
        case 'request_group':
            return "https://t.me/" . ltrim(REQUEST_GROUP, '@');
        default:
            return "https://t.me/EntertainmentTadka786";
    }
}

function get_channel_icon($channel_type) {
    switch ($channel_type) {
        case 'main':
            return '🍿';
        case 'theater':
            return '🎭';
        case 'serial':
            return '📺';
        case 'backup':
        case 'private':
        case 'private2':
            return '🔒';
        default:
            return '🎬';
    }
}
?>