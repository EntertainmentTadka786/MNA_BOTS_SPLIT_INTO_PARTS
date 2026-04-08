<?php
// ==============================
// ENHANCED REQUEST SYSTEM
// ==============================

function can_user_request($user_id) {
    $requests_data = json_decode(file_get_contents(REQUEST_FILE), true);
    $today = date('Y-m-d');
    
    $user_requests_today = 0;
    foreach ($requests_data['requests'] ?? [] as $request) {
        if ($request['user_id'] == $user_id && $request['date'] == $today) {
            $user_requests_today++;
        }
    }
    
    return $user_requests_today < DAILY_REQUEST_LIMIT;
}

function add_movie_request($user_id, $movie_name, $language = 'hindi') {
    if (!can_user_request($user_id)) {
        return false;
    }
    
    $requests_data = json_decode(file_get_contents(REQUEST_FILE), true);
    
    $request_id = uniqid();
    $requests_data['requests'][] = [
        'id' => $request_id,
        'user_id' => $user_id,
        'movie_name' => $movie_name,
        'language' => $language,
        'date' => date('Y-m-d'),
        'time' => date('H:i:s'),
        'status' => 'pending'
    ];
    
    if (!isset($requests_data['user_request_count'][$user_id])) {
        $requests_data['user_request_count'][$user_id] = 0;
    }
    $requests_data['user_request_count'][$user_id]++;
    
    file_put_contents(REQUEST_FILE, json_encode($requests_data, JSON_PRETTY_PRINT));
    
    $admin_msg = "🎯 New Movie Request\n\n";
    $admin_msg .= "🎬 Movie: $movie_name\n";
    $admin_msg .= "🗣️ Language: $language\n";
    $admin_msg .= "👤 User ID: $user_id\n";
    $admin_msg .= "📅 Date: " . date('Y-m-d H:i:s') . "\n";
    $admin_msg .= "🆔 Request ID: $request_id";
    
    sendMessage(ADMIN_ID, $admin_msg);
    bot_log("Movie request added: $movie_name by $user_id");
    
    return true;
}

function get_pending_requests($chat_id) {
    if ($chat_id != ADMIN_ID) {
        sendMessage($chat_id, "❌ Access denied. Admin only command.");
        return [];
    }
    
    $requests_data = json_decode(file_get_contents(REQUEST_FILE), true);
    $pending = $requests_data['requests'] ?? [];
    
    if (empty($pending)) {
        $message = "📝 <b>Pending Requests</b>\n\n✅ No pending requests! All good.";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Bulk Approve', 'callback_data' => 'admin_bulk_approve'],
                    ['text' => '❌ Bulk Reject', 'callback_data' => 'admin_bulk_reject']
                ],
                [
                    ['text' => '🔙 Back to Panel', 'callback_data' => 'refresh_panel']
                ]
            ]
        ];
        
        sendMessage($chat_id, $message, $keyboard, 'HTML');
        return [];
    }
    
    $message = "📝 <b>Pending Requests (" . count($pending) . ")</b>\n\n";
    
    foreach (array_slice($pending, 0, 20) as $index => $request) {
        $message .= ($index + 1) . ". 🎬 <b>" . htmlspecialchars($request['movie_name']) . "</b>\n";
        $message .= "   👤 User: <code>" . $request['user_id'] . "</code>\n";
        $message .= "   📅 Date: " . $request['date'] . " " . $request['time'] . "\n";
        $message .= "   🗣️ Language: " . ucfirst($request['language']) . "\n";
        $message .= "   🆔 ID: <code>" . $request['id'] . "</code>\n\n";
    }
    
    if (count($pending) > 20) {
        $message .= "... and " . (count($pending) - 20) . " more requests\n\n";
    }
    
    $message .= "💡 Use /approve or /reject commands for bulk actions!";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '✅ Bulk Approve All', 'callback_data' => 'bulk_approve_all'],
                ['text' => '❌ Bulk Reject All', 'callback_data' => 'bulk_reject_all']
            ],
            [
                ['text' => '🔙 Back to Panel', 'callback_data' => 'refresh_panel']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
    return $pending;
}

