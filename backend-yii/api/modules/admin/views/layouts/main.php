<?php
/** @var yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= Html::encode($this->title ?? 'GeminiMuse Admin') ?></title>
  <link rel="stylesheet" href="/admin-assets/style.css">
</head>
<body>

<div class="topbar">
  <h1>GeminiMuse Admin</h1>
  <nav>
    <a href="/admin">Dashboard</a>
    <a href="/admin/prompt/add">+ Add Prompt</a>
    <a href="/admin/translate">Translate</a>
    <a href="/admin/default/logout">Logout</a>
  </nav>
</div>

<div class="main">
  <?= $content ?>
</div>

</body>
</html>
