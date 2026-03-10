<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config.php';

// ── Load prompts from JSON (source of truth) ───────────────────
$json_path = __DIR__ . '/../../src/assets/data/prompts.json';
$prompts   = file_exists($json_path) ? json_decode(file_get_contents($json_path), true) : [];

// ── Pull copy counts from DB (if available) ────────────────────
$counts = [];
try {
    $pdo = get_db();
    $rows = $pdo->query('SELECT prompt_id, copy_count FROM prompt_stats')->fetchAll();
    foreach ($rows as $r) $counts[$r['prompt_id']] = $r['copy_count'];
} catch (Exception $e) {
    // DB unavailable — counts just show 0
}

$total     = count($prompts);
$new_count = count(array_filter($prompts, fn($p) => !empty($p['is_new'])));
$cats      = array_unique(array_filter(array_column($prompts, 'category')));
sort($cats);

// ── Simple search ──────────────────────────────────────────────
$q      = trim($_GET['q'] ?? '');
$cat    = $_GET['cat'] ?? '';
$filter = $prompts;

if ($q !== '') {
    $filter = array_filter($filter, fn($p) =>
        stripos($p['prompt'], $q) !== false ||
        stripos((string)$p['id'],  $q) !== false
    );
}
if ($cat !== '') {
    $filter = array_filter($filter, fn($p) => ($p['category'] ?? '') === $cat);
}
$filter = array_values($filter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GeminiMuse Admin — Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topbar">
  <h1>GeminiMuse Admin</h1>
  <nav>
    <a href="index.php">Dashboard</a>
    <a href="add-prompt.php">+ Add Prompt</a>
    <a href="translate.php">Translate</a>
    <a href="export.php">Export JSON</a>
    <a href="index.php?logout=1" class="logout">Logout</a>
  </nav>
</div>

<div class="main">

  <!-- Stats row -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem">
    <?php
    $db_total_copies = array_sum($counts);
    foreach ([
      ['Total Prompts', $total],
      ['Categories',    count($cats)],
      ['New This Week', $new_count],
      ['Total Copies',  $db_total_copies],
    ] as [$label, $val]):
    ?>
    <div class="card" style="text-align:center;padding:1rem">
      <div style="font-size:1.8rem;font-weight:800"><?= number_format($val) ?></div>
      <div style="color:#6b7280;font-size:.78rem;margin-top:.2rem"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Filter bar -->
  <div class="card" style="padding:1rem">
    <form method="get" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
             placeholder="Search prompt text or ID…"
             style="flex:1;min-width:200px;padding:.5rem .75rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:.875rem">
      <select name="cat" style="padding:.5rem .75rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:.875rem">
        <option value="">All categories</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>" <?= $cat === $c ? 'selected' : '' ?>>
            <?= htmlspecialchars(ucfirst($c)) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary">Filter</button>
      <?php if ($q || $cat): ?>
        <a href="index.php" class="btn btn-ghost">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Prompts table -->
  <div class="card">
    <h2>Prompts (<?= count($filter) ?><?= ($q || $cat) ? ' of '.$total : '' ?>)</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Prompt (English)</th>
            <th>Category</th>
            <th>Langs</th>
            <th>Copies</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($filter as $p):
            $lang_flags = [];
            foreach (['tn'=>'த','ml'=>'മ','te'=>'తె','kn'=>'ಕ','hi'=>'अ'] as $code => $glyph) {
              if (!empty($p["prompt_{$code}"])) $lang_flags[] = "<span title='{$code}'>{$glyph}</span>";
            }
          ?>
          <tr>
            <td><strong>#<?= $p['id'] ?></strong></td>
            <td>
              <?php if (!empty($p['url'])): ?>
                <img src="../../<?= htmlspecialchars($p['url']) ?>"
                     onerror="this.style.display='none'"
                     alt="prompt <?= $p['id'] ?>">
              <?php endif; ?>
            </td>
            <td style="max-width:340px">
              <div style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;font-size:.8rem;color:#374151">
                <?= htmlspecialchars($p['prompt']) ?>
              </div>
            </td>
            <td>
              <?php if (!empty($p['category'])): ?>
                <span class="badge badge-cat"><?= htmlspecialchars($p['category']) ?></span>
              <?php endif; ?>
              <?php if (!empty($p['is_new'])): ?>
                <span class="badge badge-new">New</span>
              <?php endif; ?>
            </td>
            <td style="font-size:.9rem;letter-spacing:.1em">
              <?= implode(' ', $lang_flags) ?: '<span style="color:#d1d5db">—</span>' ?>
            </td>
            <td><?= number_format($counts[$p['id']] ?? 0) ?></td>
            <td>
              <a href="translate.php?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm">Translate</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</body>
</html>
