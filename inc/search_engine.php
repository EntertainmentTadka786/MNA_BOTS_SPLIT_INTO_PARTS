<?php
// ==============================
// SEARCH SYSTEM
// ==============================

function smart_search($query) {
    global $movie_messages;
    $query_lower = strtolower(trim($query));
    $results = array();
    
    foreach ($movie_messages as $movie => $entries) {
        $score = 0;
        
        foreach ($entries as $entry) {
            $entry_channel_type = $entry['channel_type'] ?? 'main';
            
            if ($entry_channel_type == 'main') {
                $score += 10;
            }
            
            if (in_array($entry_channel_type, ['backup', 'private', 'private2', 'serial', 'theater'])) {
                $score += 5;
            }
        }
        
        if ($movie == $query_lower) {
            $score = 100;
        }
        elseif (strpos($movie, $query_lower) !== false) {
            $score = 80 - (strlen($movie) - strlen($query_lower));
        }
        else {
            similar_text($movie, $query_lower, $similarity);
            if ($similarity > 60) $score = $similarity;
        }
        
        if ($score > 0) {
            $channel_types = array_column($entries, 'channel_type');
            $results[$movie] = [
                'score' => $score,
                'count' => count($entries),
                'latest_entry' => end($entries),
                'qualities' => array_unique(array_column($entries, 'quality')),
                'has_theater' => in_array('theater', $channel_types),
                'has_main' => in_array('main', $channel_types),
                'has_serial' => in_array('serial', $channel_types),
                'has_backup' => in_array('backup', $channel_types),
                'has_private' => in_array('private', $channel_types) || in_array('private2', $channel_types),
                'all_channels' => array_unique($channel_types)
            ];
        }
    }
    
    uasort($results, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    return array_slice($results, 0, MAX_SEARCH_RESULTS);
}

function detect_language($text) {
    $hindi_keywords = ['फिल्म', 'मूवी', 'डाउनलोड', 'हिंदी', 'चाहिए', 'कहाँ', 'कैसे', 'खोज', 'तलाश'];
    $english_keywords = ['movie', 'download', 'watch', 'print', 'search', 'find', 'looking', 'want', 'need'];
    
    $hindi_score = 0;
    $english_score = 0;
    
    foreach ($hindi_keywords as $k) {
        if (strpos($text, $k) !== false) $hindi_score++;
    }
    
    foreach ($english_keywords as $k) {
        if (stripos($text, $k) !== false) $english_score++;
    }
    
    $hindi_chars = preg_match('/[\x{0900}-\x{097F}]/u', $text);
    if ($hindi_chars) $hindi_score += 3;
    
    return $hindi_score > $english_score ? 'hindi' : 'english';
}

function send_multilingual_response($chat_id, $message_type, $language) {
    $responses = [
        'hindi' => [
            'welcome' => "🎬 Boss, kis movie ki talash hai?",
            'found' => "✅ Mil gayi! Movie info bhej raha hoon...",
            'not_found' => "😔 Yeh movie abhi available nahi hai!\n\n📝 Aap ise request kar sakte hain: " . REQUEST_GROUP,
            'searching' => "🔍 Dhoondh raha hoon... Zara wait karo",
            'multiple_found' => "🎯 Kai versions mili hain! Aap konsi chahte hain?",
            'request_success' => "✅ Request receive ho gayi! Hum jald hi add karenge.",
            'request_limit' => "❌ Aaj ke liye aap maximum " . DAILY_REQUEST_LIMIT . " requests hi kar sakte hain."
        ],
        'english' => [
            'welcome' => "🎬 Boss, which movie are you looking for?",
            'found' => "✅ Found it! Sending movie info...",
            'not_found' => "😔 This movie isn't available yet!\n\n📝 You can request it here: " . REQUEST_GROUP,
            'searching' => "🔍 Searching... Please wait",
            'multiple_found' => "🎯 Multiple versions found! Which one do you want?",
            'request_success' => "✅ Request received! We'll add it soon.",
            'request_limit' => "❌ You've reached the daily limit of " . DAILY_REQUEST_LIMIT . " requests."
        ]
    ];
    
    sendMessage($chat_id, $responses[$language][$message_type]);
}

function clean_movie_name($text) {
    $text = preg_replace('/\s*[\(\[].*?[\)\]]\s*/', '', $text);
    $text = preg_replace('/\b(1080p|720p|480p|HD|FHD|4K|theater|print|camrip|hdcam|HQ|BluRay|WEB-DL|WEBRip)\b/i', '', $text);
    $text = preg_replace('/\b(Hindi|English|Tamil|Telugu|Malayalam|Kannada)\b/i', '', $text);
    $text = preg_replace('/\b\d+\s*(GB|MB)\b/i', '', $text);
    $text = preg_replace('/[^\w\s\-\.]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    
    if (empty($text) || strlen($text) < 3) {
        $text = 'Unknown Movie ' . date('Y-m-d');
    }
    
    return $text;
}

function send_search_results_page($chat_id, $query, $found, $page = 1) {
    $results_per_page = 5;
    $results_array = array_values($found);
    $total_results = count($results_array);
    $total_pages = ceil($total_results / $results_per_page);
    $start = ($page - 1) * $results_per_page;
    $page_results = array_slice($results_array, $start, $results_per_page);
    
    $msg = "🎬 <b>RESULTS FOR \"" . htmlspecialchars(strtoupper($query)) . "\"</b>\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    foreach ($page_results as $item) {
        $movie = $item['movie_name'] ?? key($item);
        if (is_array($item) && isset($item['movie_name'])) {
            $movie = $item['movie_name'];
            $data = $item;
        } else {
            $data = $found[$movie];
        }
        
        $quality = 'HD';
        if (!empty($data['qualities'])) {
            $qualities_array = array_values($data['qualities']);
            $quality = $qualities_array[0] ?? 'HD';
        }
        
        $channel_icon = '🍿';
        if ($data['has_serial']) $channel_icon = '📺';
        elseif ($data['has_backup']) $channel_icon = '🔒';
        elseif ($data['has_private']) $channel_icon = '🔐';
        elseif ($data['has_theater']) $channel_icon = '🎭';
        
        $channel_name = 'Main Channel';
        if ($data['has_serial']) $channel_name = 'Serial Channel';
        elseif ($data['has_backup']) $channel_name = 'Backup';
        elseif ($data['has_private']) $channel_name = 'Private';
        elseif ($data['has_theater']) $channel_name = 'Theater';
        
        preg_match('/\b(19|20)\d{2}\b/', $movie, $year_match);
        $year = $year_match[0] ?? 'N/A';
        
        $size = $data['latest_entry']['size'] ?? '—';
        if ($size == 'Unknown' || empty($size)) {
            $size = '—';
        }
        
        $language = $data['latest_entry']['language'] ?? 'Hindi';
        
        $msg .= "┌─────────────────────────────────────────────┐\n";
        $msg .= "│ $channel_icon <b>" . htmlspecialchars($movie) . "</b>\n";
        $msg .= "│ $channel_icon $channel_name  •  ⭐ $quality\n";
        $msg .= "│ 🗣️ $language  •  💾 $size  •  📅 $year\n";
        $msg .= "│ ┌─────────┐                                 \n";
        $msg .= "│ │ <b>📥 GET</b>  │                                 \n";
        $msg .= "│ └─────────┘                                 \n";
        $msg .= "└─────────────────────────────────────────────┘\n\n";
    }
    
    $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '◀️ PREV', 'callback_data' => 'search_prev_' . urlencode($query) . '_' . $page],
                ['text' => 'Page ' . $page . '/' . $total_pages, 'callback_data' => 'noop'],
                ['text' => 'NEXT ▶️', 'callback_data' => 'search_next_' . urlencode($query) . '_' . $page]
            ],
            [
                ['text' => '📝 REQUEST MOVIE', 'callback_data' => 'auto_request_' . base64_encode($query)]
            ]
        ]
    ];
    
    sendMessage($chat_id, $msg, $keyboard, 'HTML');
}

