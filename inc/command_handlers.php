<?php
// ==============================
// COMMAND HANDLER (FINAL VERSION)
// ==============================

function handle_command($chat_id, $user_id, $command, $params = []) {
    switch ($command) {
        case '/start':
            sendTypingIndicator($chat_id, 2);
            $welcome = "🎬 Welcome to Entertainment Tadka!\n\n";
            
            $welcome .= "📢 <b>How to use this bot:</b>\n";
            $welcome .= "• Simply type any movie name\n";
            $welcome .= "• Use English or Hindi\n";
            $welcome .= "• Partial names also work\n\n";
            
            $welcome .= "🔍 <b>Examples:</b>\n";
            $welcome .= "• Mandala Murders 2025\n";
            $welcome .= "• Zebra 2024\n";
            $welcome .= "• Squid Game\n";
            $welcome .= "• Now You See Me\n\n";
            
            $welcome .= "❌ <b>Don't type:</b>\n";
            $welcome .= "• Technical questions\n";
            $welcome .= "• Player instructions\n";
            $welcome .= "• Non-movie queries\n\n";
            
            $welcome .= "📢 <b>Join Our Channels:</b>\n";
            $welcome .= "🍿 Main: @EntertainmentTadka786\n";
            $welcome .= "📥 Request: @EntertainmentTadka7860\n";
            $welcome .= "🎭 Theater: @threater_print_movies\n";
            $welcome .= "📂 Backup: @ETBackup\n";
            $welcome .= "📺 Serial: @Entertainment_Tadka_Serial_786\n\n";
            
            $welcome .= "💬 <b>Need help?</b> Use /help";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🔍 Search Movies', 'switch_inline_query_current_chat' => ''],
                        ['text' => '🍿 Main', 'url' => 'https://t.me/EntertainmentTadka786']
                    ],
                    [
                        ['text' => '📥 Request', 'url' => 'https://t.me/EntertainmentTadka7860'],
                        ['text' => '🎭 Theater', 'url' => 'https://t.me/threater_print_movies']
                    ],
                    [
                        ['text' => '📂 Backup', 'url' => 'https://t.me/ETBackup'],
                        ['text' => '📺 Serial', 'url' => 'https://t.me/Entertainment_Tadka_Serial_786']
                    ],
                    [
                        ['text' => '❓ Help', 'callback_data' => 'help_command']
                    ]
                ]
            ];
            
            if ($user_id == ADMIN_ID) {
                $keyboard['inline_keyboard'][] = [
                    ['text' => '👑 Admin Panel', 'callback_data' => 'refresh_panel']
                ];
            }
            
            sendMessage($chat_id, $welcome, $keyboard, 'HTML');
            break;

        case '/help':
            sendTypingIndicator($chat_id, 2);
            $help = "🤖 <b>Entertainment Tadka Bot</b>\n\n";
            
            $help .= "📢 <b>Our Channels:</b>\n";
            $help .= "🍿 Main: @EntertainmentTadka786\n";
            $help .= "📥 Request: @EntertainmentTadka7860\n";
            $help .= "🎭 Theater: @threater_print_movies\n";
            $help .= "📂 Backup: @ETBackup\n";
            $help .= "📺 Serial: @Entertainment_Tadka_Serial_786\n\n";
            
            $help .= "💡 <b>Tip:</b> Just type any movie name to search!";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '📁 Browse Movies', 'callback_data' => 'cmd_browse'],
                        ['text' => '📝 Request Movie', 'callback_data' => 'cmd_request']
                    ],
                    [
                        ['text' => '📋 My Requests', 'callback_data' => 'cmd_myrequests'],
                        ['text' => '🔗 All Channels', 'callback_data' => 'cmd_channels']
                    ],
                    [
                        ['text' => '❓ Help', 'callback_data' => 'help_command'],
                        ['text' => 'ℹ️ Info', 'callback_data' => 'cmd_info']
                    ],
                    [
                        ['text' => '🐛 Report Bug', 'callback_data' => 'cmd_report'],
                        ['text' => '💡 Feedback', 'callback_data' => 'cmd_feedback']
                    ]
                ]
            ];
            
            if ($user_id == ADMIN_ID) {
                $keyboard['inline_keyboard'][] = [
                    ['text' => '👑 Pending Requests', 'callback_data' => 'admin_pending'],
                    ['text' => '✅ Bulk Approve', 'callback_data' => 'admin_bulk_approve']
                ];
                $keyboard['inline_keyboard'][] = [
                    ['text' => '❌ Bulk Reject', 'callback_data' => 'admin_bulk_reject'],
                    ['text' => '🔐 Forward Settings', 'callback_data' => 'admin_forward']
                ];
                $keyboard['inline_keyboard'][] = [
                    ['text' => '📢 Broadcast', 'callback_data' => 'admin_broadcast'],
                    ['text' => '👑 Admin Panel', 'callback_data' => 'refresh_panel']
                ];
            }
            
            sendMessage($chat_id, $help, $keyboard, 'HTML');
            break;

        case '/search':
            sendTypingIndicator($chat_id, 3);
            $movie_name = implode(' ', $params);
            if (empty($movie_name)) {
                sendMessage($chat_id, "❌ Usage: <code>/search movie_name</code>\nExample: <code>/search squid game</code>", null, 'HTML');
                return;
            }
            $lang = detect_language($movie_name);
            send_multilingual_response($chat_id, 'searching', $lang);
            advanced_search($chat_id, $movie_name, $user_id);
            break;

        case '/browse':
        case '/totalupload':
            sendTypingIndicator($chat_id, 1);
            show_category_menu($chat_id);
            break;

        case '/request':
            sendTypingIndicator($chat_id, 1);
            $movie_name = implode(' ', $params);
            if (empty($movie_name)) {
                sendMessage($chat_id, "❌ Usage: <code>/request movie_name</code>\nExample: <code>/request Squid Game</code>", null, 'HTML');
                return;
            }
            $lang = detect_language($movie_name);
            if (add_movie_request($user_id, $movie_name, $lang)) {
                send_multilingual_response($chat_id, 'request_success', $lang);
            } else {
                send_multilingual_response($chat_id, 'request_limit', $lang);
            }
            break;

        case '/myrequests':
            sendTypingIndicator($chat_id, 1);
            show_user_requests($chat_id, $user_id);
            break;

        case '/channel':
            sendTypingIndicator($chat_id, 1);
            show_channel_info($chat_id);
            break;

        case '/info':
            sendTypingIndicator($chat_id, 1);
            show_bot_info($chat_id);
            break;

        case '/ping':
            sendTypingIndicator($chat_id, 1);
            sendMessage($chat_id, "🏓 <b>Bot Status:</b> ✅ Online\n⏰ <b>Server Time:</b> " . date('Y-m-d H:i:s'), null, 'HTML');
            break;

        case '/report':
            sendTypingIndicator($chat_id, 1);
            $bug_report = implode(' ', $params);
            if (empty($bug_report)) {
                sendMessage($chat_id, "❌ Usage: <code>/report bug_description</code>", null, 'HTML');
                return;
            }
            submit_bug_report($chat_id, $user_id, $bug_report);
            break;

        case '/feedback':
            sendTypingIndicator($chat_id, 1);
            $feedback = implode(' ', $params);
            if (empty($feedback)) {
                sendMessage($chat_id, "❌ Usage: <code>/feedback your_feedback</code>", null, 'HTML');
                return;
            }
            submit_feedback($chat_id, $user_id, $feedback);
            break;

        case '/version':
            sendTypingIndicator($chat_id, 1);
            show_version_info($chat_id);
            break;

        case '/admin':
            if ($user_id == ADMIN_ID) {
                send_admin_panel($chat_id, $user_id);
            } else {
                sendMessage($chat_id, "❌ Access denied. Admin only command.");
            }
            break;

        case '/pending':
            if ($user_id == ADMIN_ID) {
                get_pending_requests($chat_id);
            } else {
                sendMessage($chat_id, "❌ Access denied. Admin only command.");
            }
            break;

        case '/approve':
            if ($user_id == ADMIN_ID) {
                $count = isset($params[0]) ? intval($params[0]) : null;
                bulk_approve_requests($chat_id, $count);
            } else {
                sendMessage($chat_id, "❌ Access denied. Admin only command.");
            }
            break;

        case '/reject':
            if ($user_id == ADMIN_ID) {
                $count = isset($params[0]) ? intval($params[0]) : null;
                bulk_reject_requests($chat_id, $count);
            } else {
                sendMessage($chat_id, "❌ Access denied. Admin only command.");
            }
            break;

        case '/forward':
            if ($user_id == ADMIN_ID) {
                show_forward_settings_menu($chat_id, null);
            } else {
                sendMessage($chat_id, "❌ Access denied. Admin only command.");
            }
            break;

        case '/broadcast':
            if ($user_id == ADMIN_ID) {
                $message = implode(' ', $params);
                if (empty($message)) {
                    sendMessage($chat_id, "❌ Usage: <code>/broadcast your_message</code>", null, 'HTML');
                    return;
                }
                send_broadcast($chat_id, $message);
            } else {
                sendMessage($chat_id, "❌ Access denied. Admin only command.");
            }
            break;

        case '/stats':
            if ($user_id == ADMIN_ID) {
                admin_stats($chat_id);
            } else {
                sendMessage($chat_id, "❌ Access denied. Admin only command.");
            }
            break;

        case '/maintenance':
            if ($user_id == ADMIN_ID) {
                $mode = isset($params[0]) ? strtolower($params[0]) : '';
                toggle_maintenance_mode($chat_id, $mode);
            } else {
                sendMessage($chat_id, "❌ Access denied. Admin only command.");
            }
            break;

        case '/cleanup':
            if ($user_id == ADMIN_ID) {
                perform_cleanup($chat_id);
            } else {
                sendMessage($chat_id, "❌ Access denied. Admin only command.");
            }
            break;

        default:
            sendMessage($chat_id, "❌ Unknown command. Use <code>/help</code> to see all commands.", null, 'HTML');
    }
}

