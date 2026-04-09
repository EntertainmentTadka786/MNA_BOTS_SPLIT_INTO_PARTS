<?php
// ==============================
// BILKUL SIMPLE SEARCH SYSTEM
// ==============================

function smart_search($query) {
    global $movie_messages;
    $query_lower = strtolower(trim($query));
    $results = array();
    
    foreach ($movie_messages as $movie => $entries) {
        $score = 0;
        
        // Exact match
        if ($movie == $query_lower) {
            $score = 100;
        }
        // Partial match
        elseif (strpos($movie, $query_lower) !== false) {
            $score = 80;
        }
        // Similar text
        else {
            similar_text($movie, $query_lower, $similarity);
            if ($similarity > 60) $score = $similarity;
        }
        
        if ($score > 0) {
            $results[$movie] = [
                'score' => $score,
                'count' => count($entries),
                'latest_entry' => end($entries)
            ];
        }
    }
    
    // Sort by score
    uasort($results, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    return array_slice($results, 0, MAX_SEARCH_RESULTS);
}

function detect_language($text) {
    $hindi_chars = preg_match('/[\x{0900}-\x{097F}]/u', $text);
    return $hindi_chars ? 'hindi' : 'english';
}

function send_multilingual_response($chat_id, $message_type, $language) {
    $responses = [
        'hindi' => [
            'searching' => "🔍 Dhoondh raha hoon...",
            'not_found' => "❌ Movie nahi mili.\n\n📝 Request kar sakte ho: /request movie_name"
        ],
        'english' => [
            'searching' => "🔍 Searching...",
            'not_found' => "❌ Movie not found.\n\n📝 You can request: /request movie_name"
        ]
    ];
    
    sendMessage($chat_id, $responses[$language][$message_type]);
}

function advanced_search($chat_id, $query, $user_id = null) {
    $q = strtolower(trim($query));
    
    if (strlen($q) < 2) {
        sendMessage($chat_id, "❌ Kam se kam 2 characters daalo");
        return;
    }
    
    $found = smart_search($q);
    
    if (!empty($found)) {
        update_stats('successful_searches', 1);
        
        $msg = "🔍 Found " . count($found) . " movies for '$query':\n\n";
        $i = 1;
        $buttons = [];
        
        foreach ($found as $movie => $data) {
            $msg .= "$i. $movie\n";
            $i++;
            $buttons[] = [['text' => $movie, 'callback_data' => $movie]];
            if ($i > 10) break;
        }
        
        $buttons[] = [['text' => '📝 Request Movie', 'callback_data' => 'request_movie']];
        
        sendMessage($chat_id, $msg);
        sendMessage($chat_id, "Click on movie name:", ['inline_keyboard' => $buttons]);
        
    } else {
        update_stats('failed_searches', 1);
        $lang = detect_language($query);
        send_multilingual_response($chat_id, 'not_found', $lang);
    }
    
    update_stats('total_searches', 1);
}

function is_valid_movie_query($text) {
    $text = strtolower(trim($text));
    
    if (strpos($text, '/') === 0) return true;
    if (strlen($text) < 3) return false;
    
    $invalid = ['good morning', 'good night', 'hello', 'hi', 'thank', 'bye', 'ok', 'yes', 'no'];
    foreach ($invalid as $word) {
        if (strpos($text, $word) !== false) return false;
    }
    
    return true;
}
?>
