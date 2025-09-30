<?php

require_once __DIR__ . '/mb/settings.php';

session_start();
$isLoggedIn = !empty($_SESSION['logged_in']);

function extract_post_from_file($filepath) {
    $s = @file_get_contents($filepath);
    if ($s === false) return null;
    if (preg_match("/\\\$post\s*=\s*(.+?);\\s*include/s", $s, $m)) {
        $code = 'return ' . $m[1] . ';';
        try { $arr = eval($code); if(is_array($arr)) return $arr; } catch(Throwable $e){}
    }
    if (preg_match("/json_decode\(\s*([\"'])(.*)\\1\s*\)/sU", $s, $m)) {
        $json = stripcslashes($m[2]);
        $arr = json_decode($json,true);
        if (json_last_error()===JSON_ERROR_NONE) return $arr;
    }
    return null;
}

$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = rtrim(dirname($scriptName), '/\\');
if ($basePath==='/' || $basePath==='\\') $basePath='';

$root = __DIR__;
$posts = [];

foreach (glob($root.'/*.php') as $fullpath) {
    $bn = basename($fullpath);
    if ($bn === 'index.php') continue;
    $meta = extract_post_from_file($fullpath);
    if (!is_array($meta)) $meta = [];
    $meta['slug'] = basename($fullpath,'.php');
    $meta['file'] = $bn;
    $meta['_time'] = isset($meta['datePublished']) ? strtotime($meta['datePublished']) : filemtime($fullpath);
    $posts[] = $meta;
}

usort($posts, fn($a,$b)=>($b['_time']??0) <=> ($a['_time']??0));

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($basePath === '/' || $basePath === '\\') $basePath = '';
function h($s){ return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalPages = ceil(count($posts) / $indexPosts);
$currentPosts = array_slice($posts, ($currentPage - 1) * $indexPosts, $indexPosts);

?><!doctype html>
<html lang="<?= $lang[0]; ?>">
<head>
<title><?= $blogTitle; ?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="<?= $blogDescription; ?>">
<link rel="icon" type="image/svg+xml" href="<?= h($basePath) ?>/img/favicon.svg">
<link rel="stylesheet" href="<?= h($basePath) ?>/mb/style.css?<?= rand(); ?>">
</head>
<body>
<header>
<?php include './mb/header.php'; ?>
</header>
<main>

<div class="mb-breadcrumb" aria-label="Breadcrumb">
    <?php if (isset($homePage) && is_array($homePage) && count($homePage) >= 2): ?>
        <a href="<?= h($homePage[1]) ?>"><?= h($homePage[0]) ?></a> / <?= h($blogTitle) ?>
    <?php endif; ?>
</div>

<h1><?= $blogTitle; ?></h1>

<?php if ($isLoggedIn): ?>
<div class="mb-editor"><a href="<?= h($basePath) ?>/editor/">New post</a></div>
<?php endif; ?>

<?php if (empty($currentPosts)): ?>
<div class="mb-posts-list"><h2>There are no posts.</h2></div>
<?php else: ?>

<?php foreach($currentPosts as $p):
    $href = ($basePath === '') ? '/' . $p['slug'] . '/' : $basePath . '/' . $p['slug'] . '/';
?>
<div class="mb-posts-list">
<?php
$slug = $p['slug'];
$imgPathPattern = $root . '/img/' . $slug . '-*.{jpg,jpeg,png,gif,webp}';
$images = glob($imgPathPattern, GLOB_BRACE);
$firstImage = null;
if (!empty($images)) {
    $firstImage = basename($images[0]);
}
if ($firstImage): ?>
<a href="<?= htmlspecialchars($href) ?>"><img src="<?= h($basePath) ?>/img/<?= h($firstImage) ?>" alt="<?= h($p['title'] ?? $p['slug']) ?>"></a>
<?php endif; ?>
<h2><a href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($p['title'] ?? $p['slug']) ?>
<?php if (empty($p['title'])): ?>Untitled<?php endif; ?></a></h2>
<p><?= htmlspecialchars($p['description'] ?? '') ?></p>
<p><span class="mb-date"><?= date('d.m.Y', ($p['_time']??time())) ?></span></p>
</div>
<?php endforeach; ?>

<?php if ($totalPages > 1): ?>
<div class="mb-pagination">
    <?php if ($currentPage > 1): ?><a href="?page=<?= $currentPage - 1 ?>">< Prev</a><?php endif; ?>
    <?php if ($currentPage < $totalPages): ?><a href="?page=<?= $currentPage + 1 ?>">Next ></a><?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

</main>
<footer>
<?php include './mb/footer.php'; ?>
</footer>
</body>
</html>
