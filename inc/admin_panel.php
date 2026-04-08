<?php
// ==============================
// ADMIN PANEL SYSTEM
// ==============================

$admin_session_active = false;
$admin_menu_message_id = null;
$admin_panel_users = [];

function send_admin_panel($chat_id, $user_id) {
    if ($user_id != ADMIN_ID) {
        return false;
    }
    
    $panel_message = "👑 <b>Admin Control Panel</b>\n\n";
    
    $panel_message .= "📊 <b>System Status:</b>\n";
    
    $stats = get_stats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $requests_data = json_decode(file_get_contents(REQUEST_FILE), true);
    
    $panel_message .= "• 🎬 Movies: " . ($stats['total_movies'] ?? 0) . "\n";
    $panel_message .= "• 👥 Users: " . count($users_data['users'] ?? []) . "\n";
    $panel_message .= "• 📥 Pending Requests: " . count($requests_data['requests'] ?? []) . "\n";
    $panel_message .= "• 🔍 Today's Searches: " . (($stats['daily_activity'][date('Y-m-d')]['searches'] ?? 0)) . "\n";
    $panel_message .= "• 📥 Today's Downloads: " . (($stats['daily_activity'][date('Y-m-d')]['downloads'] ?? 0)) . "\n\n";
    
    $panel_message .= "🛠️ <b>Quick Actions:</b>\n";
    $panel_message .= "• Click buttons below to manage bot\n";
    $panel_message .= "• View detailed statistics\n";
    $panel_message .= "• Manage movie database\n";
    $panel_message .= "• Handle user requests\n\n";
    
    $panel_message .= "🕐 Last Updated: " . date('Y-m-d H:i:s');
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📊 Full Statistics', 'callback_data' => 'admin_stats'],
                ['text' => '🎬 Movie Management', 'callback_data' => 'admin_movies']
            ],
            [
                ['text' => '📝 Pending Requests', 'callback_data' => 'admin_requests'],
                ['text' => '👥 User Management', 'callback_data' => 'admin_users']
            ],
            [
                ['text' => '📢 Broadcast Message', 'callback_data' => 'admin_broadcast'],
                ['text' => '🔄 System Actions', 'callback_data' => 'admin_system']
            ],
            [
                ['text' => '💾 Backup & Restore', 'callback_data' => 'admin_backup'],
                ['text' => '⚙️ Bot Settings', 'callback_data' => 'admin_settings']
            ],
            [
                ['text' => '🔐 Forward Header Settings', 'callback_data' => 'admin_forward_settings'],
                ['text' => '📋 Bulk Actions', 'callback_data' => 'admin_bulk_actions']
            ],
            [
                ['text' => '❌ Close Panel', 'callback_data' => 'admin_close']
            ]
        ]
    ];
    
    $result = sendMessage($chat_id, $panel_message, $keyboard, 'HTML');
    
    if ($result && isset($result['result']['message_id'])) {
        global $admin_menu_message_id;
        $admin_menu_message_id = $result['result']['message_id'];
    }
    
    return true;
}