function show_bot_info($chat_id) {
    $stats = get_stats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    
    $message = "🤖 <b>Entertainment Tadka Bot</b>\n\n";
    $message .= "📱 <b>Version:</b> 3.0\n";
    $message .= "👨‍💻 <b>Developer:</b> @EntertainmentTadka0786\n\n";
    
    $message .= "📊 <b>Statistics:</b>\n";
    $message .= "• 🎬 Movies: " . ($stats['total_movies'] ?? 0) . "\n";
    $message .= "• 👥 Users: " . count($users_data['users'] ?? []) . "\n";
    $message .= "• 🔍 Searches: " . ($stats['total_searches'] ?? 0) . "\n";
    $message .= "• 📥 Downloads: " . ($stats['total_downloads'] ?? 0) . "\n\n";
    
    $message .= "📢 <b>Channels:</b>\n";
    $message .= "🍿 Main: " . MAIN_CHANNEL . "\n";
    $message .= "📥 Support: " . REQUEST_GROUP . "\n";
    $message .= "🎭 Theater: " . THEATER_CHANNEL . "\n";
    $message .= "📺 Serial: " . SERIAL_CHANNEL;
    
    sendMessage($chat_id, $message, null, 'HTML');
}

function show_version_info($chat_id) {
    $message = "🔄 <b>Bot Version 3.0</b>\n\n";
    $message .= "✅ Clean & simplified\n";
    $message .= "✅ Category-wise browsing\n";
    $message .= "✅ Fast movie search\n\n";
    $message .= "🐛 <b>Report bugs:</b> /report\n";
    $message .= "💡 <b>Feedback:</b> /feedback";
    
    sendMessage($chat_id, $message, null, 'HTML');
}

