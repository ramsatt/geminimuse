<?php
// ============================================================
// GeminiMuse — Prompt Stats API
//
// GET ?ids=1,2,3,4         → {1: 42, 2: 7, 3: 0, 4: 18}
// GET ?id=42               → {prompt_id: 42, copy_count: 7}
// ============================================================

require_once __DIR__ . '/cors.php';

handle_cors();

$db     = get_db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    json_error('Method not allowed', 405);
}

// ── Single prompt stat ────────────────────────────────────────
if (isset($_GET['id'])) {
    $prompt_id = (int) $_GET['id'];
    if ($prompt_id <= 0) json_error('Invalid id');

    $stmt = $db->prepare('SELECT copy_count FROM prompt_stats WHERE prompt_id = ?');
    $stmt->execute([$prompt_id]);
    $row = $stmt->fetch();

    json_response([
        'prompt_id'  => $prompt_id,
        'copy_count' => $row ? (int) $row['copy_count'] : 0,
    ]);
}

// ── Bulk stats ────────────────────────────────────────────────
if (isset($_GET['ids'])) {
    // Parse and sanitize IDs — only allow integers
    $raw_ids = explode(',', $_GET['ids']);
    $ids = array_filter(array_map('intval', $raw_ids), fn($id) => $id > 0);
    $ids = array_slice(array_unique($ids), 0, 200); // cap at 200

    if (empty($ids)) {
        json_response([]);
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare(
        "SELECT prompt_id, copy_count FROM prompt_stats WHERE prompt_id IN ($placeholders)"
    );
    $stmt->execute(array_values($ids));
    $rows = $stmt->fetchAll();

    // Build map: prompt_id => copy_count (default 0 for prompts with no copies)
    $map = array_fill_keys($ids, 0);
    foreach ($rows as $row) {
        $map[(int) $row['prompt_id']] = (int) $row['copy_count'];
    }

    json_response($map);
}

json_error('Provide ?id= or ?ids=');
