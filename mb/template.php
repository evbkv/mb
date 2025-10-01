<?php

require_once __DIR__ . '/settings.php';

session_start();
$isLoggedIn = !empty($_SESSION['logged_in']);

$title = isset($post['title']) ? $post['title'] : ($post['h1'] ?? 'Untitled');
$description = $post['description'] ?? '';
$h1 = $post['h1'] ?? $title;
$content = $post['content'] ?? '';
$author = $AUTHOR_NAME ?? '';
$datePublished = $post['datePublished'] ?? date('c');

$script = basename($_SERVER['SCRIPT_NAME']);
$slug = preg_replace('/\.php$/', '', $script);

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($basePath === '/' || $basePath === '\\') $basePath = '';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$url = $scheme . '://' . $host . $basePath . '/' . $slug . '/';

$img_dir = realpath(__DIR__ . '/../img');
$firstImageUrl = '';
$imgs = [];
if ($img_dir !== false) {
    $pattern = $img_dir . DIRECTORY_SEPARATOR . $slug . '-*.{jpg,jpeg,png,gif,webp}';
    $imgs = glob($pattern, GLOB_BRACE);
    if ($imgs && count($imgs) > 0) {
        $first = basename($imgs[0]);
        $firstImageUrl = $scheme . '://' . $host . $basePath . '/img/' . $first;
    }
}
if (!$firstImageUrl) {
    $firstImageUrl = $scheme . '://' . $host . $basePath . '/img/default.jpg';
}

function h($s){ return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?><!doctype html>
<html lang="<?= $lang[0]; ?>">
<head>
<title><?= h($title) ?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="<?= h($description) ?>">
<meta name="robots" content="index,follow">
<link rel="icon" type="image/svg+xml" href="<?= h($basePath) ?>/img/favicon.svg">
<link rel="canonical" href="<?= h($url) ?>">
<link rel="alternate" hreflang="<?= $lang[0]; ?>" href="<?= h($url) ?>">
<meta property="og:site_name" content="<?= h($title) ?>">
<meta property="og:locale" content="<?= $lang[1]; ?>">
<meta property="article:published_time" content="<?= date('c', strtotime($datePublished)) ?>">
<meta property="og:title" content="<?= h($title) ?>">
<meta property="og:description" content="<?= h($description) ?>">
<meta property="og:image" content="<?= h($firstImageUrl) ?>">
<meta property="og:url" content="<?= h($url) ?>">
<meta property="og:type" content="article">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($title) ?>">
<meta name="twitter:description" content="<?= h($description) ?>">
<meta name="twitter:image" content="<?= h($firstImageUrl) ?>">
<link rel="icon" type="image/svg+xml" href="<?= h($basePath) ?>/img/favicon.svg">
<link rel="stylesheet" href="<?= h($basePath) ?>/mb/style.css?<?= rand(); ?>">
</head>
<body>
<header>
<?php include './mb/header.php'; ?>
</header>
<main>

<p class="mb-breadcrumb" aria-label="Breadcrumb">
    <?php if (isset($homePage) && is_array($homePage) && count($homePage) >= 2): ?>
        <a href="<?= h($homePage[1]) ?>"><?= h($homePage[0]) ?></a> / 
    <?php endif; ?>
    <a href="<?= h($basePath) ?>/"><?= h($blogTitle) ?></a> / 
    <span><?= h($h1) ?></span>
</p>

<article itemscope itemtype="http://schema.org/Article">

<h1 itemprop="headline"><?= h($h1) ?></h1>

<?php if ($isLoggedIn): ?>
<div class="mb-editor"><a href="<?= h($basePath) ?>/editor/?selected_file=<?= h($slug) ?>.php">Edit post</a></div>
<?php endif; ?>

<?php if (!empty($imgs)): ?>
    <?php $firstImg = reset($imgs); ?>
    <?php if ($firstImg): ?>
        <figure itemprop="image" itemscope itemtype="http://schema.org/ImageObject">
            <img src="<?= h($basePath) ?>/img/<?= h(basename($firstImg)) ?>" alt="<?= h($title) ?>">
            <figcaption itemprop="caption"><?= h($title) ?></figcaption>
        </figure>
    <?php endif; ?>
<?php endif; ?>

<?php
echo $content;
?>

<?php if (!empty($imgs)): ?>
    <?php unset($imgs[key($imgs)]); ?>
    <?php if (!empty($imgs)): ?>
        <?php foreach ($imgs as $img): ?>
            <figure itemprop="image" itemscope itemtype="http://schema.org/ImageObject">
                <img src="<?= h($basePath) ?>/img/<?= h(basename($img)) ?>" alt="<?= h($title) ?>">
                <figcaption itemprop="caption"><?= h($title) ?></figcaption>
            </figure>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>

<p class="mb-author-date"><span itemprop="author" itemscope itemtype="http://schema.org/Person"><?= h($author) ?></span> — <time itemprop="datePublished" datetime="<?= h($datePublished) ?>"><?= h(date('d.m.Y', strtotime($datePublished))) ?></time></p>

</article>

</main>
<footer>
<?php include './mb/footer.php'; ?>
</footer>
<script type="application/ld+json">
<?= json_encode([
    "@context" => "https://schema.org",
    "@graph" => [
        [
            "@type" => "Article",
            "headline" => $title,
            "description" => $description,
            "author" => [
                "@type" => "Person",
                "name" => $author
            ],
            "datePublished" => $datePublished,
            "image" => is_array($firstImageUrl) ? array_values($firstImageUrl) : [$firstImageUrl],
            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id" => $url
            ],
            "publisher" => [
                "@type" => "Organization",
                "name" => $host,
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => $scheme . '://' . $host . ($basePath ? $basePath : '') . '/img/favicon.svg'
                ]
            ]
        ],
        [
            "@type" => "BreadcrumbList",
            "itemListElement" => [
                [
                    "@type" => "ListItem",
                    "position" => 1,
                    "name" => $blogTitle,
                    "item" => $scheme . '://' . $host . ($basePath ? $basePath : '') . '/'
                ],
                [
                    "@type" => "ListItem",
                    "position" => 2,
                    "name" => $title,
                    "item" => $url
                ]
            ]
        ]
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
</body>
</html>
