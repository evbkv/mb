<?php
session_start();

require_once __DIR__ . '/settings.php';

function slugify($text) {
    $trans = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y',
        'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
        'х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ы'=>'y','э'=>'e','ю'=>'yu','я'=>'ya',
        'ь'=>'','ъ'=>'','є'=>'ye','і'=>'i','ї'=>'yi','ґ'=>'g','ў'=>'u',
        
        'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','ā'=>'a','ą'=>'a','æ'=>'ae',
        'ç'=>'c','ć'=>'c','č'=>'c','ĉ'=>'c','ċ'=>'c',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ē'=>'e','ę'=>'e','ě'=>'e',
        'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ī'=>'i','į'=>'i',
        'ñ'=>'n','ń'=>'n','ň'=>'n',
        'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ø'=>'o','ō'=>'o','ő'=>'o','œ'=>'oe',
        'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ū'=>'u','ů'=>'u','ű'=>'u',
        'ý'=>'y','ÿ'=>'y',
        'ż'=>'z','ź'=>'z','ž'=>'z',
        'ß'=>'ss','ș'=>'s','ş'=>'s','ț'=>'t','ð'=>'d',
        
        'α'=>'a','β'=>'b','γ'=>'g','δ'=>'d','ε'=>'e','ζ'=>'z','η'=>'h','θ'=>'th',
        'ι'=>'i','κ'=>'k','λ'=>'l','μ'=>'m','ν'=>'n','ξ'=>'x','ο'=>'o','π'=>'p',
        'ρ'=>'r','σ'=>'s','τ'=>'t','υ'=>'y','φ'=>'ph','χ'=>'ch','ψ'=>'ps','ω'=>'o',
        
        '¿'=>'','¡'=>'','°'=>'',
    ];
    
    $text = mb_strtolower($text, 'UTF-8');
    $text = strtr($text, $trans);
    $text = preg_replace('/[^a-z0-9]+/','-',$text);
    $text = trim($text,'-');
    
    if ($text === '') $text = 'post';
    return $text;
}

function extract_post_from_file($filepath) {
    $s = @file_get_contents($filepath);
    if ($s === false) return [];
    if (preg_match("/\\\$post\s*=\s*(array\s*\(.*\));/sU", $s, $m)) {
        $code = 'return ' . $m[1] . ';';
        try { $arr = eval($code); if (is_array($arr)) return $arr; } catch (Throwable $e) {}
    }
    if (preg_match("/json_decode\(\s*([\"'])(.*)\\1\s*\)/sU", $s, $m)) {
        $json = stripcslashes($m[2]);
        $arr = json_decode($json,true);
        if (json_last_error()===JSON_ERROR_NONE) return $arr;
    }
    return [];
}

function get_posts_list() {
    $res = [];
    foreach (glob(__DIR__ . '/../*.php') as $f) {
        $fname = basename($f);
        if ($fname === 'index.php') continue;
        $slug = basename($f, '.php');
        $arr = extract_post_from_file($f) ?? [];
        $arr['slug'] = $slug;
        $arr['file'] = $fname;
        $arr['mtime'] = filemtime($f);
        $arr['_time'] = isset($arr['datePublished']) ? strtotime($arr['datePublished']) : $arr['mtime'];
        $res[] = $arr;
    }
    usort($res, fn($a,$b) => ($b['_time'] ?? 0) <=> ($a['_time'] ?? 0));
    return $res;
}

$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$parts = explode('/', trim($scriptDir, '/'));
$basePath = count($parts) > 1 ? '/' . implode('/', array_slice($parts,0,-1)) : '';
$basePath = rtrim($basePath, '/');

if (isset($_POST['action']) && $_POST['action']=='login') {
    $pass = $_POST['pass'] ?? '';
    if ($pass === $AUTHOR_PASS) { 
        $_SESSION['logged_in'] = true; 
        header('Location: '.($_SERVER['REQUEST_URI']??'./'));
        exit;
    }
    else { $error='Invalid password'; }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ./');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'delete' && !empty($_POST['selected_file'])) {
    $fileToDelete = __DIR__ . '/../' . basename($_POST['selected_file']);
    if (file_exists($fileToDelete)) {
        unlink($fileToDelete);
        $slug = pathinfo($fileToDelete, PATHINFO_FILENAME);
        $pattern = __DIR__ . '/../img/' . $slug . '-*.{jpg,jpeg,png,gif,webp}';
        foreach (glob($pattern, GLOB_BRACE) as $img) {
            @unlink($img);
        }
        $saveSuccess = 'The post have been deleted';
        $selected_file = '';
        $post_meta = [];
        $posts = get_posts_list();
    } else {
        $saveError = 'Deletion error.';
    }
    generate_sitemap();
}

