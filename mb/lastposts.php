<?php
require_once __DIR__ . '/settings.php';

$root = dirname(__DIR__);
$posts = [];

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
$posts = array_slice($posts, 0, $lastPosts);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

$blogPhysicalPath = dirname(__DIR__);
$siteRootPath = $_SERVER['DOCUMENT_ROOT'];

$relativeBlogPath = str_replace($siteRootPath, '', $blogPhysicalPath);
$relativeBlogPath = str_replace('\\', '/', $relativeBlogPath);
$relativeBlogPath = trim($relativeBlogPath, '/');

if ($relativeBlogPath === '') {
    $blogUrl = $scheme . '://' . $host;
} else {
    $blogUrl = $scheme . '://' . $host . '/' . $relativeBlogPath;
}

echo '<div class="mb-last-posts">';
echo '<a href="'.htmlspecialchars($blogUrl).'/"><h2 class="mb-title">'.$blogTitle.'</h2></a>';
foreach($posts as $p):
    $h1 = $p['h1'] ?? $p['title'] ?? $p['slug'];
    $description = $p['description'] ?? '';
    $date = isset($p['datePublished']) ? date('d.m.Y', strtotime($p['datePublished'])) : date('d.m.Y', $p['_time']??time());
    $postUrl = $blogUrl . '/' . $p['slug'] . '/';
?>
    <div class="mb-post">
        <p class="mb-post-title">
            <a href="<?= htmlspecialchars($postUrl) ?>"><?= htmlspecialchars($h1) ?></a>
        </p>
        <?php if (!empty($description)): ?>
        <p class="mb-post-description"><?= htmlspecialchars($description) ?></p>
        <?php endif; ?>
        <p class="mb-post-date"><small><?= htmlspecialchars($date) ?></small></p>
    </div>
<?php
endforeach;
echo '<p class="mb-next"><a href="'.htmlspecialchars($blogUrl).'/">далее...</a></p>';
echo '</div>';