<?php
// ==============================
// MAIN UPDATE PROCESSING (WEBHOOK HANDLER)
// ==============================

function process_webhook_update($update) {
    global $MAINTENANCE_MODE, $MAINTENANCE_MESSAGE, $movie_messages, $admin_menu_message_id;
    
    if ($MAINTENANCE_MODE && isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        sendMessage($chat_id, $MAINTENANCE_MESSAGE, null, 'HTML');
        bot_log("Maintenance mode active - message blocked from $chat_id");
        return;
    }

    get_cached_movies();

    // ==============================
    // CHANNEL POST HANDLING
    // ==============================
    if (isset($update['channel_post'])) {
        $message = $update['channel_post'];
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];

        $channel_type = 'other';
        if ($chat_id == MAIN_CHANNEL_ID) {
            $channel_type = 'main';
        } elseif ($chat_id == THEATER_CHANNEL_ID) {
            $channel_type = 'theater';
        } elseif ($chat_id == SERIAL_CHANNEL_ID) {
            $channel_type = 'serial';
        } elseif ($chat_id == BACKUP_CHANNEL_ID) {
            $channel_type = 'backup';
        } elseif ($chat_id == PRIVATE_CHANNEL_1_ID) {
            $channel_type = 'private';
        } elseif ($chat_id == PRIVATE_CHANNEL_2_ID) {
            $channel_type = 'private2';
        } else {
            return;
        }

        $text = '';
        $quality = 'Unknown';
        $size = 'Unknown';
        $language = 'Hindi';

        if (isset($message['caption'])) {
            $text = $message['caption'];
            if (stripos($text, '1080') !== false) $quality = '1080p';
            elseif (stripos($text, '720') !== false) $quality = '720p';
            elseif (stripos($text, '480') !== false) $quality = '480p';
            
            if (stripos($text, 'english') !== false) $language = 'English';
            if (stripos($text, 'hindi') !== false) $language = 'Hindi';
        }
        elseif (isset($message['text'])) {
            $text = $message['text'];
        }
        elseif (isset($message['document'])) {
            $text = $message['document']['file_name'];
            $size = round($message['document']['file_size'] / (1024 * 1024), 2) . ' MB';
        }
        else {
            $text = 'Uploaded Media - ' . date('d-m-Y H:i');
        }

        if (!empty(trim($text))) {
            append_movie($text, $message_id, date('d-m-Y'), $chat_id, $quality, $size, $language);
        }
    }

    // ==============================
    // MESSAGE HANDLING
    // ==============================
    if (isset($update['message'])) {
        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $message['from']['id'];
        $text = isset($message['text']) ? $message['text'] : '';
        $chat_type = $message['chat']['type'] ?? 'private';

        $user_info = [
            'first_name' => $message['from']['first_name'] ?? '',
            'last_name' => $message['from']['last_name'] ?? '',
            'username' => $message['from']['username'] ?? ''
        ];
        update_user_data($user_id, $user_info);

        if ($chat_type !== 'private') {
            if (strpos($text, '/') === 0) {
            } else {
                if (!is_valid_movie_query($text)) {
                    bot_log("Invalid group message blocked from $chat_id: $text");
                    return;
                }
            }
        }

        if (strpos($text, '/') === 0) {
            $parts = explode(' ', $text);
            $command = strtolower($parts[0]);
            $params = array_slice($parts, 1);
            
            handle_command($chat_id, $user_id, $command, $params);
        } else if (!empty(trim($text))) {
            sendTypingIndicator($chat_id, 3);
            $lang = detect_language($text);
            send_multilingual_response($chat_id, 'searching', $lang);
            advanced_search($chat_id, $text, $user_id);
        }
    }

    // ==============================
    // CALLBACK QUERY HANDLING
    // ==============================
    if (isset($update['callback_query'])) {
        $query = $update['callback_query'];
        $message = $query['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $query['from']['id'];
        $data = $query['data'];

        // Admin panel callbacks
        if (strpos($data, 'admin_') === 0 || 
            strpos($data, 'approve_req_') === 0 || 
            strpos($data, 'reject_req_') === 0 ||
            strpos($data, 'delete_movie_') === 0 ||
            strpos($data, 'view_user_') === 0 ||
            strpos($data, 'ban_user_') === 0 ||
            strpos($data, 'toggle_forward_') === 0 ||
            $data == 'bulk_approve_all' ||
            $data == 'bulk_reject_all' ||
            $data == 'bulk_approve_10' ||
            $data == 'bulk_approve_25' ||
            $data == 'bulk_approve_50' ||
            $data == 'manual_backup' ||
            $data == 'quick_backup' ||
            $data == 'toggle_maintenance' ||
            $data == 'clear_cache' ||
            $data == 'refresh_panel') {
            
            handle_admin_callback($chat_id, $user_id, $data, $query['id']);
        }
        // Search pagination callbacks
        elseif (strpos($data, 'search_next_') === 0) {
            $parts = explode('_', $data);
            $query_param = urldecode($parts[2]);
            $current_page = intval($parts[3]);
            $next_page = $current_page + 1;
            
            $found = smart_search(strtolower(trim($query_param)));
            $total_pages = ceil(count($found) / 5);
            
            if ($next_page <= $total_pages) {
                send_search_results_page($chat_id, $query_param, $found, $next_page);
                answerCallbackQuery($query['id'], "Page $next_page");
            } else {
                answerCallbackQuery($query['id'], "Last page", true);
            }
        }
        elseif (strpos($data, 'search_prev_') === 0) {
            $parts = explode('_', $data);
            $query_param = urldecode($parts[2]);
            $current_page = intval($parts[3]);
            $prev_page = max(1, $current_page - 1);
            
            $found = smart_search(strtolower(trim($query_param)));
            
            if ($prev_page >= 1) {
                send_search_results_page($chat_id, $query_param, $found, $prev_page);
                answerCallbackQuery($query['id'], "Page $prev_page");
            } else {
                answerCallbackQuery($query['id'], "First page", true);
            }
        }
        // Command buttons
        elseif ($data == 'cmd_browse') {
            show_category_menu($chat_id);
            answerCallbackQuery($query['id'], "Opening browse menu");
        }
        elseif ($data == 'cmd_request') {
            sendMessage($chat_id, "📝 To request a movie, use:\n<code>/request movie_name</code>\n\nExample: <code>/request Squid Game</code>", null, 'HTML');
            answerCallbackQuery($query['id'], "Request instruction sent");
        }
        elseif ($data == 'cmd_myrequests') {
            show_user_requests($chat_id, $user_id);
            answerCallbackQuery($query['id'], "Showing your requests");
        }
        elseif ($data == 'cmd_channels') {
            show_channel_info($chat_id);
            answerCallbackQuery($query['id'], "Showing all channels");
        }
        elseif ($data == 'cmd_info') {
            show_bot_info($chat_id);
            answerCallbackQuery($query['id'], "Bot information");
        }
        elseif ($data == 'cmd_report') {
            sendMessage($chat_id, "🐛 To report a bug, use:\n<code>/report your_bug_description</code>\n\nExample: <code>/report search not working</code>", null, 'HTML');
            answerCallbackQuery($query['id'], "Report instruction sent");
        }
        elseif ($data == 'cmd_feedback') {
            sendMessage($chat_id, "💡 To give feedback, use:\n<code>/feedback your_feedback</code>\n\nExample: <code>/feedback bot is great</code>", null, 'HTML');
            answerCallbackQuery($query['id'], "Feedback instruction sent");
        }
        // Admin command buttons
        elseif ($data == 'admin_pending') {
            if ($user_id == ADMIN_ID) {
                get_pending_requests($chat_id);
                answerCallbackQuery($query['id'], "Fetching pending requests");
            } else {
                answerCallbackQuery($query['id'], "Admin only!", true);
            }
        }
        elseif ($data == 'admin_bulk_approve') {
            if ($user_id == ADMIN_ID) {
                sendMessage($chat_id, "✅ <b>Bulk Approve</b>\n\nHow many requests to approve?\n\nUse: <code>/approve 10</code>\n\nYou can also use:\n• /approve 25\n• /approve 50\n• /approve all", null, 'HTML');
                answerCallbackQuery($query['id'], "Bulk approve instruction");
            } else {
                answerCallbackQuery($query['id'], "Admin only!", true);
            }
        }
        elseif ($data == 'admin_bulk_reject') {
            if ($user_id == ADMIN_ID) {
                sendMessage($chat_id, "❌ <b>Bulk Reject</b>\n\nHow many requests to reject?\n\nUse: <code>/reject 10</code>\n\nYou can also use:\n• /reject 25\n• /reject 50\n• /reject all", null, 'HTML');
                answerCallbackQuery($query['id'], "Bulk reject instruction");
            } else {
                answerCallbackQuery($query['id'], "Admin only!", true);
            }
        }
        elseif ($data == 'admin_forward') {
            if ($user_id == ADMIN_ID) {
                show_forward_settings_menu($chat_id, $query['id']);
                answerCallbackQuery($query['id'], "Forward header settings");
            } else {
                answerCallbackQuery($query['id'], "Admin only!", true);
            }
        }
        elseif ($data == 'admin_broadcast') {
            if ($user_id == ADMIN_ID) {
                sendMessage($chat_id, "📢 <b>Broadcast Message</b>\n\nSend message to all users:\n\n<code>/broadcast Your message here</code>\n\nExample:\n<code>/broadcast New movie added!</code>", null, 'HTML');
                answerCallbackQuery($query['id'], "Broadcast instruction");
            } else {
                answerCallbackQuery($query['id'], "Admin only!", true);
            }
        }
        // Category browse callbacks
        elseif (strpos($data, 'cat_') === 0) {
            $parts = explode('_', $data);
            $category = $parts[1];
            $page = isset($parts[2]) ? intval($parts[2]) : 1;
            show_category_movies($chat_id, $category, $page);
            answerCallbackQuery($query['id'], "Loading " . $category . " movies");
        }
        elseif ($data == 'back_to_categories') {
            show_category_menu($chat_id);
            answerCallbackQuery($query['id'], "Back to categories");
        }
        elseif (strpos($data, 'send_category_') === 0) {
            $parts = explode('_', $data);
            $category = $parts[2];
            $page = isset($parts[3]) ? intval($parts[3]) : 1;
            send_category_all_movies($chat_id, $category, $page);
            answerCallbackQuery($query['id'], "Sending movies...");
        }
        // Movie selection callbacks
        else {
            $movie_lower = strtolower($data);
            if (isset($movie_messages[$movie_lower])) {
                sendTypingIndicator($chat_id, 2);
                $entries = $movie_messages[$movie_lower];
                $cnt = 0;
                
                foreach ($entries as $entry) {
                    deliver_item_to_chat($chat_id, $entry);
                    usleep(200000);
                    $cnt++;
                }
                
                sendMessage($chat_id, "✅ '$data' ke $cnt items ka info mil gaya!\n\n📢 Join our channels:\n🍿 @EntertainmentTadka786\n🎭 @threater_print_movies\n📺 @Entertainment_Tadka_Serial_786");
                answerCallbackQuery($query['id'], "🎬 $cnt items ka info sent!");
            }
            elseif ($data === 'request_movie') {
                sendMessage($chat_id, "📝 To request a movie, use:\n<code>/request movie_name</code>\n\nExample: <code>/request Squid Game</code>", null, 'HTML');
                answerCallbackQuery($query['id'], "Request instructions sent");
            }
            elseif ($data === 'help_command') {
                handle_command($chat_id, $user_id, '/help', []);
                answerCallbackQuery($query['id'], "Help menu");
            }
            elseif (strpos($data, 'auto_request_') === 0) {
                $movie_name = base64_decode(str_replace('auto_request_', '', $data));
                $lang = detect_language($movie_name);
                
                if (add_movie_request($user_id, $movie_name, $lang)) {
                    send_multilingual_response($chat_id, 'request_success', $lang);
                    answerCallbackQuery($query['id'], "Request sent successfully!");
                } else {
                    send_multilingual_response($chat_id, 'request_limit', $lang);
                    answerCallbackQuery($query['id'], "Daily limit reached!", true);
                }
            }
            else {
                sendMessage($chat_id, "❌ Movie not found: " . $data . "\n\nTry searching with exact name!");
                answerCallbackQuery($query['id'], "❌ Movie not available");
            }
        }
    }

    // ==============================
    // AUTO-BACKUP TRIGGER
    // ==============================
    $current_hour = date('H');
    $current_minute = date('i');

    if ($current_hour == AUTO_BACKUP_HOUR && $current_minute == '00') {
        auto_backup();
        bot_log("Daily auto-backup completed");
    }

    // ==============================
    // CACHE CLEANUP
    // ==============================
    if ($current_minute == '30') {
        global $movie_cache;
        $movie_cache = [];
        bot_log("Hourly cache cleanup");
    }
}

