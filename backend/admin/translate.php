<?php
require_once __DIR__ . '/auth.php';

// ── Gemini API key ─────────────────────────────────────────────
// Store this in a file OUTSIDE web root, or set as env variable.
// Example: define('GEMINI_API_KEY', getenv('GEMINI_API_KEY'));
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY');

$json_path = __DIR__ . '/../../src/assets/data/prompts.json';
$prompts   = file_exists($json_path) ? json_decode(file_get_contents($json_path), true) : [];

// Index by ID for fast lookup
$by_id = [];
foreach ($prompts as $i => $p) $by_id[$p['id']] = $i;

$flash     = '';
$ftype     = '';
$selected  = null;
$translated = null;

// Pre-select prompt from ?id= query param
$preselect_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ── Handle translate (AJAX or form post) ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'translate') {
    header('Content-Type: application/json');

    $prompt_text = trim($_POST['prompt_text'] ?? '');
    if ($prompt_text === '') {
        echo json_encode(['error' => 'Prompt text is required']);
        exit;
    }

    if (GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY') {
        echo json_encode(['error' => 'Gemini API key not configured. Edit translate.php and set GEMINI_API_KEY.']);
        exit;
    }

    $result = call_gemini_translate($prompt_text);
    echo json_encode($result);
    exit;
}