function bulk_approve_requests($chat_id, $count = null) {
    if ($chat_id != ADMIN_ID) {
        sendMessage($chat_id, "❌ Access denied. Admin only command.");
        return;
    }
    
    $requests_data = json_decode(file_get_contents(REQUEST_FILE), true);
    $pending = $requests_data['requests'] ?? [];
    
    if (empty($pending)) {
        sendMessage($chat_id, "❌ No pending requests to approve!");
        return;
    }
    
    $approve_count = ($count === null || $count > count($pending)) ? count($pending) : $count;
    $approved_requests = array_slice($pending, 0, $approve_count);
    $approved_count = 0;
    $failed_count = 0;
    
    $progress_msg = sendMessage($chat_id, "🔄 Approving $approve_count requests...\n\nProgress: 0%");
    $progress_msg_id = $progress_msg['result']['message_id'];
    
    foreach ($approved_requests as $index => $request) {
        try {
            $request_index = array_search($request, $requests_data['requests']);
            if ($request_index !== false) {
                unset($requests_data['requests'][$request_index]);
                $requests_data['requests'] = array_values($requests_data['requests']);
            }
            
            if (!isset($requests_data['completed_requests'])) {
                $requests_data['completed_requests'] = [];
            }
            
            $request['status'] = 'approved';
            $request['approved_at'] = date('Y-m-d H:i:s');
            $requests_data['completed_requests'][] = $request;
            
            $user_message = "✅ <b>Movie Request Approved!</b>\n\n";
            $user_message .= "🎬 Movie: <b>" . htmlspecialchars($request['movie_name']) . "</b>\n";
            $user_message .= "📅 Request Date: " . $request['date'] . "\n\n";
            $user_message .= "🔍 Use /search " . urlencode($request['movie_name']) . " to find this movie!\n\n";
            $user_message .= "🍿 Join @EntertainmentTadka786 for latest updates!";
            
            sendMessage($request['user_id'], $user_message, null, 'HTML');
            $approved_count++;
            
            if (($index + 1) % 5 == 0) {
                $progress = round((($index + 1) / $approve_count) * 100);
                editMessage($chat_id, $progress_msg_id, "🔄 Approving $approve_count requests...\n\nProgress: $progress%\n✅ Approved: $approved_count\n❌ Failed: $failed_count");
            }
            
            usleep(200000);
            
        } catch (Exception $e) {
            $failed_count++;
            bot_log("Bulk approve failed for request {$request['id']}: " . $e->getMessage(), 'ERROR');
        }
    }
    
    file_put_contents(REQUEST_FILE, json_encode($requests_data, JSON_PRETTY_PRINT));
    
    editMessage($chat_id, $progress_msg_id, "✅ <b>Bulk Approval Complete!</b>\n\n📊 Total Processed: $approve_count\n✅ Successfully Approved: $approved_count\n❌ Failed: $failed_count\n\n🕐 Completed at: " . date('Y-m-d H:i:s'));
    
    bot_log("Bulk approved $approved_count requests by admin $chat_id");
    
    $summary = "📊 <b>Bulk Approval Summary</b>\n\n";
    $summary .= "✅ Approved: $approved_count requests\n";
    $summary .= "❌ Failed: $failed_count requests\n";
    $summary .= "📅 Time: " . date('Y-m-d H:i:s') . "\n\n";
    
    if ($approved_count > 0) {
        $summary .= "📋 <b>Approved Movies:</b>\n";
        foreach (array_slice($approved_requests, 0, 10) as $req) {
            $summary .= "• " . htmlspecialchars($req['movie_name']) . " (by user {$req['user_id']})\n";
        }
        if (count($approved_requests) > 10) {
            $summary .= "... and " . (count($approved_requests) - 10) . " more\n";
        }
    }
    
    sendMessage($chat_id, $summary, null, 'HTML');
}