if (empty($_SESSION['logged_in'])) {
?><!doctype html>
<html lang="<?= $lang[0]; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>MicroBlogger</title>
<link rel="icon" type="image/svg+xml" href="<?= $basePath ?>/img/favicon.svg">
<link href="<?= $basePath ?>/mb/bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
<script src="<?= $basePath ?>/mb/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div style="width: 100vw; height: 100vh; display: flex; justify-content: center; align-items: center;"><div class="container" style="width: 300px;">
    
    <h1 class="h2 mb-5">MicroBlogger</h1>    

    <form class="mb-5" method="post">

        <?php if(!empty($error)) echo '<div class="alert alert-danger mb-5" role="alert">'.htmlspecialchars($error).'</div>';?>

        <input type="hidden" name="action" value="login">
        <div class="form-floating mb-3">
            <input name="pass" type="password" class="form-control" id="floatingPassword" placeholder="Password">
            <label for="floatingPassword">Author's password</label>
        </div>
        <button type="submit" class="btn btn-lg btn-primary">Log in</button>
    </form>

    <p class="mt-5 text-secondary"><small>© <a href="https://github.com/evbkv/" class="text-secondary" target="_blank">evbkv</a> 2025</small></p>

</div></div>
</body>
</html><?php exit; }

function generate_sitemap() {
    global $basePath;
    $protocol = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePathNormalized = ltrim($basePath, '/');
    $baseUrl = "$protocol://$host/$basePathNormalized";
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $xml .= "<url>\n";
    $xml .= "    <loc>$baseUrl/</loc>\n";
    $xml .= "    <changefreq>daily</changefreq>\n";
    $xml .= "    <priority>0.9</priority>\n";
    $xml .= "</url>\n";
    $posts = get_posts_list();
    foreach ($posts as $post) {
        $postUrl = $baseUrl . '/' . $post['slug'] . '/';
        $xml .= "<url>\n";
        $xml .= "    <loc>$postUrl</loc>\n";
        $xml .= "    <changefreq>monthly</changefreq>\n";
        $xml .= "    <priority>0.8</priority>\n";
        $xml .= "</url>\n";
    }
    $xml .= '</urlset>';
    file_put_contents('../sitemap.xml', $xml);
}

$selected_file = $_POST['selected_file'] ?? $_GET['selected_file'] ?? '';
$post_meta = [];
if ($selected_file) {
    $fullpath = __DIR__ . '/../' . basename($selected_file);
    if (file_exists($fullpath)) $post_meta = extract_post_from_file($fullpath) ?? [];
}

$uploadErrors = [];
$saveError = '';
$posts = get_posts_list();

