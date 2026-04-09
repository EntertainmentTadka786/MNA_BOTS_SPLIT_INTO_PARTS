<?php
// ==============================
// BILKUL SIMPLE WEBHOOK HANDLER
// ==============================

function process_webhook_update($update) {
    global $MAINTENANCE_MODE, $MAINTENANCE_MESSAGE, $movie_messages;
    
    if ($MAINTENANCE_MODE && isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        sendMessage($chat_id, $MAINTENANCE_MESSAGE, null, 'HTML');
        return;
    }

    get_cached_movies();

    // ==============================
    // CHANNEL POST - MOVIES ADD HOGI
    // ==============================
    if (isset($update['channel_post'])) {
        $message = $update['channel_post'];
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];

        $text = '';
        $quality = 'Unknown';
        $size = 'Unknown';
        $language = 'Hindi';

        if (isset($message['caption'])) {
            $text = $message['caption'];
            if (stripos($text, '1080') !== false) $quality = '1080p';
            elseif (stripos($text, '720') !== false) $quality = '720p';
            if (stripos($text, 'hindi') !== false) $language = 'Hindi';
            if (stripos($text, 'english') !== false) $language = 'English';
        }
        elseif (isset($message['text'])) {
            $text = $message['text'];
        }
        elseif (isset($message['document'])) {
            $text = $message['document']['file_name'];
            $size = round($message['document']['file_size'] / (1024 * 1024), 2) . ' MB';
        }

        if (!empty(trim($text))) {
            append_movie($text, $message_id, date('d-m-Y'), $chat_id, $quality, $size, $language);
        }
    }

    // ==============================
    // MESSAGE - USER NE KUCH BHEJA
    // ==============================
    if (isset($update['message'])) {
        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $message['from']['id'];
        $text = isset($message['text']) ? $message['text'] : '';

        $user_info = [
            'first_name' => $message['from']['first_name'] ?? '',
            'last_name' => $message['from']['last_name'] ?? '',
            'username' => $message['from']['username'] ?? ''
        ];
        update_user_data($user_id, $user_info);

        // Command hai toh handle karo
        if (strpos($text, '/') === 0) {
            $parts = explode(' ', $text);
            $command = strtolower($parts[0]);
            $params = array_slice($parts, 1);
            handle_command($chat_id, $user_id, $command, $params);
        }
        // Movie name hai toh search karo
        else if (!empty(trim($text))) {
            sendTypingIndicator($chat_id, 2);
            advanced_search($chat_id, $text, $user_id);
        }
    }

    // ==============================
    // CALLBACK - BUTTON CLICK
    // ==============================
    if (isset($update['callback_query'])) {
        $query = $update['callback_query'];
        $message = $query['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $query['from']['id'];
        $data = $query['data'];

        // Admin callbacks
        if (strpos($data, 'admin_') === 0 || 
            strpos($data, 'approve_req_') === 0 || 
            strpos($data, 'reject_req_') === 0 ||
            $data == 'bulk_approve_all' ||
            $data == 'bulk_reject_all' ||
            $data == 'refresh_panel') {
            
            handle_admin_callback($chat_id, $user_id, $data, $query['id']);
        }
        // Movie selection
        else {
            $movie_lower = strtolower($data);
            if (isset($movie_messages[$movie_lower])) {
                $entries = $movie_messages[$movie_lower];
                foreach ($entries as $entry) {
                    deliver_item_to_chat($chat_id, $entry);
                    usleep(200000);
                }
                sendMessage($chat_id, "✅ '$data' ka info mil gaya!");
                answerCallbackQuery($query['id'], "Movie sent");
            }
            elseif ($data == 'request_movie') {
                sendMessage($chat_id, "📝 Use: /request movie_name\nExample: /request Squid Game");
                answerCallbackQuery($query['id'], "Request instruction");
            }
            elseif (strpos($data, 'auto_request_') === 0) {
                $movie_name = base64_decode(str_replace('auto_request_', '', $data));
                if (add_movie_request($user_id, $movie_name, 'hindi')) {
                    sendMessage($chat_id, "✅ Request receive ho gayi! Hum jald hi add karenge.");
                    answerCallbackQuery($query['id'], "Request sent");
                } else {
                    sendMessage($chat_id, "❌ Aaj ki limit ho gayi (5 requests/day)");
                    answerCallbackQuery($query['id'], "Limit reached", true);
                }
            }
            else {
                answerCallbackQuery($query['id'], "Movie not found");
            }
        }
    }

    // ==============================
    // AUTO-BACKUP (Daily at 3 AM)
    // ==============================
    $current_hour = date('H');
    $current_minute = date('i');
    if ($current_hour == AUTO_BACKUP_HOUR && $current_minute == '00') {
        auto_backup();
    }

    // ==============================
    // CACHE CLEANUP (Hourly)
    // ==============================
    if ($current_minute == '30') {
        global $movie_cache;
        $movie_cache = [];
    }
}

// ==============================
// STATUS PAGE
// ==============================
function show_status_page() {
    $stats = get_stats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    
    echo "<h1>🎬 Entertainment Tadka Bot</h1>";
    echo "<p>Status: ✅ Running</p>";
    echo "<p>Movies: " . ($stats['total_movies'] ?? 0) . "</p>";
    echo "<p>Users: " . count($users_data['users'] ?? []) . "</p>";
    echo "<p>Searches: " . ($stats['total_searches'] ?? 0) . "</p>";
    
    echo "<h3>Quick Setup</h3>";
    echo "<p><a href='?setwebhook=1'>Set Webhook</a></p>";
    echo "<p><a href='?test_save=1'>Test Save</a></p>";
    echo "<p><a href='?check_csv=1'>Check CSV</a></p>";
}

// ==============================
// TEST FUNCTIONS
// ==============================
if (isset($_GET['test_save'])) {
    function manual_save_to_csv($movie_name, $message_id, $channel_id) {
        $entry = [$movie_name, $message_id, $channel_id];
        $handle = fopen(CSV_FILE, "a");
        if ($handle !== FALSE) {
            fputcsv($handle, $entry);
            fclose($handle);
            return true;
        }
        return false;
    }
    manual_save_to_csv("Test Movie 1", 1001, MAIN_CHANNEL_ID);
    manual_save_to_csv("Test Movie 2", 1002, MAIN_CHANNEL_ID);
    echo "✅ Test movies saved!";
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
        echo "CSV not found!";
    }
    exit;
}

if (php_sapi_name() === 'cli' || isset($_GET['setwebhook'])) {
    $webhook_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $result = apiRequest('setWebhook', ['url' => $webhook_url]);
    echo "<h1>Webhook Setup</h1>";
    echo "<p>Result: " . htmlspecialchars($result) . "</p>";
    echo "<p>URL: " . htmlspecialchars($webhook_url) . "</p>";
    exit;
}
?>