// ── Handle save translations ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $prompt_id = (int)($_POST['prompt_id'] ?? 0);

    if (!isset($by_id[$prompt_id])) {
        $flash = 'Prompt not found.';
        $ftype = 'error';
    } else {
        $idx = $by_id[$prompt_id];
        foreach (['prompt_tn', 'prompt_hi', 'prompt_te', 'prompt_kn', 'prompt_ml'] as $field) {
            $val = trim($_POST[$field] ?? '');
            if ($val !== '') {
                $prompts[$idx][$field] = $val;
            }
        }

        $written = file_put_contents(
            $json_path,
            json_encode($prompts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        if ($written !== false) {
            $flash = "Translations saved for Prompt #$prompt_id.";
            $ftype = 'success';
            // Refresh index
            $by_id = [];
            foreach ($prompts as $i => $p) $by_id[$p['id']] = $i;
        } else {
            $flash = 'Failed to write prompts.json. Check file permissions.';
            $ftype = 'error';
        }
        $preselect_id = $prompt_id;
    }
}

// Load selected prompt for the form
if ($preselect_id && isset($by_id[$preselect_id])) {
    $selected = $prompts[$by_id[$preselect_id]];
}

// ── Gemini API call ────────────────────────────────────────────
function call_gemini_translate(string $prompt_text): array {
    $instruction = <<<PROMPT
You are an expert AI prompt translator. Translate the following AI image generation prompt into Tamil, Hindi, Telugu, Kannada, and Malayalam.

Rules:
1. Keep technical AI terms (like "cinematic", "bokeh", "8K", "hyper-realistic") in English.
2. Translate the descriptive parts naturally into each language.
3. Return ONLY a valid JSON object with these exact keys: "ta", "hi", "te", "kn", "ml".
4. No markdown, no explanation — just the raw JSON.

Prompt to translate:
{$prompt_text}
PROMPT;

    $body = json_encode([
        'contents' => [
            ['parts' => [['text' => $instruction]]]
        ],
        'generationConfig' => [
            'temperature'     => 0.2,
            'maxOutputTokens' => 2048,
        ],
    ]);

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . GEMINI_API_KEY;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $http_code !== 200) {
        return ['error' => "Gemini API error (HTTP $http_code). Check your API key."];
    }

    $data = json_decode($response, true);
    $raw  = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

    // Strip markdown code fences if present
    $raw = preg_replace('/^```json\s*/i', '', trim($raw));
    $raw = preg_replace('/\s*```$/',      '', $raw);

    $translations = json_decode(trim($raw), true);
    if (!is_array($translations) || !isset($translations['ta'])) {
        return ['error' => 'Gemini returned unexpected format.', 'raw' => $raw];
    }

    return ['translations' => $translations];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GeminiMuse Admin — Translate</title>
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

  <div class="card">
    <h2>Auto-Translate Prompt</h2>
    <p style="color:#6b7280;font-size:.85rem;margin-bottom:1.25rem">
      Select a prompt, click <strong>Auto-Translate with Gemini</strong>, review & edit the results, then save.
    </p>

    <!-- Prompt selector -->
    <form method="get" style="display:flex;gap:.75rem;align-items:center;margin-bottom:1.25rem">
      <select name="id" style="flex:1;padding:.55rem .75rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:.875rem">
        <option value="">— Select a prompt —</option>
        <?php foreach ($prompts as $p):
          $has_all = !empty($p['prompt_tn']) && !empty($p['prompt_hi']) && !empty($p['prompt_te']) && !empty($p['prompt_kn']) && !empty($p['prompt_ml']);
        ?>
          <option value="<?= $p['id'] ?>"
            <?= ($preselect_id === $p['id']) ? 'selected' : '' ?>>
            #<?= $p['id'] ?> — <?= htmlspecialchars(mb_substr($p['prompt'], 0, 60)) ?>…
            <?= $has_all ? '✓' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-ghost">Load</button>
    </form>

    <?php if ($selected): ?>
    <!-- Translation form -->
    <div style="background:#f8f9ff;border:1px solid #e0e7ff;border-radius:.5rem;padding:1rem;margin-bottom:1.25rem">
      <div style="font-size:.75rem;font-weight:700;color:#4f46e5;margin-bottom:.4rem">PROMPT #<?= $selected['id'] ?> — ENGLISH</div>
      <p style="font-size:.875rem;line-height:1.6" id="prompt-en-text"><?= htmlspecialchars($selected['prompt']) ?></p>
    </div>

    <button type="button" class="btn btn-primary" id="btn-translate" onclick="autoTranslate()">
      ✦ Auto-Translate with Gemini
    </button>
    <span id="translate-status" style="margin-left:.75rem;font-size:.85rem;color:#6b7280"></span>

    <form method="post" style="margin-top:1.25rem" id="save-form">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="prompt_id" value="<?= $selected['id'] ?>">

      <div class="form-grid">
        <?php
        $lang_fields = [
          ['prompt_tn', 'Tamil (தமிழ்)',      'ta'],
          ['prompt_hi', 'Hindi (हिन्दी)',       'hi'],
          ['prompt_te', 'Telugu (తెలుగు)',     'te'],
          ['prompt_kn', 'Kannada (ಕನ್ನಡ)',    'kn'],
          ['prompt_ml', 'Malayalam (മലയാളം)', 'ml'],
        ];
        foreach ($lang_fields as [$field, $label, $code]):
        ?>
        <div class="form-group">
          <label><?= $label ?></label>
          <textarea name="<?= $field ?>" id="field-<?= $code ?>" rows="4"><?= htmlspecialchars($selected[$field] ?? '') ?></textarea>
        </div>
        <?php endforeach; ?>
      </div>

      <div style="margin-top:1.25rem;display:flex;gap:.75rem">
        <button type="submit" class="btn btn-success">Save Translations</button>
        <a href="index.php" class="btn btn-ghost">Back to Dashboard</a>
      </div>
    </form>

    <script>
    async function autoTranslate() {
      const btn    = document.getElementById('btn-translate');
      const status = document.getElementById('translate-status');
      const text   = document.getElementById('prompt-en-text').textContent.trim();

      btn.disabled = true;
      btn.textContent = 'Translating…';
      status.textContent = '';

      try {
        const fd = new FormData();
        fd.append('action',      'translate');
        fd.append('prompt_text', text);

        const res  = await fetch('translate.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.error) {
          status.textContent = '✗ ' + data.error;
          status.style.color = '#ef4444';
        } else {
          const t = data.translations;
          const map = { ta: 'field-ta', hi: 'field-hi', te: 'field-te', kn: 'field-kn', ml: 'field-ml' };
          for (const [code, id] of Object.entries(map)) {
            if (t[code]) document.getElementById(id).value = t[code];
          }
          status.textContent = '✓ Done — review & save';
          status.style.color = '#22c55e';
        }
      } catch (e) {
        status.textContent = '✗ Network error';
        status.style.color = '#ef4444';
      } finally {
        btn.disabled = false;
        btn.textContent = '✦ Auto-Translate with Gemini';
      }
    }
    </script>
    <?php endif; ?>
  </div>

  <!-- Batch translate: prompts missing translations -->
  <?php
  $missing = array_filter($prompts, fn($p) =>
    empty($p['prompt_tn']) || empty($p['prompt_hi']) ||
    empty($p['prompt_te']) || empty($p['prompt_kn']) || empty($p['prompt_ml'])
  );
  $missing = array_values($missing);
  if (count($missing) > 0):
  ?>
  <div class="card">
    <h2>Prompts Missing Translations (<?= count($missing) ?>)</h2>
    <p style="color:#6b7280;font-size:.85rem;margin-bottom:.75rem">
      These prompts have one or more missing Indian-language versions.
    </p>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>ID</th><th>Prompt</th><th>Missing</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($missing, 0, 30) as $p):
            $miss_langs = [];
            foreach (['tn'=>'Tamil','hi'=>'Hindi','te'=>'Telugu','kn'=>'Kannada','ml'=>'Malayalam'] as $code => $name) {
              if (empty($p["prompt_{$code}"])) $miss_langs[] = $name;
            }
          ?>
          <tr>
            <td>#<?= $p['id'] ?></td>
            <td style="max-width:300px;font-size:.8rem;color:#374151">
              <?= htmlspecialchars(mb_substr($p['prompt'], 0, 80)) ?>…
            </td>
            <td style="font-size:.78rem;color:#ef4444"><?= implode(', ', $miss_langs) ?></td>
            <td>
              <a href="translate.php?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm">Translate</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>
</body>
</html>