function handle_admin_callback($chat_id, $user_id, $callback_data, $callback_query_id) {
    if ($user_id != ADMIN_ID) {
        answerCallbackQuery($callback_query_id, "❌ Admin access only!", true);
        return false;
    }
    
    switch ($callback_data) {
        case 'admin_stats':
            show_admin_stats($chat_id, $callback_query_id);
            break;
            
        case 'admin_movies':
            show_movie_management_menu($chat_id, $callback_query_id);
            break;
            
        case 'admin_requests':
            show_pending_requests($chat_id, $callback_query_id);
            break;
            
        case 'admin_users':
            show_user_management_menu($chat_id, $callback_query_id);
            break;
            
        case 'admin_broadcast':
            show_broadcast_menu($chat_id, $callback_query_id);
            break;
            
        case 'admin_system':
            show_system_actions_menu($chat_id, $callback_query_id);
            break;
            
        case 'admin_backup':
            show_backup_menu($chat_id, $callback_query_id);
            break;
            
        case 'admin_settings':
            show_settings_menu($chat_id, $callback_query_id);
            break;
            
        case 'admin_forward_settings':
            show_forward_settings_menu($chat_id, $callback_query_id);
            break;
            
        case 'admin_bulk_actions':
            show_bulk_actions_menu($chat_id, $callback_query_id);
            break;
            
        case 'admin_close':
            deleteMessage($chat_id, $admin_menu_message_id ?? 0);
            sendMessage($chat_id, "👋 Admin Panel Closed.\n\nUse /admin to reopen.");
            answerCallbackQuery($callback_query_id, "Panel closed");
            break;
            
        default:
            if (strpos($callback_data, 'approve_req_') === 0) {
                $request_id = str_replace('approve_req_', '', $callback_data);
                approve_movie_request($chat_id, $request_id, $callback_query_id);
            } elseif (strpos($callback_data, 'reject_req_') === 0) {
                $request_id = str_replace('reject_req_', '', $callback_data);
                reject_movie_request($chat_id, $request_id, $callback_query_id);
            } elseif (strpos($callback_data, 'delete_movie_') === 0) {
                $movie_name = urldecode(str_replace('delete_movie_', '', $callback_data));
                delete_movie_from_db($chat_id, $movie_name, $callback_query_id);
            } elseif (strpos($callback_data, 'view_user_') === 0) {
                $target_user_id = str_replace('view_user_', '', $callback_data);
                view_user_details($chat_id, $target_user_id, $callback_query_id);
            } elseif (strpos($callback_data, 'ban_user_') === 0) {
                $target_user_id = str_replace('ban_user_', '', $callback_data);
                toggle_user_ban($chat_id, $target_user_id, $callback_query_id);
            } elseif (strpos($callback_data, 'toggle_forward_') === 0) {
                $channel_id = str_replace('toggle_forward_', '', $callback_data);
                $channel_type = (strpos($channel_id, 'private') !== false) ? 'private' : 'public';
                toggle_forward_header($chat_id, $channel_id, $channel_type);
                answerCallbackQuery($callback_query_id, "Forward header toggled");
                show_forward_settings_menu($chat_id, $callback_query_id);
            } elseif ($callback_data == 'bulk_approve_all') {
                bulk_approve_requests($chat_id);
                answerCallbackQuery($callback_query_id, "Bulk approve started!");
            } elseif ($callback_data == 'bulk_reject_all') {
                bulk_reject_requests($chat_id);
                answerCallbackQuery($callback_query_id, "Bulk reject started!");
            } elseif ($callback_data == 'bulk_approve_10') {
                bulk_approve_requests($chat_id, 10);
                answerCallbackQuery($callback_query_id, "Approving 10 requests");
            } elseif ($callback_data == 'bulk_approve_25') {
                bulk_approve_requests($chat_id, 25);
                answerCallbackQuery($callback_query_id, "Approving 25 requests");
            } elseif ($callback_data == 'bulk_approve_50') {
                bulk_approve_requests($chat_id, 50);
                answerCallbackQuery($callback_query_id, "Approving 50 requests");
            } elseif ($callback_data == 'manual_backup') {
                manual_backup($chat_id);
                answerCallbackQuery($callback_query_id, "Backup started!");
            } elseif ($callback_data == 'quick_backup') {
                quick_backup($chat_id);
                answerCallbackQuery($callback_query_id, "Quick backup started!");
            } elseif ($callback_data == 'toggle_maintenance') {
                toggle_maintenance_mode_panel($chat_id, $callback_query_id);
            } elseif ($callback_data == 'clear_cache') {
                clear_system_cache($chat_id, $callback_query_id);
            } elseif ($callback_data == 'refresh_panel') {
                send_admin_panel($chat_id, $user_id);
                answerCallbackQuery($callback_query_id, "Panel refreshed");
            }
            break;
    }
    
    return true;
}

