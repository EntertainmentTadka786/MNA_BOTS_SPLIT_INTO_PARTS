<?php
// ==============================
// ENTRY POINT - ENTERTAINMENT TADKA BOT v3.0
// ==============================

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/telegram_api.php';
require_once __DIR__ . '/inc/channel_mapping.php';
require_once __DIR__ . '/inc/forward_header.php';
require_once __DIR__ . '/inc/typing_indicators.php';
require_once __DIR__ . '/inc/csv_manager.php';
require_once __DIR__ . '/inc/search_engine.php';
require_once __DIR__ . '/inc/movie_delivery.php';
require_once __DIR__ . '/inc/request_system.php';
require_once __DIR__ . '/inc/user_management.php';
require_once __DIR__ . '/inc/category_browse.php';
require_once __DIR__ . '/inc/backup_system.php';
require_once __DIR__ . '/inc/channel_info.php';
require_once __DIR__ . '/inc/admin_panel.php';
require_once __DIR__ . '/inc/command_handlers.php';
require_once __DIR__ . '/inc/webhook_handler.php';

// Initialize files on first run
initialize_files();

// Process incoming webhook update
$update = json_decode(file_get_contents('php://input'), true);

if ($update) {
    process_webhook_update($update);
} else {
    show_status_page();
}
?>