function submit_bug_report($chat_id, $user_id, $bug_report) {
    $report_id = uniqid();
    
    $admin_message = "🐛 <b>New Bug Report</b>\n\n";
    $admin_message .= "🆔 Report ID: $report_id\n";
    $admin_message .= "👤 User ID: $user_id\n";
    $admin_message .= "📅 Time: " . date('Y-m-d H:i:s') . "\n\n";
    $admin_message .= "📝 <b>Bug Description:</b>\n$bug_report";
    
    sendMessage(ADMIN_ID, $admin_message, null, 'HTML');
    sendMessage($chat_id, "✅ Bug report submitted!\n\n🆔 Report ID: <code>$report_id</code>\n\nWe'll fix it soon! 🛠️", null, 'HTML');
    
    bot_log("Bug report submitted by $user_id: $report_id");
}

function submit_feedback($chat_id, $user_id, $feedback) {
    $feedback_id = uniqid();
    
    $admin_message = "💡 <b>New User Feedback</b>\n\n";
    $admin_message .= "🆔 Feedback ID: $feedback_id\n";
    $admin_message .= "👤 User ID: $user_id\n";
    $admin_message .= "📅 Time: " . date('Y-m-d H:i:s') . "\n\n";
    $admin_message .= "📝 <b>Feedback:</b>\n$feedback";
    
    sendMessage(ADMIN_ID, $admin_message, null, 'HTML');
    sendMessage($chat_id, "✅ Feedback submitted!\n\n🆔 Feedback ID: <code>$feedback_id</code>\n\nThanks for your input! 🌟", null, 'HTML');
    
    bot_log("Feedback submitted by $user_id: $feedback_id");
}