function advanced_search($chat_id, $query, $user_id = null) {
    global $movie_messages;
    $q = strtolower(trim($query));
    
    if (strlen($q) < 2) {
        sendMessage($chat_id, "❌ Please enter at least 2 characters for search");
        return;
    }
    
    $invalid_keywords = [
        'vlc', 'audio', 'track', 'change', 'open', 'kar', 'me', 'hai',
        'how', 'what', 'problem', 'issue', 'help', 'solution', 'fix',
        'error', 'not working', 'download', 'play', 'video', 'sound',
        'subtitle', 'quality', 'hd', 'full', 'part', 'scene',
        'hi', 'hello', 'hey', 'good', 'morning', 'night', 'bye',
        'thanks', 'thank', 'ok', 'okay', 'yes', 'no', 'maybe',
        'who', 'when', 'where', 'why', 'how', 'can', 'should',
        'kaise', 'kya', 'kahan', 'kab', 'kyun', 'kon', 'kisne',
        'hai', 'hain', 'ho', 'raha', 'raha', 'rah', 'tha', 'thi',
        'mere', 'apne', 'tumhare', 'hamare', 'sab', 'log', 'group'
    ];
    
    $query_words = explode(' ', $q);
    $total_words = count($query_words);
    
    $invalid_count = 0;
    foreach ($query_words as $word) {
        if (in_array($word, $invalid_keywords)) {
            $invalid_count++;
        }
    }
    
    if ($invalid_count > 0 && ($invalid_count / $total_words) > 0.5) {
        $help_msg = "🎬 Please enter a movie name!\n\n";
        $help_msg .= "🔍 Examples of valid movie names:\n";
        $help_msg .= "• Mandala Murders 2025\n• Zebra 2024\n• Now You See Me\n";
        $help_msg .= "• Squid Game\n• Show Time (2024)\n• Taskaree S01 (2025)\n\n";
        $help_msg .= "❌ Technical queries like 'vlc', 'audio track', etc. are not movie names.\n\n";
        $help_msg .= "📢 Join Our Channels:\n";
        $help_msg .= "🍿 Main: @EntertainmentTadka786\n";
        $help_msg .= "📥 Request: @EntertainmentTadka7860\n";
        $help_msg .= "🎭 Theater: @threater_print_movies\n";
        $help_msg .= "📂 Backup: @ETBackup\n";
        $help_msg .= "📺 Serial: @Entertainment_Tadka_Serial_786";
        sendMessage($chat_id, $help_msg, null, 'HTML');
        return;
    }
    
    $movie_pattern = '/^[a-zA-Z0-9\s\-\.\,\&\+\(\)\:\'\"]+$/';
    if (!preg_match($movie_pattern, $query)) {
        sendMessage($chat_id, "❌ Invalid movie name format. Only letters, numbers, and basic punctuation allowed.");
        return;
    }
    
    $found = smart_search($q);
    
    if (!empty($found)) {
        update_stats('successful_searches', 1);
        send_search_results_page($chat_id, $query, $found, 1);
    } else {
        update_stats('failed_searches', 1);
        $lang = detect_language($query);
        
        $not_found_msg = "😔 <b>NO RESULTS FOUND FOR \"" . htmlspecialchars(strtoupper($query)) . "\"</b>\n\n";
        $not_found_msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $not_found_msg .= "┌─────────────────────────────────────────────┐\n";
        $not_found_msg .= "│ ❌ This movie isn't available yet!           │\n";
        $not_found_msg .= "│                                             │\n";
        $not_found_msg .= "│ 💡 <b>What you can do:</b>                    │\n";
        $not_found_msg .= "│                                             │\n";
        $not_found_msg .= "│ 📝 Request the movie using button below     │\n";
        $not_found_msg .= "│ 🔍 Check spelling and try again             │\n";
        $not_found_msg .= "│ 📢 Join @EntertainmentTadka7860 for support │\n";
        $not_found_msg .= "└─────────────────────────────────────────────┘\n\n";
        $not_found_msg .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
        
        $request_keyboard = [
            'inline_keyboard' => [[
                ['text' => '📝 REQUEST THIS MOVIE', 'callback_data' => 'auto_request_' . base64_encode($query)]
            ]]
        ];
        
        sendMessage($chat_id, $not_found_msg, $request_keyboard, 'HTML');
    }
    
    update_stats('total_searches', 1);
}

