<?php
// ==============================
// FORWARD HEADER SETTINGS SYSTEM
// ==============================

function initialize_forward_settings() {
    if (!file_exists(FORWARD_SETTINGS_FILE)) {
        $default_settings = [
            'public_channels' => [
                MAIN_CHANNEL_ID => ['forward_header' => true, 'name' => 'Main Channel'],
                THEATER_CHANNEL_ID => ['forward_header' => true, 'name' => 'Theater Channel'],
                SERIAL_CHANNEL_ID => ['forward_header' => true, 'name' => 'Serial Channel'],
                REQUEST_GROUP_ID => ['forward_header' => true, 'name' => 'Request Group']
            ],
            'private_channels' => [
                PRIVATE_CHANNEL_1_ID => ['forward_header' => false, 'name' => 'Private Channel 1'],
                PRIVATE_CHANNEL_2_ID => ['forward_header' => false, 'name' => 'Private Channel 2'],
                BACKUP_CHANNEL_ID => ['forward_header' => false, 'name' => 'Backup Channel']
            ],
            'last_updated' => date('Y-m-d H:i:s')
        ];
        file_put_contents(FORWARD_SETTINGS_FILE, json_encode($default_settings, JSON_PRETTY_PRINT));
    }
    return json_decode(file_get_contents(FORWARD_SETTINGS_FILE), true);
}

function get_forward_header_setting($channel_id) {
    $settings = initialize_forward_settings();
    
    if (isset($settings['private_channels'][$channel_id])) {
        return $settings['private_channels'][$channel_id]['forward_header'];
    }
    
    if (isset($settings['public_channels'][$channel_id])) {
        return $settings['public_channels'][$channel_id]['forward_header'];
    }
    
    return false;
}

function set_forward_header_setting($channel_id, $enabled, $channel_type = 'private') {
    $settings = initialize_forward_settings();
    
    if ($channel_type == 'private') {
        if (!isset($settings['private_channels'][$channel_id])) {
            $settings['private_channels'][$channel_id] = [
                'forward_header' => $enabled,
                'name' => 'Channel ' . $channel_id
            ];
        } else {
            $settings['private_channels'][$channel_id]['forward_header'] = $enabled;
        }
    } else {
        if (!isset($settings['public_channels'][$channel_id])) {
            $settings['public_channels'][$channel_id] = [
                'forward_header' => $enabled,
                'name' => 'Channel ' . $channel_id
            ];
        } else {
            $settings['public_channels'][$channel_id]['forward_header'] = $enabled;
        }
    }
    
    $settings['last_updated'] = date('Y-m-d H:i:s');
    file_put_contents(FORWARD_SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT));
    return true;
}

function toggle_forward_header($chat_id, $channel_id, $channel_type = 'private') {
    $current = get_forward_header_setting($channel_id);
    $new_status = !$current;
    set_forward_header_setting($channel_id, $new_status, $channel_type);
    
    $status_text = $new_status ? "✅ ENABLED" : "❌ DISABLED";
    $channel_name = ($channel_type == 'private') ? "Private Channel" : "Public Channel";
    
    sendMessage($chat_id, "🔄 Forward header for $channel_name has been $status_text\n\nChannel ID: <code>$channel_id</code>", null, 'HTML');
    return $new_status;
}
?>