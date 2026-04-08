<?php
// ==============================
// TYPING INDICATOR SYSTEM
// ==============================

function sendTypingAction($chat_id) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendChatAction";
    $data = [
        'chat_id' => $chat_id,
        'action' => 'typing'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
}

function sendTypingIndicator($chat_id, $duration_seconds = 2) {
    sendTypingAction($chat_id);
    
    if ($duration_seconds > 0) {
        $start = time();
        $interval = 4;
        
        while (time() - $start < $duration_seconds) {
            usleep(500000);
            if ((time() - $start) % $interval == 0) {
                sendTypingAction($chat_id);
            }
        }
    }
}

function sendUploadPhotoAction($chat_id) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendChatAction";
    $data = [
        'chat_id' => $chat_id,
        'action' => 'upload_photo'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
}

function sendUploadDocumentAction($chat_id) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendChatAction";
    $data = [
        'chat_id' => $chat_id,
        'action' => 'upload_document'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
}

function sendFindLocationAction($chat_id) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendChatAction";
    $data = [
        'chat_id' => $chat_id,
        'action' => 'find_location'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
}

function sendRecordVideoAction($chat_id) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendChatAction";
    $data = [
        'chat_id' => $chat_id,
        'action' => 'record_video'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
}
?>