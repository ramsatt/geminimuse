<?php
require_once __DIR__ . '/auth.php';

$json_path = __DIR__ . '/../../src/assets/data/prompts.json';
$prompts   = file_exists($json_path) ? json_decode(file_get_contents($json_path), true) : [];

$flash = '';
$ftype = '';

// ── Download as file ───────────────────────────────────────────
if (isset($_GET['download'])) {
    $json = json_encode($prompts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="prompts-' . date('Y-m-d') . '.json"');
    header('Content-Length: ' . strlen($json));
    echo $json;
    exit;
}

// ── Stats ──────────────────────────────────────────────────────
$total     = count($prompts);
$with_all  = 0;
$with_some = 0;
$no_trans  = 0;
$cats      = [];

foreach ($prompts as $p) {
    $has = array_filter(['prompt_tn','prompt_hi','prompt_te','prompt_kn','prompt_ml'],
        fn($f) => !empty($p[$f])
    );
    $n = count($has);
    if ($n === 5)        $with_all++;
    elseif ($n > 0)      $with_some++;
    else                 $no_trans++;

    if (!empty($p['category'])) $cats[$p['category']] = ($cats[$p['category']] ?? 0) + 1;
}

$file_size = file_exists($json_path) ? round(filesize($json_path) / 1024, 1) : 0;
$last_mod  = file_exists($json_path) ? date('Y-m-d H:i', filemtime($json_path)) : 'unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GeminiMuse Admin — Export</title>
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

  <?php if ($flash): ?>
    <div class="flash flash-<?= $ftype ?>"><?= $flash ?></div>
  <?php endif; ?>

  <!-- File info -->
  <div class="card">
    <h2>prompts.json — Current State</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.25rem">
      <?php
      foreach ([
        ['Total Prompts', $total],
        ['Fully Translated (5 langs)', $with_all],
        ['Partially Translated', $with_some],
        ['English Only', $no_trans],
        ['File Size', $file_size . ' KB'],
        ['Last Modified', $last_mod],
      ] as [$label, $val]):
      ?>
      <div style="text-align:center;padding:1rem;background:var(--light);border-radius:.5rem">
        <div style="font-size:1.3rem;font-weight:800"><?= $val ?></div>
        <div style="color:#6b7280;font-size:.72rem;margin-top:.2rem"><?= $label ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Translation coverage bar -->
    <div style="margin-bottom:1.25rem">
      <div style="display:flex;justify-content:space-between;font-size:.78rem;color:#6b7280;margin-bottom:.3rem">
        <span>Translation coverage</span>
        <span><?= $total > 0 ? round($with_all / $total * 100) : 0 ?>% fully translated</span>
      </div>
      <div style="height:8px;background:#e5e7eb;border-radius:99px;overflow:hidden">
        <?php
        $all_pct  = $total > 0 ? ($with_all  / $total * 100) : 0;
        $some_pct = $total > 0 ? ($with_some / $total * 100) : 0;
        ?>
        <div style="height:100%;width:<?= $all_pct + $some_pct ?>%;background:linear-gradient(90deg,#22c55e,#86efac);border-radius:99px;position:relative">
          <div style="position:absolute;left:0;top:0;height:100%;width:<?= $total > 0 ? ($all_pct / ($all_pct + $some_pct + .01) * 100) : 0 ?>%;background:#16a34a;border-radius:99px"></div>
        </div>
      </div>
      <div style="display:flex;gap:1rem;margin-top:.4rem;font-size:.72rem;color:#6b7280">
        <span><span style="display:inline-block;width:10px;height:10px;background:#16a34a;border-radius:50%;margin-right:.25rem"></span>All 5 languages</span>
        <span><span style="display:inline-block;width:10px;height:10px;background:#86efac;border-radius:50%;margin-right:.25rem"></span>Partial</span>
        <span><span style="display:inline-block;width:10px;height:10px;background:#e5e7eb;border-radius:50%;margin-right:.25rem"></span>English only</span>
      </div>
    </div>

    <!-- Category breakdown -->
    <?php if ($cats): arsort($cats); ?>
    <div style="margin-bottom:1.25rem">
      <div style="font-size:.78rem;font-weight:700;color:#6b7280;margin-bottom:.5rem">BY CATEGORY</div>
      <div style="display:flex;flex-wrap:wrap;gap:.4rem">
        <?php foreach ($cats as $cat => $count): ?>
          <span style="background:#ede9fe;color:#5b21b6;padding:.2rem .65rem;border-radius:99px;font-size:.75rem;font-weight:600">
            <?= htmlspecialchars($cat) ?> (<?= $count ?>)
          </span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:.75rem;flex-wrap:wrap">
      <a href="export.php?download=1" class="btn btn-success">
        ↓ Download prompts.json
      </a>
      <a href="translate.php" class="btn btn-primary">
        Go to Translate (<?= $no_trans + $with_some ?> need work)
      </a>
    </div>
  </div>

  <!-- JSON preview -->
  <div class="card">
    <h2>Preview (first 3 prompts)</h2>
    <pre style="background:var(--light);padding:1rem;border-radius:.5rem;overflow:auto;font-size:.75rem;line-height:1.5;max-height:400px"><?=
      htmlspecialchars(json_encode(array_slice($prompts, 0, 3), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
    ?></pre>
  </div>

</div>
</body>
</html>