// ==============================
// GROUP MESSAGE FILTER
// ==============================
function is_valid_movie_query($text) {
    $text = strtolower(trim($text));
    
    if (strpos($text, '/') === 0) {
        return true;
    }
    
    if (strlen($text) < 3) {
        return false;
    }
    
    $invalid_phrases = [
        'good morning', 'good night', 'hello', 'hi ', 'hey ', 'thank you', 'thanks',
        'welcome', 'bye', 'see you', 'ok ', 'okay', 'yes', 'no', 'maybe',
        'how are you', 'whats up', 'anyone', 'someone', 'everyone',
        'problem', 'issue', 'help', 'question', 'doubt', 'query'
    ];
    
    foreach ($invalid_phrases as $phrase) {
        if (strpos($text, $phrase) !== false) {
            return false;
        }
    }
    
    $movie_patterns = [
        'movie', 'film', 'video', 'download', 'watch', 'hd', 'full', 'part',
        'series', 'episode', 'season', 'bollywood', 'hollywood'
    ];
    
    foreach ($movie_patterns as $pattern) {
        if (strpos($text, $pattern) !== false) {
            return true;
        }
    }
    
    if (preg_match('/^[a-zA-Z0-9\s\-\.\,]{3,}$/', $text)) {
        return true;
    }
    
    return false;
}
?>
