<?php
/** @var string $content @var array<string,string> $meta */
$meta = $meta ?? [];
$title = $meta['title'] ?? 'Daylight — Software company in Nairobi, Kenya';
$desc = $meta['description'] ?? 'Daylight builds custom business software, web and mobile applications, and SaaS products for organizations across Kenya.';
$ogTitle = $meta['og_title'] ?? $title;
$ogDesc = $meta['og_description'] ?? $desc;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($desc) ?>">
  <meta name="author" content="Daylight">
  <meta property="og:title" content="<?= e($ogTitle) ?>">
  <meta property="og:description" content="<?= e($ogDesc) ?>">
  <meta property="og:type" content="website">
  <meta name="twitter:card" content="summary_large_image">
<?php if (!empty($meta['noindex'])): ?>
  <meta name="robots" content="noindex, nofollow">
<?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="icon" href="<?= e(url('/favicon.ico')) ?>" type="image/x-icon">
  <script src="<?= e(asset('js/theme-init.js')) ?>"></script>
</head>
<body>
  <div class="page">
    <?= partial('partials/header') ?>
    <main>
      <?= $content ?>
    </main>
    <?= partial('partials/footer') ?>
  </div>
  <?= partial('partials/assistant') ?>
  <script src="<?= e(asset('js/app.js')) ?>" defer></script>
  <script src="<?= e(asset('js/assistant.js')) ?>" defer></script>
</body>
</html>
