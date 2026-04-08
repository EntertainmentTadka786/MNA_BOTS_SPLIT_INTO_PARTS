<?php
// ==============================
// ERROR REPORTING & TIMEZONE
// ==============================
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Kolkata');

// ==============================
// MAINTENANCE MODE
// ==============================
$MAINTENANCE_MODE = false;
$MAINTENANCE_MESSAGE = "🛠️ <b>Bot Under Maintenance</b>\n\nWe're temporarily unavailable.\nWill be back soon!\n\nThanks for patience 🙏";

// ==============================
// RENDER.COM SPECIFIC CONFIGURATION
// ==============================
$port = getenv('PORT') ?: '80';

if (!getenv('BOT_TOKEN')) {
    die("❌ BOT_TOKEN environment variable set nahi hai.");
}

// ==============================
// ENVIRONMENT VARIABLES TO CONSTANTS
// ==============================
define('BOT_TOKEN', getenv('BOT_TOKEN'));

// Channel Configurations
define('MAIN_CHANNEL', '@EntertainmentTadka786');
define('MAIN_CHANNEL_ID', '-1003181705395');
define('THEATER_CHANNEL', '@threater_print_movies');
define('THEATER_CHANNEL_ID', '-1002831605258');
define('SERIAL_CHANNEL', '@Entertainment_Tadka_Serial_786');
define('SERIAL_CHANNEL_ID', '-1003614546520');
define('BACKUP_CHANNEL_USERNAME', '@ETBackup');
define('BACKUP_CHANNEL_ID', '-1002964109368');
define('REQUEST_GROUP', '@EntertainmentTadka7860');
define('REQUEST_GROUP_ID', '-1003083386043');
define('PRIVATE_CHANNEL_1_ID', '-1003251791991');
define('PRIVATE_CHANNEL_2_ID', '-1002337293281');
define('ADMIN_ID', (int)getenv('ADMIN_ID'));

// File paths
define('CSV_FILE', 'movies.csv');
define('USERS_FILE', 'users.json');
define('STATS_FILE', 'bot_stats.json');
define('REQUEST_FILE', 'movie_requests.json');
define('BACKUP_DIR', 'backups/');
define('LOG_FILE', 'bot_activity.log');
define('FORWARD_SETTINGS_FILE', 'forward_settings.json');

// Cache & Pagination constants
define('CACHE_EXPIRY', 300);
define('ITEMS_PER_PAGE', 10);
define('MAX_SEARCH_RESULTS', 15);
define('DAILY_REQUEST_LIMIT', 5);
define('AUTO_BACKUP_HOUR', '03');

// ==============================
// GLOBAL VARIABLES
// ==============================
$movie_messages = array();
$movie_cache = array();
$waiting_users = array();
$user_pagination_sessions = array();

// ==============================
// CHECK ESSENTIAL CHANNELS
// ==============================
if (!MAIN_CHANNEL_ID || !THEATER_CHANNEL_ID || !BACKUP_CHANNEL_ID) {
    die("❌ Essential channel IDs environment variables set nahi hain.");
}

// ==============================
// INITIALIZE CACHE HELPER
// ==============================
function get_cached_value($key, $default = null) {
    $cache_file = 'cache_' . $key . '.json';
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if ($data && isset($data['value']) && isset($data['expires']) && $data['expires'] > time()) {
            return $data['value'];
        }
    }
    return $default;
}

function set_cached_value($key, $value, $ttl = 300) {
    $cache_file = 'cache_' . $key . '.json';
    $data = [
        'value' => $value,
        'expires' => time() + $ttl
    ];
    file_put_contents($cache_file, json_encode($data));
}
?>