function send_broadcast($chat_id, $message) {
    if ($chat_id != ADMIN_ID) {
        sendMessage($chat_id, "❌ Access denied. Admin only command.");
        return;
    }
    
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $total_users = count($users_data['users'] ?? []);
    $success_count = 0;
    
    $progress_msg = sendMessage($chat_id, "📢 Broadcasting to $total_users users...\n\nProgress: 0%");
    $progress_msg_id = $progress_msg['result']['message_id'];
    
    $i = 0;
    foreach ($users_data['users'] as $user_id => $user) {
        try {
            sendMessage($user_id, "📢 <b>Announcement from Admin:</b>\n\n$message", null, 'HTML');
            $success_count++;
            
            if ($i % 10 === 0) {
                $progress = round(($i / $total_users) * 100);
                editMessage($chat_id, $progress_msg_id, "📢 Broadcasting to $total_users users...\n\nProgress: $progress%");
            }
            
            usleep(100000);
            $i++;
        } catch (Exception $e) {
        }
    }
    
    editMessage($chat_id, $progress_msg_id, "✅ Broadcast completed!\n\n📊 Sent to: $success_count/$total_users users");
    bot_log("Broadcast sent by $chat_id to $success_count users");
}

function toggle_maintenance_mode($chat_id, $mode) {
    global $MAINTENANCE_MODE;
    
    if ($chat_id != ADMIN_ID) {
        sendMessage($chat_id, "❌ Access denied. Admin only command.");
        return;
    }
    
    if ($mode == 'on') {
        $MAINTENANCE_MODE = true;
        sendMessage($chat_id, "🔧 Maintenance mode ENABLED\n\nBot is now in maintenance mode.");
        bot_log("Maintenance mode enabled by $chat_id");
    } elseif ($mode == 'off') {
        $MAINTENANCE_MODE = false;
        sendMessage($chat_id, "✅ Maintenance mode DISABLED\n\nBot is now operational.");
        bot_log("Maintenance mode disabled by $chat_id");
    } else {
        sendMessage($chat_id, "❌ Usage: <code>/maintenance on</code> or <code>/maintenance off</code>", null, 'HTML');
    }
}

function perform_cleanup($chat_id) {
    if ($chat_id != ADMIN_ID) {
        sendMessage($chat_id, "❌ Access denied. Admin only command.");
        return;
    }
    
    $old = glob(BACKUP_DIR . '*', GLOB_ONLYDIR);
    if (count($old) > 7) {
        usort($old, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        $deleted_count = 0;
        foreach (array_slice($old, 0, count($old) - 7) as $d) {
            $files = glob($d . '/*');
            foreach ($files as $ff) @unlink($ff);
            if (@rmdir($d)) $deleted_count++;
        }
    }
    
    global $movie_cache;
    $movie_cache = [];
    
    sendMessage($chat_id, "🧹 Cleanup completed!\n\n• Old backups removed\n• Cache cleared\n• System optimized");
    bot_log("Cleanup performed by $chat_id");
}

function admin_stats($chat_id) {
    if ($chat_id != ADMIN_ID) {
        sendMessage($chat_id, "❌ Access denied. Admin only command.");
        return;
    }
    
    $stats = get_stats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $requests_data = json_decode(file_get_contents(REQUEST_FILE), true);
    
    $msg = "📊 <b>Bot Statistics</b>\n\n";
    $msg .= "🎬 Movies: " . ($stats['total_movies'] ?? 0) . "\n";
    $msg .= "👥 Users: " . count($users_data['users'] ?? []) . "\n";
    $msg .= "📝 Pending Requests: " . count($requests_data['requests'] ?? []) . "\n";
    $msg .= "🔍 Total Searches: " . ($stats['total_searches'] ?? 0) . "\n";
    $msg .= "📥 Total Downloads: " . ($stats['total_downloads'] ?? 0) . "\n";
    $msg .= "✅ Success Rate: " . (($stats['total_searches'] ?? 0) > 0 ? round((($stats['successful_searches'] ?? 0) / ($stats['total_searches'] ?? 0)) * 100, 2) : 0) . "%\n\n";
    
    $today = date('Y-m-d');
    $msg .= "📈 <b>Today:</b>\n";
    $msg .= "• Searches: " . (($stats['daily_activity'][$today]['searches'] ?? 0)) . "\n";
    $msg .= "• Downloads: " . (($stats['daily_activity'][$today]['downloads'] ?? 0)) . "\n";
    
    sendMessage($chat_id, $msg, null, 'HTML');
    bot_log("Admin stats viewed by $chat_id");
}
?>