function show_admin_stats($chat_id, $callback_query_id) {
    $stats = get_stats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $requests_data = json_decode(file_get_contents(REQUEST_FILE), true);
    
    $message = "📊 <b>Detailed Statistics</b>\n\n";
    
    $message .= "🎬 <b>Movie Database:</b>\n";
    $message .= "• Total Movies: " . ($stats['total_movies'] ?? 0) . "\n\n";
    
    $message .= "👥 <b>User Statistics:</b>\n";
    $message .= "• Total Users: " . count($users_data['users'] ?? []) . "\n";
    $message .= "• Total Requests: " . ($users_data['total_requests'] ?? 0) . "\n";
    $message .= "• Pending Requests: " . count($requests_data['requests'] ?? []) . "\n\n";
    
    $message .= "🔍 <b>Search Statistics:</b>\n";
    $message .= "• Total Searches: " . ($stats['total_searches'] ?? 0) . "\n";
    $message .= "• Successful: " . ($stats['successful_searches'] ?? 0) . "\n";
    $message .= "• Failed: " . ($stats['failed_searches'] ?? 0) . "\n";
    $message .= "• Success Rate: " . (($stats['total_searches'] ?? 0) > 0 ? round((($stats['successful_searches'] ?? 0) / ($stats['total_searches'] ?? 0)) * 100, 2) : 0) . "%\n\n";
    
    $message .= "📥 <b>Download Statistics:</b>\n";
    $message .= "• Total Downloads: " . ($stats['total_downloads'] ?? 0) . "\n\n";
    
    $message .= "📅 <b>Today's Activity:</b>\n";
    $today = date('Y-m-d');
    $message .= "• Searches: " . (($stats['daily_activity'][$today]['searches'] ?? 0)) . "\n";
    $message .= "• Downloads: " . (($stats['daily_activity'][$today]['downloads'] ?? 0)) . "\n\n";
    
    $message .= "💾 <b>System Info:</b>\n";
    $message .= "• PHP Version: " . phpversion() . "\n";
    $message .= "• Memory Usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
    $message .= "• Last Updated: " . ($stats['last_updated'] ?? 'N/A');
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🔄 Refresh Stats', 'callback_data' => 'admin_stats'],
                ['text' => '🔙 Back to Panel', 'callback_data' => 'refresh_panel']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
    answerCallbackQuery($callback_query_id, "Statistics displayed");
}

function show_movie_management_menu($chat_id, $callback_query_id) {
    $stats = get_stats();
    
    $message = "🎬 <b>Movie Management</b>\n\n";
    $message .= "📊 Total Movies: " . ($stats['total_movies'] ?? 0) . "\n\n";
    $message .= "🛠️ <b>Available Actions:</b>\n";
    $message .= "• Delete movies from database\n";
    $message .= "• View all movies\n";
    $message .= "• Search and edit movies\n";
    $message .= "• Add movies manually\n\n";
    $message .= "💡 <b>Quick Commands:</b>\n";
    $message .= "• /checkcsv - View CSV data\n";
    $message .= "• /browse - Browse all movies\n";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📋 View All Movies', 'callback_data' => 'admin_view_all_movies'],
                ['text' => '🔍 Search Movie', 'callback_data' => 'admin_search_movie']
            ],
            [
                ['text' => '➕ Add Movie Manually', 'callback_data' => 'admin_add_movie'],
                ['text' => '🗑️ Delete Movie', 'callback_data' => 'admin_delete_movie']
            ],
            [
                ['text' => '🔙 Back to Panel', 'callback_data' => 'refresh_panel']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
    answerCallbackQuery($callback_query_id, "Movie management menu");
}