// ==============================
// STATUS PAGE
// ==============================
function show_status_page() {
    $stats = get_stats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $requests_data = json_decode(file_get_contents(REQUEST_FILE), true);
    $forward_settings = json_decode(file_get_contents(FORWARD_SETTINGS_FILE), true);
    
    echo "<h1>🎬 Entertainment Tadka Bot v3.0</h1>";
    echo "<p><strong>Status:</strong> ✅ Running</p>";
    echo "<p><strong>Total Movies:</strong> " . ($stats['total_movies'] ?? 0) . "</p>";
    echo "<p><strong>Total Users:</strong> " . count($users_data['users'] ?? []) . "</p>";
    echo "<p><strong>Total Searches:</strong> " . ($stats['total_searches'] ?? 0) . "</p>";
    echo "<p><strong>Pending Requests:</strong> " . count($requests_data['requests'] ?? []) . "</p>";
    
    echo "<h3>🚀 Quick Setup</h3>";
    echo "<p><a href='?setwebhook=1'>Set Webhook Now</a></p>";
    echo "<p><a href='?test_save=1'>Test Movie Save</a></p>";
    echo "<p><a href='?check_csv=1'>Check CSV Data</a></p>";
    
    echo "<h3>📋 Commands</h3>";
    echo "<ul>";
    echo "<li><code>/start</code> - Welcome message</li>";
    echo "<li><code>/help</code> - Help menu</li>";
    echo "<li><code>/search movie</code> - Search movies</li>";
    echo "<li><code>/browse</code> - Browse by category</li>";
    echo "<li><code>/request movie</code> - Request movie</li>";
    echo "<li><code>/myrequests</code> - Check request status</li>";
    echo "<li><code>/channel</code> - All channels</li>";
    echo "<li><code>/report</code> - Report bug</li>";
    echo "<li><code>/feedback</code> - Give feedback</li>";
    echo "<li><code>/info</code> - Bot info</li>";
    echo "<li><code>/ping</code> - Health check</li>";
    echo "</ul>";
    
    echo "<h3>👑 Admin Commands</h3>";
    echo "<ul>";
    echo "<li><code>/admin</code> - Open admin panel</li>";
    echo "<li><code>/pending</code> - View pending requests</li>";
    echo "<li><code>/approve [count]</code> - Bulk approve</li>";
    echo "<li><code>/reject [count]</code> - Bulk reject</li>";
    echo "<li><code>/forward</code> - Forward header settings</li>";
    echo "<li><code>/broadcast</code> - Send to all users</li>";
    echo "<li><code>/stats</code> - Bot statistics</li>";
    echo "<li><code>/maintenance on/off</code> - Maintenance mode</li>";
    echo "<li><code>/cleanup</code> - Cleanup</li>";
    echo "</ul>";
    
    echo "<h3>🔥 Channels:</h3>";
    echo "<ul>";
    echo "<li>🍿 Main: @EntertainmentTadka786</li>";
    echo "<li>📥 Request: @EntertainmentTadka7860</li>";
    echo "<li>🎭 Theater: @threater_print_movies</li>";
    echo "<li>📂 Backup: @ETBackup</li>";
    echo "<li>📺 Serial: @Entertainment_Tadka_Serial_786</li>";
    echo "</ul>";
    
    echo "<h3>🔐 Forward Header Settings:</h3>";
    echo "<ul>";
    echo "<li>Public Channels: " . (($forward_settings['public_channels'][MAIN_CHANNEL_ID]['forward_header'] ?? true) ? "✅ ON" : "❌ OFF") . "</li>";
    echo "<li>Private Channels: " . (($forward_settings['private_channels'][PRIVATE_CHANNEL_1_ID]['forward_header'] ?? false) ? "✅ ON" : "❌ OFF") . "</li>";
    echo "</ul>";
}

