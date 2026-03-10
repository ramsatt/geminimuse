<?php
require_once __DIR__ . '/auth.php';

$json_path = __DIR__ . '/../../src/assets/data/prompts.json';
$prompts   = file_exists($json_path) ? json_decode(file_get_contents($json_path), true) : [];

$flash  = '';
$ftype  = '';

// ── Handle form submit ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prompt_en = trim($_POST['prompt']    ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $filename  = trim($_POST['filename']  ?? '');
    $category  = trim($_POST['category']  ?? '');
    $is_new    = !empty($_POST['is_new']);

    if ($prompt_en === '' || $image_url === '') {
        $flash = 'Prompt text and image URL are required.';
        $ftype = 'error';
    } else {
        // Auto-generate filename from URL if not set
        if ($filename === '') {
            $filename = basename(parse_url($image_url, PHP_URL_PATH));
        }

        // Next ID
        $max_id = count($prompts) > 0 ? max(array_column($prompts, 'id')) : 0;
        $new_id = $max_id + 1;

        $new_prompt = [
            'id'        => $new_id,
            'filename'  => $filename,
            'url'       => $image_url,
            'prompt'    => $prompt_en,
        ];

        if (!empty($_POST['prompt_tn'])) $new_prompt['prompt_tn'] = trim($_POST['prompt_tn']);
        if (!empty($_POST['prompt_hi'])) $new_prompt['prompt_hi'] = trim($_POST['prompt_hi']);
        if (!empty($_POST['prompt_te'])) $new_prompt['prompt_te'] = trim($_POST['prompt_te']);
        if (!empty($_POST['prompt_kn'])) $new_prompt['prompt_kn'] = trim($_POST['prompt_kn']);
        if (!empty($_POST['prompt_ml'])) $new_prompt['prompt_ml'] = trim($_POST['prompt_ml']);
        if ($category !== '')           $new_prompt['category']   = $category;
        if ($is_new)                    $new_prompt['is_new']     = true;

        $prompts[] = $new_prompt;

        $written = file_put_contents(
            $json_path,
            json_encode($prompts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        if ($written !== false) {
            $flash  = "Prompt #$new_id added successfully! <a href='translate.php?id=$new_id'>Auto-translate it now →</a>";
            $ftype  = 'success';
        } else {
            $flash = 'Failed to write prompts.json. Check file permissions.';
            $ftype = 'error';
        }
    }
}

$categories = [
  'portrait','cinematic','fantasy','anime','street',
  'nature','sci-fi','architecture','food','wildlife',
  'abstract','festive','fashion','macro',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GeminiMuse Admin — Add Prompt</title>
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
    <h2>Add New Prompt</h2>
    <p style="color:#6b7280;font-size:.85rem;margin-bottom:1.25rem">
      After saving, use the <a href="translate.php">Translate</a> tool to auto-generate Indian-language versions via Gemini API.
    </p>

    <form method="post">
      <div class="form-grid">

        <div class="form-group full">
          <label>English Prompt *</label>
          <textarea name="prompt" rows="4" required
            placeholder="Describe the AI image in English..."><?= htmlspecialchars($_POST['prompt'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label>Image URL *</label>
          <input type="text" name="image_url" required
            value="<?= htmlspecialchars($_POST['image_url'] ?? '') ?>"
            placeholder="assets/gemini/filename.jpg or full https://… URL">
        </div>

        <div class="form-group">
          <label>Filename (auto-detected if blank)</label>
          <input type="text" name="filename"
            value="<?= htmlspecialchars($_POST['filename'] ?? '') ?>"
            placeholder="filename.jpg">
        </div>

        <div class="form-group">
          <label>Category</label>
          <select name="category">
            <option value="">— none —</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c ?>" <?= ($_POST['category'] ?? '') === $c ? 'selected' : '' ?>>
                <?= ucfirst($c) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" style="justify-content:flex-end;padding-bottom:.25rem">
          <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
            <input type="checkbox" name="is_new" <?= !empty($_POST['is_new']) ? 'checked' : '' ?>>
            Mark as "New This Week"
          </label>
        </div>

        <!-- Indian language fields (optional — can auto-translate later) -->
        <div class="form-group full" style="margin-top:.5rem">
          <label style="color:#4f46e5;font-weight:700">Indian Language Translations
            <span style="font-weight:400;color:#9ca3af">(optional — leave blank, translate later)</span>
          </label>
        </div>

        <?php
        $langs = [
          ['prompt_tn', 'Tamil (தமிழ்)',      'அழகான AI படத்திற்கான தமிழ் விளக்கம்...'],
          ['prompt_hi', 'Hindi (हिन्दी)',       'AI छवि का हिंदी विवरण...'],
          ['prompt_te', 'Telugu (తెలుగు)',     'AI చిత్రం యొక్క తెలుగు వివరణ...'],
          ['prompt_kn', 'Kannada (ಕನ್ನಡ)',    'AI ಚಿತ್ರದ ಕನ್ನಡ ವಿವರಣೆ...'],
          ['prompt_ml', 'Malayalam (മലയാളം)', 'AI ചിത്രത്തിന്റെ മലയാളം വിവരണം...'],
        ];
        foreach ($langs as [$name, $label, $ph]):
        ?>
        <div class="form-group">
          <label><?= $label ?></label>
          <textarea name="<?= $name ?>" rows="3"
            placeholder="<?= $ph ?>"><?= htmlspecialchars($_POST[$name] ?? '') ?></textarea>
        </div>
        <?php endforeach; ?>

      </div><!-- /form-grid -->

      <div style="margin-top:1.25rem;display:flex;gap:.75rem">
        <button type="submit" class="btn btn-primary">Save Prompt</button>
        <a href="index.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>

</div>
</body>
</html>
