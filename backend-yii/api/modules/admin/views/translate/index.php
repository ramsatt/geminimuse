<?php
/**
 * @var yii\web\View $this
 * @var array        $prompts
 * @var array|null   $selected
 * @var array        $missing
 * @var string       $flash
 * @var string       $flashType
 */
use yii\helpers\Html;
$this->title = 'Translate — GeminiMuse Admin';
$preselect   = $selected['id'] ?? 0;
?>

<?php if ($flash): ?>
  <div class="flash flash-<?= $flashType ?>"><?= $flash ?></div>
<?php endif; ?>

<div class="card">
  <h2>Auto-Translate Prompt</h2>
  <p style="color:#6b7280;font-size:.85rem;margin-bottom:1.25rem">
    Select a prompt, click <strong>Auto-Translate with Gemini</strong>, review, then save.
  </p>

  <!-- Prompt selector -->
  <form method="get" action="/admin/translate" style="display:flex;gap:.75rem;margin-bottom:1.25rem">
    <select name="id" style="flex:1;padding:.55rem .75rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:.875rem">
      <option value="">— Select a prompt —</option>
      <?php foreach ($prompts as $p):
        $hasAll = !empty($p['prompt_tn']) && !empty($p['prompt_hi']) &&
                  !empty($p['prompt_te']) && !empty($p['prompt_kn']) && !empty($p['prompt_ml']);
      ?>
        <option value="<?= $p['id'] ?>" <?= $preselect === $p['id'] ? 'selected' : '' ?>>
          #<?= $p['id'] ?> — <?= Html::encode(mb_substr($p['prompt'], 0, 60)) ?>… <?= $hasAll ? '✓' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-ghost">Load</button>
  </form>

  <?php if ($selected): ?>
  <!-- English source -->
  <div style="background:#f8f9ff;border:1px solid #e0e7ff;border-radius:.5rem;padding:1rem;margin-bottom:1.25rem">
    <div style="font-size:.75rem;font-weight:700;color:#4f46e5;margin-bottom:.4rem">PROMPT #<?= $selected['id'] ?> — ENGLISH</div>
    <p id="prompt-en-text" style="font-size:.875rem;line-height:1.6"><?= Html::encode($selected['prompt']) ?></p>
  </div>

  <button type="button" class="btn btn-primary" id="btn-translate" onclick="autoTranslate()">
    ✦ Auto-Translate with Gemini
  </button>
  <span id="translate-status" style="margin-left:.75rem;font-size:.85rem;color:#6b7280"></span>

  <form method="post" action="/admin/translate" style="margin-top:1.25rem">
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="prompt_id" value="<?= $selected['id'] ?>">

    <div class="form-grid">
      <?php foreach ([
        ['prompt_tn', 'Tamil (தமிழ்)',      'ta'],
        ['prompt_hi', 'Hindi (हिन्दी)',       'hi'],
        ['prompt_te', 'Telugu (తెలుగు)',     'te'],
        ['prompt_kn', 'Kannada (ಕನ್ನಡ)',    'kn'],
        ['prompt_ml', 'Malayalam (മലയാളം)', 'ml'],
      ] as [$field, $label, $code]): ?>
      <div class="form-group">
        <label><?= $label ?></label>
        <textarea name="<?= $field ?>" id="field-<?= $code ?>" rows="4"><?= Html::encode($selected[$field] ?? '') ?></textarea>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="margin-top:1.25rem;display:flex;gap:.75rem">
      <button type="submit" class="btn btn-success">Save Translations</button>
      <a href="/admin" class="btn btn-ghost">Back</a>
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
      fd.append('prompt_text', text);
      fd.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

      const res  = await fetch('/admin/translate/auto', { method: 'POST', body: fd });
      const data = await res.json();

      if (data.error) {
        status.textContent = '✗ ' + data.error;
        status.style.color = '#ef4444';
      } else {
        const t   = data.translations;
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

<!-- Missing translations table -->
<?php if (count($missing) > 0): ?>
<div class="card">
  <h2>Missing Translations (<?= count($missing) ?>)</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>ID</th><th>Prompt</th><th>Missing</th><th></th></tr></thead>
      <tbody>
        <?php foreach (array_slice($missing, 0, 30) as $p):
          $miss = [];
          foreach (['tn'=>'Tamil','hi'=>'Hindi','te'=>'Telugu','kn'=>'Kannada','ml'=>'Malayalam'] as $c => $n) {
            if (empty($p["prompt_{$c}"])) $miss[] = $n;
          }
        ?>
        <tr>
          <td>#<?= $p['id'] ?></td>
          <td style="max-width:300px;font-size:.8rem"><?= Html::encode(mb_substr($p['prompt'], 0, 80)) ?>…</td>
          <td style="font-size:.78rem;color:#ef4444"><?= implode(', ', $miss) ?></td>
          <td><a href="/admin/translate?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm">Translate</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
