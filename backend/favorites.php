<?php
// ============================================================
// GeminiMuse — Favorites API
//
// GET  ?device_id=xxx               → list of prompt IDs
// POST {device_id, prompt_id}       → toggle (add/remove)
// ============================================================

require_once __DIR__ . '/cors.php';

handle_cors();

$db = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: return all favorite prompt IDs for a device ─────────
if ($method === 'GET') {
    $device_id = trim($_GET['device_id'] ?? '');

    if (!$device_id || !validate_device_id($device_id)) {
        json_error('Invalid device_id');
    }

    $stmt = $db->prepare(
        'SELECT prompt_id FROM favorites WHERE device_id = ? ORDER BY created_at DESC'
    );
    $stmt->execute([$device_id]);
    $ids = array_column($stmt->fetchAll(), 'prompt_id');

    json_response(['device_id' => $device_id, 'favorites' => $ids]);
}

// ── POST: toggle a favorite ───────────────────────────────────
if ($method === 'POST') {
    $body      = get_body();
    $device_id = trim($body['device_id'] ?? '');
    $prompt_id = (int) ($body['prompt_id'] ?? 0);

    if (!$device_id || !validate_device_id($device_id)) {
        json_error('Invalid device_id');
    }
    if ($prompt_id <= 0) {
        json_error('Invalid prompt_id');
    }

    // Check if it already exists
    $check = $db->prepare(
        'SELECT id FROM favorites WHERE device_id = ? AND prompt_id = ?'
    );
    $check->execute([$device_id, $prompt_id]);
    $exists = $check->fetch();

    if ($exists) {
        // Remove
        $db->prepare('DELETE FROM favorites WHERE device_id = ? AND prompt_id = ?')
           ->execute([$device_id, $prompt_id]);
        $action = 'removed';
    } else {
        // Add
        $db->prepare('INSERT INTO favorites (device_id, prompt_id) VALUES (?, ?)')
           ->execute([$device_id, $prompt_id]);
        $action = 'added';
    }

    // Return the full updated list
    $stmt = $db->prepare(
        'SELECT prompt_id FROM favorites WHERE device_id = ? ORDER BY created_at DESC'
    );
    $stmt->execute([$device_id]);
    $ids = array_column($stmt->fetchAll(), 'prompt_id');

    json_response(['action' => $action, 'prompt_id' => $prompt_id, 'favorites' => $ids]);
}

json_error('Method not allowed', 405);
