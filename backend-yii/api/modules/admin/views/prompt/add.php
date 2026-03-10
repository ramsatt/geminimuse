<?php
/**
 * @var yii\web\View $this
 * @var string       $flash
 * @var string       $flashType
 * @var array        $categories
 * @var array        $post
 */
use yii\helpers\Html;
$this->title = 'Add Prompt — GeminiMuse Admin';
?>

<?php if ($flash): ?>
  <div class="flash flash-<?= $flashType ?>"><?= $flash ?></div>
<?php endif; ?>

<div class="card">
  <h2>Add New Prompt</h2>
  <p style="color:#6b7280;font-size:.85rem;margin-bottom:1.25rem">
    After saving, use <a href="/admin/translate">Translate</a> to auto-generate Indian-language versions.
  </p>

  <form method="post" action="/admin/prompt/add">
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

    <div class="form-grid">

      <div class="form-group full">
        <label>English Prompt *</label>
        <textarea name="prompt" rows="4" required
          placeholder="Describe the AI image in English..."><?= Html::encode($post['prompt'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>Image URL *</label>
        <input type="text" name="image_url" required
          value="<?= Html::encode($post['image_url'] ?? '') ?>"
          placeholder="assets/gemini/filename.jpg">
      </div>

      <div class="form-group">
        <label>Filename (auto from URL if blank)</label>
        <input type="text" name="filename"
          value="<?= Html::encode($post['filename'] ?? '') ?>"
          placeholder="filename.jpg">
      </div>

      <div class="form-group">
        <label>Category</label>
        <select name="category">
          <option value="">— none —</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c ?>" <?= ($post['category'] ?? '') === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group" style="justify-content:flex-end;padding-bottom:.25rem">
        <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;margin-top:auto">
          <input type="checkbox" name="is_new" <?= !empty($post['is_new']) ? 'checked' : '' ?>>
          Mark as "New This Week"
        </label>
      </div>

      <div class="form-group full" style="margin-top:.5rem">
        <label style="color:#4f46e5;font-weight:700">Indian Language Translations
          <span style="font-weight:400;color:#9ca3af">(optional — can translate later)</span>
        </label>
      </div>

      <?php foreach ([
        ['prompt_tn', 'Tamil (தமிழ்)'],
        ['prompt_hi', 'Hindi (हिन्दी)'],
        ['prompt_te', 'Telugu (తెలుగు)'],
        ['prompt_kn', 'Kannada (ಕನ್ನಡ)'],
        ['prompt_ml', 'Malayalam (മലയാളം)'],
      ] as [$name, $label]): ?>
      <div class="form-group">
        <label><?= $label ?></label>
        <textarea name="<?= $name ?>" rows="3"><?= Html::encode($post[$name] ?? '') ?></textarea>
      </div>
      <?php endforeach; ?>

    </div>

    <div style="margin-top:1.25rem;display:flex;gap:.75rem">
      <button type="submit" class="btn btn-primary">Save Prompt</button>
      <a href="/admin" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>