function show_pending_requests($chat_id, $callback_query_id) {
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
        answerCallbackQuery($callback_query_id, "No pending requests");
        return;
    }
    
    $message = "📝 <b>Pending Requests (" . count($pending) . ")</b>\n\n";
    
    foreach (array_slice($pending, 0, 10) as $request) {
        $message .= "🎬 <b>" . htmlspecialchars($request['movie_name']) . "</b>\n";
        $message .= "👤 User: <code>" . $request['user_id'] . "</code>\n";
        $message .= "📅 Date: " . $request['date'] . " " . $request['time'] . "\n";
        $message .= "🗣️ Language: " . ucfirst($request['language']) . "\n";
        $message .= "🆔 ID: <code>" . $request['id'] . "</code>\n\n";
    }
    
    if (count($pending) > 10) {
        $message .= "... and " . (count($pending) - 10) . " more requests\n\n";
    }
    
    $message .= "💡 Use buttons below to approve/reject requests:";
    
    $keyboard = ['inline_keyboard' => []];
    
    foreach (array_slice($pending, 0, 5) as $request) {
        $movie_short = strlen($request['movie_name']) > 20 ? substr($request['movie_name'], 0, 20) . '...' : $request['movie_name'];
        $keyboard['inline_keyboard'][] = [
            ['text' => "✅ " . $movie_short, 'callback_data' => 'approve_req_' . $request['id']],
            ['text' => "❌ Reject", 'callback_data' => 'reject_req_' . $request['id']]
        ];
    }
    
    $keyboard['inline_keyboard'][] = [
        ['text' => '✅ Bulk Approve All', 'callback_data' => 'bulk_approve_all'],
        ['text' => '❌ Bulk Reject All', 'callback_data' => 'bulk_reject_all']
    ];
    $keyboard['inline_keyboard'][] = [
        ['text' => '✅ Approve 10', 'callback_data' => 'bulk_approve_10'],
        ['text' => '✅ Approve 25', 'callback_data' => 'bulk_approve_25'],
        ['text' => '✅ Approve 50', 'callback_data' => 'bulk_approve_50']
    ];
    $keyboard['inline_keyboard'][] = [
        ['text' => '🔄 Refresh', 'callback_data' => 'admin_requests'],
        ['text' => '🔙 Back to Panel', 'callback_data' => 'refresh_panel']
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
    answerCallbackQuery($callback_query_id, count($pending) . " pending requests");
}

function show_user_management_menu($chat_id, $callback_query_id) {
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $users = $users_data['users'] ?? [];
    
    $message = "👥 <b>User Management</b>\n\n";
    $message .= "📊 Total Users: " . count($users) . "\n";
    $message .= "📝 Total Requests: " . ($users_data['total_requests'] ?? 0) . "\n\n";
    $message .= "👤 <b>Recent Users:</b>\n";
    
    $recent_users = array_slice($users, -10, 10, true);
    $recent_users = array_reverse($recent_users);
    
    foreach ($recent_users as $user_id => $user) {
        $name = $user['first_name'] ?? 'Unknown';
        if (!empty($user['username'])) {
            $name .= " (@{$user['username']})";
        }
        $message .= "• <code>$user_id</code> - $name\n";
        $message .= "  Joined: {$user['joined']}\n";
    }
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📊 User Statistics', 'callback_data' => 'admin_user_stats'],
                ['text' => '📢 Broadcast', 'callback_data' => 'admin_broadcast']
            ],
            [
                ['text' => '🔙 Back to Panel', 'callback_data' => 'refresh_panel']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
    answerCallbackQuery($callback_query_id, "User management menu");
}

function show_broadcast_menu($chat_id, $callback_query_id) {
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $total_users = count($users_data['users'] ?? []);
    
    $message = "📢 <b>Broadcast Message</b>\n\n";
    $message .= "👥 Total Users: $total_users\n\n";
    $message .= "💡 <b>How to send broadcast:</b>\n";
    $message .= "Use command:\n";
    $message .= "<code>/broadcast Your message here</code>\n\n";
    $message .= "⚠️ <b>Warning:</b>\n";
    $message .= "• Message will be sent to ALL users\n";
    $message .= "• Use carefully!\n";
    $message .= "• Can't be undone\n\n";
    $message .= "📝 <b>Example:</b>\n";
    $message .= "<code>/broadcast New movie added: Pushpa 2!</code>";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📊 View Users', 'callback_data' => 'admin_users'],
                ['text' => '🔙 Back', 'callback_data' => 'refresh_panel']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
    answerCallbackQuery($callback_query_id, "Broadcast instructions");
}

function show_system_actions_menu($chat_id, $callback_query_id) {
    $message = "🔄 <b>System Actions</b>\n\n";
    $message .= "🛠️ <b>Available Actions:</b>\n\n";
    $message .= "• <b>Clear Cache</b> - Refresh movie database\n";
    $message .= "• <b>Toggle Maintenance</b> - Put bot in maintenance mode\n\n";
    $message .= "⚠️ Some actions may take time!";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🧹 Clear Cache', 'callback_data' => 'clear_cache'],
                ['text' => '🔧 Maintenance Mode', 'callback_data' => 'toggle_maintenance']
            ],
            [
                ['text' => '🔙 Back to Panel', 'callback_data' => 'refresh_panel']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
    answerCallbackQuery($callback_query_id, "System actions menu");
}

