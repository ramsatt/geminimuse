<?php
// ============================================================
// GeminiMuse — Copy Tracking API
//
// POST {device_id, prompt_id, language}
//   → records the copy event
//   → upserts prompt_stats.copy_count
//   → returns {prompt_id, copy_count}
// ============================================================

require_once __DIR__ . '/cors.php';

handle_cors();

$db     = get_db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    json_error('Method not allowed', 405);
}

$body      = get_body();
$device_id = trim($body['device_id'] ?? '');
$prompt_id = (int) ($body['prompt_id'] ?? 0);
$language  = preg_replace('/[^a-z]/', '', strtolower($body['language'] ?? 'en'));
$language  = substr($language, 0, 5);

if (!$device_id || !validate_device_id($device_id)) {
    json_error('Invalid device_id');
}
if ($prompt_id <= 0) {
    json_error('Invalid prompt_id');
}
if (!$language) {
    $language = 'en';
}

// Simple rate limit: max 20 copies per device per prompt per day
$rate_check = $db->prepare(
    'SELECT COUNT(*) AS cnt FROM copy_events
     WHERE device_id = ? AND prompt_id = ? AND created_at >= CURDATE()'
);
$rate_check->execute([$device_id, $prompt_id]);
$rate = $rate_check->fetch();

if ((int) $rate['cnt'] >= 20) {
    // Still return a count but don't record
    $stat = $db->prepare('SELECT copy_count FROM prompt_stats WHERE prompt_id = ?');
    $stat->execute([$prompt_id]);
    $row   = $stat->fetch();
    $count = $row ? (int) $row['copy_count'] : 0;
    json_response(['prompt_id' => $prompt_id, 'copy_count' => $count, 'rate_limited' => true]);
}

// Record the copy event
$db->prepare(
    'INSERT INTO copy_events (device_id, prompt_id, language) VALUES (?, ?, ?)'
)->execute([$device_id, $prompt_id, $language]);

// Upsert the count cache
$db->prepare(
    'INSERT INTO prompt_stats (prompt_id, copy_count)
     VALUES (?, 1)
     ON DUPLICATE KEY UPDATE copy_count = copy_count + 1'
)->execute([$prompt_id]);

// Return the new count
$stat = $db->prepare('SELECT copy_count FROM prompt_stats WHERE prompt_id = ?');
$stat->execute([$prompt_id]);
$row  = $stat->fetch();

json_response([
    'prompt_id'  => $prompt_id,
    'copy_count' => $row ? (int) $row['copy_count'] : 1,
]);