function bulk_reject_requests($chat_id, $count = null) {
    if ($chat_id != ADMIN_ID) {
        sendMessage($chat_id, "❌ Access denied. Admin only command.");
        return;
    }
    
    $requests_data = json_decode(file_get_contents(REQUEST_FILE), true);
    $pending = $requests_data['requests'] ?? [];
    
    if (empty($pending)) {
        sendMessage($chat_id, "❌ No pending requests to reject!");
        return;
    }
    
    $reject_count = ($count === null || $count > count($pending)) ? count($pending) : $count;
    $rejected_requests = array_slice($pending, 0, $reject_count);
    $rejected_count = 0;
    
    $progress_msg = sendMessage($chat_id, "❌ Rejecting $reject_count requests...\n\nProgress: 0%");
    $progress_msg_id = $progress_msg['result']['message_id'];
    
    foreach ($rejected_requests as $index => $request) {
        try {
            $request_index = array_search($request, $requests_data['requests']);
            if ($request_index !== false) {
                unset($requests_data['requests'][$request_index]);
                $requests_data['requests'] = array_values($requests_data['requests']);
            }
            
            if (!isset($requests_data['completed_requests'])) {
                $requests_data['completed_requests'] = [];
            }
            
            $request['status'] = 'rejected';
            $request['rejected_at'] = date('Y-m-d H:i:s');
            $requests_data['completed_requests'][] = $request;
            
            $user_message = "❌ <b>Movie Request Rejected</b>\n\n";
            $user_message .= "🎬 Movie: <b>" . htmlspecialchars($request['movie_name']) . "</b>\n";
            $user_message .= "📅 Request Date: " . $request['date'] . "\n\n";
            $user_message .= "💡 Possible reasons:\n";
            $user_message .= "• Movie already available\n";
            $user_message .= "• Invalid movie name\n";
            $user_message .= "• Technical limitations\n\n";
            $user_message .= "📝 Try requesting again with correct spelling!\n";
            $user_message .= "🍿 Join @EntertainmentTadka7860 for support!";
            
            sendMessage($request['user_id'], $user_message, null, 'HTML');
            $rejected_count++;
            
            if (($index + 1) % 5 == 0) {
                $progress = round((($index + 1) / $reject_count) * 100);
                editMessage($chat_id, $progress_msg_id, "❌ Rejecting $reject_count requests...\n\nProgress: $progress%\n❌ Rejected: $rejected_count");
            }
            
            usleep(200000);
            
        } catch (Exception $e) {
            bot_log("Bulk reject failed for request {$request['id']}: " . $e->getMessage(), 'ERROR');
        }
    }
    
    file_put_contents(REQUEST_FILE, json_encode($requests_data, JSON_PRETTY_PRINT));
    
    editMessage($chat_id, $progress_msg_id, "✅ <b>Bulk Rejection Complete!</b>\n\n📊 Total Processed: $reject_count\n❌ Successfully Rejected: $rejected_count\n\n🕐 Completed at: " . date('Y-m-d H:i:s'));
    
    bot_log("Bulk rejected $rejected_count requests by admin $chat_id");
}

function approve_movie_request($chat_id, $request_id, $callback_query_id) {
    $requests_data = json_decode(file_get_contents(REQUEST_FILE), true);
    
    $request_index = null;
    $request = null;
    
    foreach ($requests_data['requests'] as $index => $req) {
        if ($req['id'] == $request_id) {
            $request_index = $index;
            $request = $req;
            break;
        }
    }
    
    if ($request === null) {
        sendMessage($chat_id, "❌ Request not found!");
        answerCallbackQuery($callback_query_id, "Request not found", true);
        return;
    }
    
    unset($requests_data['requests'][$request_index]);
    $requests_data['requests'] = array_values($requests_data['requests']);
    
    if (!isset($requests_data['completed_requests'])) {
        $requests_data['completed_requests'] = [];
    }
    
    $request['status'] = 'approved';
    $request['approved_at'] = date('Y-m-d H:i:s');
    $requests_data['completed_requests'][] = $request;
    
    file_put_contents(REQUEST_FILE, json_encode($requests_data, JSON_PRETTY_PRINT));
    
    $user_message = "✅ <b>Movie Request Approved!</b>\n\n";
    $user_message .= "🎬 Movie: <b>" . htmlspecialchars($request['movie_name']) . "</b>\n";
    $user_message .= "📅 Request Date: " . $request['date'] . "\n\n";
    $user_message .= "🔍 Use /search " . urlencode($request['movie_name']) . " to find this movie!\n\n";
    $user_message .= "🍿 Join @EntertainmentTadka786 for latest updates!";
    
    sendMessage($request['user_id'], $user_message, null, 'HTML');
    
    sendMessage($chat_id, "✅ Request approved and user notified!\n\nMovie: " . htmlspecialchars($request['movie_name']));
    answerCallbackQuery($callback_query_id, "Request approved");
}

