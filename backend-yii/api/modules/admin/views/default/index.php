<?php
/**
 * @var yii\web\View $this
 * @var array        $prompts
 * @var int          $allCount
 * @var array        $counts    prompt_id => copy_count
 * @var array        $cats      list of category strings
 * @var string       $q
 * @var string       $cat
 */
use yii\helpers\Html;
$this->title = 'Dashboard — GeminiMuse Admin';

$newCount     = count(array_filter($prompts, fn($p) => !empty($p['is_new'])));
$totalCopies  = array_sum($counts);
?>

<!-- Stats row -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem">
  <?php foreach ([
    ['Total Prompts', $allCount],
    ['Filtered',      count($prompts)],
    ['Categories',    count($cats)],
    ['Total Copies',  number_format($totalCopies)],
  ] as [$label, $val]): ?>
  <div class="card" style="text-align:center;padding:1rem">
    <div style="font-size:1.8rem;font-weight:800"><?= $val ?></div>
    <div style="color:#6b7280;font-size:.78rem;margin-top:.2rem"><?= $label ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="card" style="padding:1rem;margin-bottom:1.5rem">
  <form method="get" action="/admin" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
    <input type="text" name="q" value="<?= Html::encode($q) ?>" placeholder="Search prompt text or ID…"
           style="flex:1;min-width:200px;padding:.5rem .75rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:.875rem">
    <select name="cat" style="padding:.5rem .75rem;border:1px solid #d1d5db;border-radius:.5rem;font-size:.875rem">
      <option value="">All categories</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= Html::encode($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= Html::encode(ucfirst($c)) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
    <?php if ($q || $cat): ?>
      <a href="/admin" class="btn btn-ghost">Clear</a>
    <?php endif; ?>
  </form>
</div>

<!-- Prompts table -->
<div class="card">
  <h2>Prompts (<?= count($prompts) ?><?= ($q || $cat) ? " of $allCount" : '' ?>)</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Image</th>
          <th>Prompt</th>
          <th>Category</th>
          <th>Langs</th>
          <th>Copies</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($prompts as $p):
          $langFlags = [];
          foreach (['tn'=>'த','ml'=>'മ','te'=>'తె','kn'=>'ಕ','hi'=>'अ'] as $code => $glyph) {
            if (!empty($p["prompt_{$code}"])) $langFlags[] = "<span title='{$code}'>{$glyph}</span>";
          }
        ?>
        <tr>
          <td><strong>#<?= $p['id'] ?></strong></td>
          <td>
            <?php if (!empty($p['url'])): ?>
              <img src="/<?= Html::encode($p['url']) ?>"
                   onerror="this.style.display='none'"
                   alt="prompt <?= $p['id'] ?>"
                   style="width:48px;height:60px;object-fit:cover;border-radius:.35rem">
            <?php endif; ?>
          </td>
          <td style="max-width:340px;font-size:.8rem;color:#374151">
            <?= Html::encode(mb_substr($p['prompt'], 0, 100)) ?>…
          </td>
          <td>
            <?php if (!empty($p['category'])): ?>
              <span class="badge badge-cat"><?= Html::encode($p['category']) ?></span>
            <?php endif; ?>
            <?php if (!empty($p['is_new'])): ?>
              <span class="badge badge-new">New</span>
            <?php endif; ?>
          </td>
          <td style="font-size:.9rem;letter-spacing:.1em">
            <?= implode(' ', $langFlags) ?: '<span style="color:#d1d5db">—</span>' ?>
          </td>
          <td><?= number_format($counts[$p['id']] ?? 0) ?></td>
          <td>
            <a href="/admin/translate?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm">Translate</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
