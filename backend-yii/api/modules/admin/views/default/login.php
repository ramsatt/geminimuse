<?php
/** @var string $error */
use yii\helpers\Html;
$this->title = 'Login — GeminiMuse Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GeminiMuse Admin — Login</title>
  <link rel="stylesheet" href="/admin-assets/style.css">
</head>
<body class="login-page">
  <div class="login-box">
    <h1>GeminiMuse Admin</h1>
    <?php if ($error): ?>
      <p class="error"><?= Html::encode($error) ?></p>
    <?php endif; ?>
    <form method="post" action="/admin/default/login">
      <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
      <input type="password" name="password" placeholder="Admin password" autofocus required>
      <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>