function reject_movie_request($chat_id, $request_id, $callback_query_id) {
    $requests_data = json_decode(file_get_contents(REQUEST_FILE), true);
    
    $request_index = null;
    $request = null;
    
    foreach ($requests_data['requests'] as $index => $req) {
        if ($req['id'] == $request_id) {
            $request_index = $index;
            $request = $req;
            break;
        }
    }
    
    if ($request === null) {
        sendMessage($chat_id, "❌ Request not found!");
        answerCallbackQuery($callback_query_id, "Request not found", true);
        return;
    }
    
    unset($requests_data['requests'][$request_index]);
    $requests_data['requests'] = array_values($requests_data['requests']);
    
    file_put_contents(REQUEST_FILE, json_encode($requests_data, JSON_PRETTY_PRINT));
    
    $user_message = "❌ <b>Movie Request Rejected</b>\n\n";
    $user_message .= "🎬 Movie: <b>" . htmlspecialchars($request['movie_name']) . "</b>\n";
    $user_message .= "📅 Request Date: " . $request['date'] . "\n\n";
    $user_message .= "💡 Possible reasons:\n";
    $user_message .= "• Movie already available\n";
    $user_message .= "• Invalid movie name\n";
    $user_message .= "• Technical limitations\n\n";
    $user_message .= "📝 Try requesting again with correct spelling!\n";
    $user_message .= "🍿 Join @EntertainmentTadka7860 for support!";
    
    sendMessage($request['user_id'], $user_message, null, 'HTML');
    
    sendMessage($chat_id, "❌ Request rejected.\n\nMovie: " . htmlspecialchars($request['movie_name']));
    answerCallbackQuery($callback_query_id, "Request rejected");
}

function show_user_requests($chat_id, $user_id) {
    $requests_data = json_decode(file_get_contents(REQUEST_FILE), true);
    $user_requests = [];
    
    foreach ($requests_data['requests'] ?? [] as $request) {
        if ($request['user_id'] == $user_id) {
            $user_requests[] = $request;
        }
    }
    
    if (empty($user_requests)) {
        sendMessage($chat_id, "📭 Aapne abhi tak koi movie request nahi ki hai!");
        return;
    }
    
    $message = "📝 <b>Your Movie Requests</b>\n\n";
    $i = 1;
    
    foreach (array_slice($user_requests, 0, 10) as $request) {
        $status_emoji = $request['status'] == 'approved' ? '✅' : '⏳';
        $message .= "$i. $status_emoji <b>" . htmlspecialchars($request['movie_name']) . "</b>\n";
        $message .= "   📅 " . $request['date'] . " | 🗣️ " . ucfirst($request['language']) . "\n";
        $message .= "   🆔 " . $request['id'] . "\n\n";
        $i++;
    }
    
    $pending_count = count(array_filter($user_requests, function($req) {
        return $req['status'] == 'pending';
    }));
    
    $message .= "📊 <b>Summary:</b>\n";
    $message .= "• Total Requests: " . count($user_requests) . "\n";
    $message .= "• Pending: $pending_count\n";
    $message .= "• Approved: " . (count($user_requests) - $pending_count);
    
    sendMessage($chat_id, $message, null, 'HTML');
}

function show_request_limit($chat_id, $user_id) {
    $requests_data = json_decode(file_get_contents(REQUEST_FILE), true);
    $today = date('Y-m-d');
    $today_requests = 0;
    
    foreach ($requests_data['requests'] ?? [] as $request) {
        if ($request['user_id'] == $user_id && $request['date'] == $today) {
            $today_requests++;
        }
    }
    
    $remaining = DAILY_REQUEST_LIMIT - $today_requests;
    
    $message = "📋 <b>Your Request Limit</b>\n\n";
    $message .= "✅ Daily Limit: " . DAILY_REQUEST_LIMIT . " requests\n";
    $message .= "📅 Used Today: $today_requests requests\n";
    $message .= "🎯 Remaining Today: $remaining requests\n\n";
    
    if ($remaining > 0) {
        $message .= "💡 Use <code>/request movie_name</code> to request movies!";
    } else {
        $message .= "⏳ Limit resets at midnight!";
    }
    
    sendMessage($chat_id, $message, null, 'HTML');
}
?>