if (isset($_POST['action']) && $_POST['action'] === 'save') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $h1 = $_POST['h1'] ?? '';
    $content = $_POST['content'] ?? '';
    $existing_file = $_POST['existing_file'] ?? '';

    if ($existing_file) {
        $slug = basename($existing_file, '.php');
        $prev = null;
        $full = __DIR__ . '/../' . $existing_file;
        if (file_exists($full)) $prev = extract_post_from_file($full);
        $datePublished = $prev['datePublished'] ?? date('c');
    } else {
        $slug = slugify($h1 ?: $title);
        $baseSlug = $slug;
        $i = 1;
        while (file_exists(__DIR__ . '/../' . $slug . '.php')) { $i++; $slug = $baseSlug . '-' . $i; }
        $datePublished = date('c');
    }

    if (!empty($_FILES['images']['tmp_name']) && is_array($_FILES['images']['tmp_name'])) {
        $imgPattern = __DIR__ . '/../img/' . $slug . '-*.{jpg,jpeg,png,gif,webp}';
        $existingImgs = glob($imgPattern, GLOB_BRACE);
        $maxIndex = 0;
        foreach ($existingImgs as $p) {
            if (preg_match('/-([0-9]+)\.[^.\/]+$/', $p, $mm)) $maxIndex = max($maxIndex,intval($mm[1]));
        }
        $nextIndex = $maxIndex + 1;
        for ($k=0;$k<count($_FILES['images']['tmp_name']);$k++) {
            if ($_FILES['images']['error'][$k]===UPLOAD_ERR_NO_FILE) continue;
            if ($_FILES['images']['error'][$k]!==UPLOAD_ERR_OK) { $uploadErrors[]="File upload error: ".($_FILES['images']['name'][$k]??''); continue; }
            $tmp = $_FILES['images']['tmp_name'][$k];
            $ext = strtolower(pathinfo($_FILES['images']['name'][$k],PATHINFO_EXTENSION));
            if (!in_array($ext,['jpg','jpeg','png','gif','webp'])) { $uploadErrors[]="Invalid format ".$_FILES['images']['name'][$k]; continue; }
            $destName = $slug.'-'.str_pad($nextIndex,2,'0',STR_PAD_LEFT).'.'.$ext;
            $destPath = __DIR__ . '/../img/' . $destName;
            if (!move_uploaded_file($tmp,$destPath)) $uploadErrors[]="Couldn't save ".$_FILES['images']['name'][$k];
            $nextIndex++;
        }
    }

    $postArr = [
        'title'=>$title,
        'description'=>$description,
        'h1'=>$h1,
        'content'=>$content,
        'datePublished'=>$datePublished
    ];

    $export = var_export($postArr,true);
    $php = "<?php\n\$post = ".$export.";\ninclude __DIR__ . '/mb/template.php';\n";
    $targetFile = __DIR__ . '/../'.$slug.'.php';
    $ok = @file_put_contents($targetFile,$php);
    if ($ok===false) $saveError='Failed to write a file '.htmlspecialchars($targetFile);
    else {
        $selected_file = $slug.'.php';
        $post_meta = extract_post_from_file(__DIR__ . '/../' . $selected_file);
        $saveSuccess='The post was saved successfully.';
        $posts = get_posts_list();
    }

    generate_sitemap();
}

?><!doctype html>
<html lang="<?= $lang[0]; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>MicroBlogger</title>
<link rel="icon" type="image/svg+xml" href="<?= $basePath ?>/img/favicon.svg">
<link href="<?= $basePath ?>/mb/bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
<script src="<?= $basePath ?>/mb/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $basePath ?>/mb/tinymce/js/tinymce/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
</head>
<body>

<div class="container">

<nav class="navbar navbar-expand-lg bg-white border-bottom mb-5">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">MicroBlogger</span>
        <button class="navbar-toggler" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#navbarText" 
                aria-controls="navbarText" 
                aria-expanded="false" 
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarText">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="../">"<?= $blogTitle ?>" by <?= $AUTHOR_NAME ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="?logout=1">Log out</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<form method="post">
    <div class="input-group mb-3">
        <a class="btn btn-primary input-group-text" for="selectPostForm" href="../editor/">New</a>
        <select name="selected_file" class="form-select" id="selectPostForm">
            <option value=""></option>
            <?php foreach ($posts as $p): ?>
            <option value="<?=htmlspecialchars($p['file'])?>" <?= ($selected_file === ($p['file'] ?? '')) ? 'selected' : '' ?>>
            <?= htmlspecialchars(
            (isset($p['datePublished']) ? date('d.m.Y', strtotime($p['datePublished'])) : date('d.m.Y', $p['mtime'] ?? time())) . 
            ' — ' . 
            ($p['title'] ?? $p['h1'] ?? $p['slug'])
            ) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="action" value="load" class="input-group-text" for="selectPostForm">Load</button>
        <button type="submit" name="action" value="delete" onclick="return confirm('Are you sure you want to delete this post?');" class="input-group-text" for="selectPostForm">Delete</button>
    </div>
</form>

<?php if (!empty($saveError)) echo '<div class="alert alert-danger" role="alert">'.htmlspecialchars($saveError).'</div>'; ?>
<?php if (!empty($saveSuccess)) echo '<div class="alert alert-success" role="alert">'.htmlspecialchars($saveSuccess).'</div>'; ?>
<?php foreach($uploadErrors as $ue) echo '<div class="alert alert-danger" role="alert">'.htmlspecialchars($ue).'</div>'; ?>

<h1 class="mt-5 mb-4"><?= $selected_file ? 'Edit post' : 'New post' ?></h1>

