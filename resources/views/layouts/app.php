<?php
/**
 * Main layout.
 *
 * @var string $content Rendered child template (already escaped HTML)
 * @var string|null $title Page title
 */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->e(isset($title) ? $title . ' · MiniForge' : 'MiniForge') ?></title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<header class="topbar">
    <div class="container topbar__inner">
        <a class="brand" href="/"><span class="brand__mark">⚒</span> MiniForge</a>
        <nav class="nav">
            <a href="/">Home</a>
        </nav>
    </div>
</header>
<main class="container">
<?= $content ?>
</main>
<footer class="footer container">
    MiniForge PHP — a mini framework built for learning.
</footer>
</body>
</html>
