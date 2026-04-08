<?php
// ==============================
// FILE INITIALIZATION FUNCTION
// ==============================

function initialize_files() {
    $files = [
        CSV_FILE => "movie_name,message_id,channel_id\n",
        USERS_FILE => json_encode([
            'users' => [],
            'total_requests' => 0,
            'message_logs' => [],
            'daily_stats' => []
        ], JSON_PRETTY_PRINT),
        STATS_FILE => json_encode([
            'total_movies' => 0,
            'total_users' => 0,
            'total_searches' => 0,
            'total_downloads' => 0,
            'successful_searches' => 0,
            'failed_searches' => 0,
            'daily_activity' => [],
            'last_updated' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT),
        REQUEST_FILE => json_encode([
            'requests' => [],
            'pending_approval' => [],
            'completed_requests' => [],
            'user_request_count' => []
        ], JSON_PRETTY_PRINT),
        FORWARD_SETTINGS_FILE => json_encode([
            'public_channels' => [],
            'private_channels' => [],
            'last_updated' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT)
    ];
    
    foreach ($files as $file => $content) {
        if (!file_exists($file)) {
            file_put_contents($file, $content);
            @chmod($file, 0666);
        }
    }
    
    if (!file_exists(BACKUP_DIR)) {
        @mkdir(BACKUP_DIR, 0777, true);
    }
    
    if (!file_exists(LOG_FILE)) {
        file_put_contents(LOG_FILE, "[" . date('Y-m-d H:i:s') . "] SYSTEM: Files initialized\n");
    }
    
    initialize_forward_settings();
}

// ==============================
// CACHING SYSTEM
// ==============================
function get_cached_movies() {
    global $movie_cache;
    
    if (!empty($movie_cache) && (time() - $movie_cache['timestamp']) < CACHE_EXPIRY) {
        return $movie_cache['data'];
    }
    
    $movie_cache = [
        'data' => load_and_clean_csv(),
        'timestamp' => time()
    ];
    
    bot_log("Movie cache refreshed - " . count($movie_cache['data']) . " movies");
    return $movie_cache['data'];
}

// ==============================
// CSV MANAGEMENT FUNCTIONS
// ==============================
function load_and_clean_csv($filename = CSV_FILE) {
    global $movie_messages;
    
    if (!file_exists($filename)) {
        file_put_contents($filename, "movie_name,message_id,channel_id\n");
        return [];
    }

    $data = [];
    $handle = fopen($filename, "r");
    if ($handle !== FALSE) {
        $header = fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== FALSE) {
            if (count($row) >= 3 && (!empty(trim($row[0])))) {
                $movie_name = trim($row[0]);
                $message_id_raw = isset($row[1]) ? trim($row[1]) : '';
                $channel_id = isset($row[2]) ? trim($row[2]) : '';

                $channel_type = get_channel_type_by_id($channel_id);
                
                $channel_username = '';
                switch ($channel_type) {
                    case 'main':
                        $channel_username = MAIN_CHANNEL;
                        break;
                    case 'theater':
                        $channel_username = THEATER_CHANNEL;
                        break;
                    case 'serial':
                        $channel_username = SERIAL_CHANNEL;
                        break;
                    case 'backup':
                        $channel_username = BACKUP_CHANNEL_USERNAME;
                        break;
                }

                $entry = [
                    'movie_name' => $movie_name,
                    'message_id_raw' => $message_id_raw,
                    'channel_id' => $channel_id,
                    'channel_type' => $channel_type,
                    'channel_username' => $channel_username,
                    'source_channel' => $channel_id
                ];
                
                if (is_numeric($message_id_raw)) {
                    $entry['message_id'] = intval($message_id_raw);
                } else {
                    $entry['message_id'] = null;
                }

                $data[] = $entry;

                $movie = strtolower($movie_name);
                if (!isset($movie_messages[$movie])) $movie_messages[$movie] = [];
                $movie_messages[$movie][] = $entry;
            }
        }
        fclose($handle);
    }

    $stats = json_decode(file_get_contents(STATS_FILE), true);
    $stats['total_movies'] = count($data);
    $stats['last_updated'] = date('Y-m-d H:i:s');
    file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));

    $handle = fopen($filename, "w");
    fputcsv($handle, array('movie_name', 'message_id', 'channel_id'));
    foreach ($data as $row) {
        fputcsv($handle, [
            $row['movie_name'], 
            $row['message_id_raw'], 
            $row['channel_id']
        ]);
    }
    fclose($handle);

    bot_log("CSV cleaned and reloaded - " . count($data) . " entries");
    return $data;
}

// ==============================
// MOVIE APPEND FUNCTION
// ==============================
function append_movie($movie_name, $message_id_raw, $date = null, $channel_id = '', $quality = 'Unknown', $size = 'Unknown', $language = 'Hindi') {
    global $movie_messages, $movie_cache;
    
    if (empty(trim($movie_name))) return;
    
    if ($date === null) $date = date('d-m-Y');
    
    if (empty($channel_id)) {
        $channel_id = MAIN_CHANNEL_ID;
    }
    
    $channel_type = get_channel_type_by_id($channel_id);
    
    $channel_username = '';
    switch ($channel_type) {
        case 'main':
            $channel_username = MAIN_CHANNEL;
            break;
        case 'theater':
            $channel_username = THEATER_CHANNEL;
            break;
        case 'serial':
            $channel_username = SERIAL_CHANNEL;
            break;
        case 'backup':
            $channel_username = BACKUP_CHANNEL_USERNAME;
            break;
    }
    
    $entry = [$movie_name, $message_id_raw, $channel_id];
    
    $handle = fopen(CSV_FILE, "a");
    fputcsv($handle, $entry);
    fclose($handle);

    $movie = strtolower(trim($movie_name));
    $item = [
        'movie_name' => $movie_name,
        'message_id_raw' => $message_id_raw,
        'channel_id' => $channel_id,
        'channel_type' => $channel_type,
        'channel_username' => $channel_username,
        'message_id' => is_numeric($message_id_raw) ? intval($message_id_raw) : null,
        'source_channel' => $channel_id
    ];
    
    if (!isset($movie_messages[$movie])) $movie_messages[$movie] = [];
    $movie_messages[$movie][] = $item;
    $movie_cache = [];

    update_stats('total_movies', 1);
    bot_log("Movie appended: $movie_name with ID $message_id_raw from channel $channel_type ($channel_id)");
}

function get_all_movies_list() {
    return get_cached_movies();
}
?>