<form method="post" enctype="multipart/form-data" class="mb-5">
<input type="hidden" name="action" value="save">
<input type="hidden" name="existing_file" value="<?= htmlspecialchars($selected_file) ?>">

<div class="form-floating mb-3">
    <input name="title" type="text" class="form-control" id="floatingInput1" placeholder=" " 
           value="<?= htmlspecialchars($post_meta['title']??'') ?>" required 
           autocomplete="off" oninput="updateColor(this, 0, 50)">
    <label for="floatingInput1">Title</label>
</div>
<div class="form-floating mb-3">
    <input name="description" type="text" class="form-control" id="floatingInput2" placeholder=" " 
           value="<?= htmlspecialchars($post_meta['description']??'') ?>" required 
           autocomplete="off" oninput="updateColor(this, 140, 160)">
    <label for="floatingInput2">Description</label>
</div>
<div class="form-floating mb-3">
    <input name="h1" type="text" class="form-control" id="floatingInput3" placeholder=" " 
           value="<?= htmlspecialchars($post_meta['h1']??'') ?>" required 
           autocomplete="off" oninput="updateColor(this, 0, 70)">
    <label for="floatingInput3">H1</label>
</div>
<script>
function updateColor(input, minLength, maxLength) {
    const length = input.value.length;
    input.style.color = (length < minLength || length > maxLength) ? '#dc3545' : 'black';
}
document.addEventListener('DOMContentLoaded', function() {
    const titleInput = document.getElementById('floatingInput1');
    if (titleInput) {
        updateColor(titleInput, 0, 50);
        titleInput.addEventListener('input', () => updateColor(titleInput, 0, 50));
    }
    const descInput = document.getElementById('floatingInput2');
    if (descInput) {
        updateColor(descInput, 140, 160);
        descInput.addEventListener('input', () => updateColor(descInput, 140, 160));
    }
    const h1Input = document.getElementById('floatingInput3');
    if (h1Input) {
        updateColor(h1Input, 0, 70);
        h1Input.addEventListener('input', () => updateColor(h1Input, 0, 70));
    }
});
</script>

<div class="form-floating mb-3"><textarea id="tiny" name="content" rows="4"><?= htmlspecialchars($post_meta['content']??'') ?></textarea></div>
<script>
  tinymce.init({
    selector: '#tiny',
    license_key: 'gpl',
    plugins: 'code link lists fullscreen wordcount',
    toolbar: 'undo redo | blocks link | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent  | code fullscreen',
    image_caption: false,
    branding: false,
    promotion: false,
    images_reuse_filename: true,
    invalid_elements: 'img',
    paste_data_images: false,
    automatic_uploads: false,
    height: 300
  });
</script>
<style>
.tox-tinymce {
    border: 1px solid rgb(222, 226, 230) !important;
    border-radius: 7px !important;
}
</style>

<div class="mb-5">

    <div class="input-group mb-3">
        <label class="input-group-text" for="inputGroupFile">Images</label>
        <input type="file" class="form-control" id="inputGroupFile" name="images[]" multiple accept="image/*">
    </div>

    <?php
    $existingImages = [];
    if (!empty($selected_file)) {
        $slug = pathinfo($selected_file, PATHINFO_FILENAME);
        $pattern = __DIR__ . '/../img/' . $slug . '-*.{jpg,jpeg,png,gif,webp}';
        $existingImages = glob($pattern, GLOB_BRACE);
    }
    if (!empty($existingImages)): ?>
    <div class="row">
        <?php foreach ($existingImages as $imgPath):
            $imgUrl = '../img/' . basename($imgPath);
        ?>
        <div class="col-4 col-md-3 col-lg-2 mb-3"> <!-- 6/4/3 колонки в зависимости от размера экрана -->
            <div class="ratio ratio-1x1"> <!-- Сохраняем квадратное соотношение сторон -->
                <img class="img-thumbnail object-fit-cover" src="<?= htmlspecialchars($imgUrl) ?>" alt="Изображение поста">
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<p><button type="submit" class="btn btn-primary btn-lg">Save</button></p>

</form>

<p class="mt-5 mb-2 text-secondary"><small>© <a href="https://github.com/evbkv/" class="text-secondary" target="_blank">evbkv</a> 2025</small></p>

</div>

</body>
</html>