// ==============================
// MANUAL TESTING FUNCTIONS
// ==============================
if (isset($_GET['test_save'])) {
    function manual_save_to_csv($movie_name, $message_id, $channel_id) {
        $entry = [$movie_name, $message_id, $channel_id];
        $handle = fopen(CSV_FILE, "a");
        if ($handle !== FALSE) {
            fputcsv($handle, $entry);
            fclose($handle);
            @chmod(CSV_FILE, 0666);
            return true;
        }
        return false;
    }
    
    manual_save_to_csv("Test Movie 1", 1001, MAIN_CHANNEL_ID);
    manual_save_to_csv("Test Movie 2", 1002, MAIN_CHANNEL_ID);
    
    echo "✅ Test movies saved!<br>";
    exit;
}

if (isset($_GET['check_csv'])) {
    echo "<h3>CSV Content:</h3>";
    if (file_exists(CSV_FILE)) {
        $lines = file(CSV_FILE);
        foreach ($lines as $line) {
            echo htmlspecialchars($line) . "<br>";
        }
    } else {
        echo "❌ CSV file not found!";
    }
    exit;
}

if (php_sapi_name() === 'cli' || isset($_GET['setwebhook'])) {
    $webhook_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $result = apiRequest('setWebhook', ['url' => $webhook_url]);
    
    echo "<h1>Webhook Setup</h1>";
    echo "<p>Result: " . htmlspecialchars($result) . "</p>";
    echo "<p>Webhook URL: " . htmlspecialchars($webhook_url) . "</p>";
    
    $bot_info = json_decode(apiRequest('getMe'), true);
    if ($bot_info && isset($bot_info['ok']) && $bot_info['ok']) {
        echo "<h2>Bot Info</h2>";
        echo "<p>Name: " . htmlspecialchars($bot_info['result']['first_name']) . "</p>";
        echo "<p>Username: @" . htmlspecialchars($bot_info['result']['username']) . "</p>";
    }
    
    exit;
}
?>