function show_backup_menu($chat_id, $callback_query_id) {
    $message = "💾 <b>Backup & Restore</b>\n\n";
    $message .= "🛡️ <b>Backup Options:</b>\n\n";
    $message .= "• <b>Full Backup</b> - Complete system backup\n";
    $message .= "• <b>Quick Backup</b> - Essential files only\n";
    $message .= "• <b>Auto-Backup</b> - Runs daily at " . AUTO_BACKUP_HOUR . ":00\n\n";
    $message .= "📡 <b>Backup Channel:</b> " . BACKUP_CHANNEL_USERNAME . "\n\n";
    $message .= "💾 Backups include:\n";
    $message .= "• Movies database (CSV)\n";
    $message .= "• User data (JSON)\n";
    $message .= "• Statistics (JSON)\n";
    $message .= "• Requests (JSON)\n";
    $message .= "• Activity logs";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '💾 Full Backup', 'callback_data' => 'manual_backup'],
                ['text' => '⚡ Quick Backup', 'callback_data' => 'quick_backup']
            ],
            [
                ['text' => '📊 Backup Status', 'callback_data' => 'backup_status'],
                ['text' => '🔙 Back', 'callback_data' => 'refresh_panel']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
    answerCallbackQuery($callback_query_id, "Backup menu");
}

function show_settings_menu($chat_id, $callback_query_id) {
    global $MAINTENANCE_MODE;
    
    $message = "⚙️ <b>Bot Settings</b>\n\n";
    $message .= "🔧 <b>Current Settings:</b>\n";
    $message .= "• Maintenance Mode: " . ($MAINTENANCE_MODE ? "🔧 ON" : "✅ OFF") . "\n";
    $message .= "• Daily Request Limit: " . DAILY_REQUEST_LIMIT . "\n";
    $message .= "• Items Per Page: 10\n";
    $message .= "• Cache Expiry: " . CACHE_EXPIRY . " seconds\n\n";
    
    $message .= "🛠️ <b>Quick Settings:</b>\n";
    $message .= "• Toggle maintenance mode\n";
    $message .= "• Clear system cache\n";
    $message .= "• Refresh configuration";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '🔧 Toggle Maintenance', 'callback_data' => 'toggle_maintenance'],
                ['text' => '🧹 Clear Cache', 'callback_data' => 'clear_cache']
            ],
            [
                ['text' => '🔄 Refresh Config', 'callback_data' => 'refresh_panel'],
                ['text' => '🔙 Back', 'callback_data' => 'refresh_panel']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
    answerCallbackQuery($callback_query_id, "Settings menu");
}

