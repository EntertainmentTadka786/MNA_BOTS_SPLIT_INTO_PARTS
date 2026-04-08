<?php
// ==============================
// ENHANCED MOVIE DELIVERY SYSTEM WITH FORWARD HEADER CONTROL
// ==============================

function deliver_item_to_chat($chat_id, $item) {
    if (!isset($item['channel_id']) || empty($item['channel_id'])) {
        $source_channel = MAIN_CHANNEL_ID;
        bot_log("Channel ID not found for movie: {$item['movie_name']}, using default", 'WARNING');
    } else {
        $source_channel = $item['channel_id'];
    }
    
    $channel_type = isset($item['channel_type']) ? $item['channel_type'] : 'main';
    $forward_header_enabled = get_forward_header_setting($source_channel);
    
    if (!empty($item['message_id']) && is_numeric($item['message_id'])) {
        if ($forward_header_enabled) {
            $result = json_decode(forwardMessage($chat_id, $source_channel, $item['message_id']), true);
            
            if ($result && $result['ok']) {
                update_stats('total_downloads', 1);
                bot_log("Movie FORWARDED (with header) from $channel_type: {$item['movie_name']} to $chat_id");
                return true;
            }
        } else {
            $result = json_decode(copyMessage($chat_id, $source_channel, $item['message_id']), true);
            
            if ($result && $result['ok']) {
                update_stats('total_downloads', 1);
                bot_log("Movie COPIED (no header) from $channel_type: {$item['movie_name']} to $chat_id");
                return true;
            }
        }
        
        if ($forward_header_enabled) {
            $fallback_result = json_decode(copyMessage($chat_id, $source_channel, $item['message_id']), true);
            if ($fallback_result && $fallback_result['ok']) {
                update_stats('total_downloads', 1);
                bot_log("Movie COPIED (fallback) from $channel_type: {$item['movie_name']} to $chat_id");
                return true;
            }
        } else {
            $fallback_result = json_decode(forwardMessage($chat_id, $source_channel, $item['message_id']), true);
            if ($fallback_result && $fallback_result['ok']) {
                update_stats('total_downloads', 1);
                bot_log("Movie FORWARDED (fallback) from $channel_type: {$item['movie_name']} to $chat_id");
                return true;
            }
        }
    }
    
    if (!empty($item['message_id_raw'])) {
        $message_id_clean = preg_replace('/[^0-9]/', '', $item['message_id_raw']);
        if (is_numeric($message_id_clean) && $message_id_clean > 0) {
            if ($forward_header_enabled) {
                $result = json_decode(forwardMessage($chat_id, $source_channel, $message_id_clean), true);
                if ($result && $result['ok']) {
                    update_stats('total_downloads', 1);
                    bot_log("Movie FORWARDED (raw ID) from $channel_type: {$item['movie_name']} to $chat_id");
                    return true;
                }
            } else {
                $result = json_decode(copyMessage($chat_id, $source_channel, $message_id_clean), true);
                if ($result && $result['ok']) {
                    update_stats('total_downloads', 1);
                    bot_log("Movie COPIED (raw ID) from $channel_type: {$item['movie_name']} to $chat_id");
                    return true;
                }
            }
        }
    }

    $text = "🎬 <b>" . htmlspecialchars($item['movie_name'] ?? 'Unknown') . "</b>\n";
    $text .= "🎭 Channel: " . get_channel_display_name($channel_type) . "\n\n";
    
    if (!empty($item['message_id']) && is_numeric($item['message_id']) && !empty($source_channel)) {
        $text .= "🔗 Direct Link: " . get_direct_channel_link($item['message_id'], $source_channel) . "\n\n";
    }
    
    $text .= "⚠️ Join channel to access content: " . get_channel_username_link($channel_type);
    
    sendMessage($chat_id, $text, null, 'HTML');
    update_stats('total_downloads', 1);
    return false;
}

function batch_download_with_progress($chat_id, $movies, $page_num) {
    $total = count($movies);
    if ($total === 0) return;
    
    $progress_msg = sendMessage($chat_id, "📦 <b>Batch Info Started</b>\n\nPage: {$page_num}\nTotal: {$total} movies\n\n⏳ Initializing...");
    $progress_id = $progress_msg['result']['message_id'];
    
    $success = 0;
    $failed = 0;
    
    for ($i = 0; $i < $total; $i++) {
        $movie = $movies[$i];
        
        if ($i % 2 == 0) {
            $progress = round(($i / $total) * 100);
            editMessage($chat_id, $progress_id, 
                "📦 <b>Sending Page {$page_num} Info</b>\n\n" .
                "Progress: {$progress}%\n" .
                "Processed: {$i}/{$total}\n" .
                "✅ Success: {$success}\n" .
                "❌ Failed: {$failed}\n\n" .
                "⏳ Please wait..."
            );
        }
        
        try {
            $result = deliver_item_to_chat($chat_id, $movie);
            if ($result) {
                $success++;
            } else {
                $failed++;
            }
        } catch (Exception $e) {
            $failed++;
        }
        
        usleep(500000);
    }
    
    editMessage($chat_id, $progress_id,
        "✅ <b>Batch Info Complete</b>\n\n" .
        "📄 Page: {$page_num}\n" .
        "🎬 Total: {$total} movies\n" .
        "✅ Successfully sent: {$success}\n" .
        "❌ Failed: {$failed}\n\n" .
        "📊 Success rate: " . round(($success / $total) * 100, 2) . "%\n" .
        "⏱️ Time: " . date('H:i:s') . "\n\n" .
        "🔗 Join channels to download:\n" .
        "🍿 Main: @EntertainmentTadka786\n" .
        "🎭 Theater: @threater_print_movies\n" .
        "📺 Serial: @Entertainment_Tadka_Serial_786"
    );
}
?>