function show_forward_settings_menu($chat_id, $callback_query_id) {
    $settings = initialize_forward_settings();
    
    $message = "🔐 <b>Forward Header Settings</b>\n\n";
    $message .= "📋 <b>Public Channels (Header ON by default):</b>\n";
    
    foreach ($settings['public_channels'] as $channel_id => $channel) {
        $status = $channel['forward_header'] ? "✅ ON" : "❌ OFF";
        $message .= "• {$channel['name']}: $status\n";
        $message .= "  <code>$channel_id</code>\n";
    }
    
    $message .= "\n🔒 <b>Private Channels (Header OFF by default):</b>\n";
    
    foreach ($settings['private_channels'] as $channel_id => $channel) {
        $status = $channel['forward_header'] ? "✅ ON" : "❌ OFF";
        $message .= "• {$channel['name']}: $status\n";
        $message .= "  <code>$channel_id</code>\n";
    }
    
    $message .= "\n💡 <b>How it works:</b>\n";
    $message .= "• ✅ ON = Forward with sender header (shows original channel)\n";
    $message .= "• ❌ OFF = Copy without header (hides original source)\n\n";
    $message .= "🔄 Click a channel below to toggle its setting:";
    
    $keyboard = ['inline_keyboard' => []];
    
    $keyboard['inline_keyboard'][] = [['text' => '📢 PUBLIC CHANNELS', 'callback_data' => 'noop']];
    foreach ($settings['public_channels'] as $channel_id => $channel) {
        $status_icon = $channel['forward_header'] ? "✅" : "❌";
        $keyboard['inline_keyboard'][] = [
            ['text' => "$status_icon {$channel['name']}", 'callback_data' => 'toggle_forward_' . $channel_id]
        ];
    }
    
    $keyboard['inline_keyboard'][] = [['text' => '🔒 PRIVATE CHANNELS', 'callback_data' => 'noop']];
    foreach ($settings['private_channels'] as $channel_id => $channel) {
        $status_icon = $channel['forward_header'] ? "✅" : "❌";
        $keyboard['inline_keyboard'][] = [
            ['text' => "$status_icon {$channel['name']}", 'callback_data' => 'toggle_forward_' . $channel_id]
        ];
    }
    
    $keyboard['inline_keyboard'][] = [
        ['text' => '🔙 Back to Panel', 'callback_data' => 'refresh_panel']
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
    answerCallbackQuery($callback_query_id, "Forward header settings");
}

function show_bulk_actions_menu($chat_id, $callback_query_id) {
    $requests_data = json_decode(file_get_contents(REQUEST_FILE), true);
    $pending_count = count($requests_data['requests'] ?? []);
    
    $message = "📋 <b>Bulk Actions Menu</b>\n\n";
    $message .= "📊 Current Pending Requests: <b>$pending_count</b>\n\n";
    
    $message .= "🛠️ <b>Available Bulk Actions:</b>\n\n";
    $message .= "• <b>Bulk Approve</b> - Approve multiple pending requests\n";
    $message .= "• <b>Bulk Reject</b> - Reject multiple pending requests\n";
    $message .= "• <b>View All Pending</b> - See all pending requests\n\n";
    
    $message .= "💡 <b>Commands:</b>\n";
    $message .= "• <code>/pending</code> - View all pending requests\n";
    $message .= "• <code>/approve 10</code> - Approve 10 requests\n";
    $message .= "• <code>/reject 5</code> - Reject 5 requests\n\n";
    
    $message .= "⚠️ <b>Warning:</b> Bulk actions cannot be undone!";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '✅ Bulk Approve All', 'callback_data' => 'bulk_approve_all'],
                ['text' => '❌ Bulk Reject All', 'callback_data' => 'bulk_reject_all']
            ],
            [
                ['text' => '✅ Approve 10', 'callback_data' => 'bulk_approve_10'],
                ['text' => '✅ Approve 25', 'callback_data' => 'bulk_approve_25'],
                ['text' => '✅ Approve 50', 'callback_data' => 'bulk_approve_50']
            ],
            [
                ['text' => '📝 View Pending Requests', 'callback_data' => 'admin_requests'],
                ['text' => '🔙 Back', 'callback_data' => 'refresh_panel']
            ]
        ]
    ];
    
    sendMessage($chat_id, $message, $keyboard, 'HTML');
    answerCallbackQuery($callback_query_id, "Bulk actions menu");
}

function toggle_maintenance_mode_panel($chat_id, $callback_query_id) {
    global $MAINTENANCE_MODE;
    $MAINTENANCE_MODE = !$MAINTENANCE_MODE;
    
    $status = $MAINTENANCE_MODE ? "ENABLED" : "DISABLED";
    $message = "🔧 Maintenance mode $status!\n\n";
    $message .= $MAINTENANCE_MODE ? "Bot is now in maintenance mode." : "Bot is now operational.";
    
    sendMessage($chat_id, $message);
    answerCallbackQuery($callback_query_id, "Maintenance mode " . ($MAINTENANCE_MODE ? "ON" : "OFF"));
    
    show_settings_menu($chat_id, $callback_query_id);
}

function clear_system_cache($chat_id, $callback_query_id) {
    global $movie_cache;
    $movie_cache = [];
    
    sendMessage($chat_id, "✅ System cache cleared successfully!\n\nMovie database refreshed.");
    answerCallbackQuery($callback_query_id, "Cache cleared");
}
?>