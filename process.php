<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

verifyCsrf();

if (isRateLimited($_SERVER['REMOTE_ADDR'])) {
    die('Too many uploads. Please wait 60 seconds.');
}

if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    header('Location: index.php?error=no_file');
    exit;
}

$file = $_FILES['image'];

if ($file['size'] > MAX_FILE_SIZE) {
    header('Location: index.php?error=too_large');
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
    header('Location: index.php?error=invalid_type');
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
    header('Location: index.php?error=invalid_type');
    exit;
}

$info = @getimagesize($file['tmp_name']);
if (!$info) {
    header('Location: index.php?error=invalid_image');
    exit;
}

$src = match($mime) {
    'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
    'image/png'  => imagecreatefrompng($file['tmp_name']),
    'image/webp' => imagecreatefromwebp($file['tmp_name']),
};

if (!$src) {
    header('Location: index.php?error=invalid_image');
    exit;
}

$srcW = imagesx($src);
$srcH = imagesy($src);

$sessionId = bin2hex(random_bytes(8));
$outputDir = OUTPUTS_PATH . $sessionId . '/';
mkdir($outputDir, 0755, true);

$weight = 200;

// ---- Define 12 variants ----
$variants = [
    // Clean variants
    ['type' => 'clean', 'border' => 'darkred',  'label' => 'Dark Red',          'sticker' => null,           'shift' => 0],
    ['type' => 'clean', 'border' => 'black',     'label' => 'Pure Black',        'sticker' => null,           'shift' => 0],
    ['type' => 'clean', 'border' => 'charcoal',  'label' => 'Charcoal',          'sticker' => null,           'shift' => 0],
    ['type' => 'clean', 'border' => 'navy',      'label' => 'Deep Navy',         'sticker' => null,           'shift' => 0],

    // Sticker variants
    ['type' => 'sticker', 'border' => 'black',    'label' => 'Black + Free Delivery',    'sticker' => 'free_delivery', 'shift' => 0],
    ['type' => 'sticker', 'border' => 'charcoal', 'label' => 'Charcoal + Best Seller',   'sticker' => 'best_seller',   'shift' => 0],
    ['type' => 'sticker', 'border' => 'black',    'label' => 'Black + Special Offer',    'sticker' => 'special_offer', 'shift' => 0],
    ['type' => 'sticker', 'border' => 'charcoal', 'label' => 'Charcoal + Best Deal',     'sticker' => 'best_deal',     'shift' => 0],
    ['type' => 'sticker', 'border' => 'black',    'label' => 'Black + Best Quality',     'sticker' => 'best_quality',  'shift' => 0],
    ['type' => 'sticker', 'border' => 'charcoal', 'label' => 'Charcoal + Free Delivery', 'sticker' => 'free_delivery', 'shift' => 0],
    ['type' => 'sticker', 'border' => 'black',    'label' => 'Black + Best Seller',      'sticker' => 'best_seller',   'shift' => 0],
    ['type' => 'sticker', 'border' => 'darkred',  'label' => 'Dark Red + Special Offer', 'sticker' => 'special_offer', 'shift' => 0],
];

// ---- Border colors ----
$colors = [
    'darkred'  => [121, 2,   5  ],
    'black'    => [0,   0,   0  ],
    'charcoal' => [30,  30,  30 ],
    'navy'     => [0,   0,   128],
    'forest'   => [20,  83,  45 ],
    'purple'   => [88,  28,  135],
    'teal'     => [13,  148, 136],
    'rust'     => [180, 60,  20 ],
    'pink'     => [190, 24,  93 ],
];

// ---- Process each variant ----
$generatedFiles = [];

foreach ($variants as $i => $variant) {

    $canvas = imagecreatetruecolor(CANVAS_SIZE, CANVAS_SIZE);

    [$r, $g, $b] = $colors[$variant['border']];
    $bg = imagecolorallocate($canvas, $r, $g, $b);
    imagefill($canvas, 0, 0, $bg);

    $offset  = (CANVAS_SIZE - PRODUCT_SIZE) / 2;
    $offsetX = $offset + $variant['shift'];
    $offsetY = $offset;
    $offsetX = max(0, min(CANVAS_SIZE - PRODUCT_SIZE, $offsetX));

    imagecopyresampled(
        $canvas, $src,
        $offsetX, $offsetY,
        0, 0,
        PRODUCT_SIZE, PRODUCT_SIZE,
        $srcW, $srcH
    );

    if ($variant['sticker']) {
        drawSticker($canvas, $variant['sticker']);
    }

    $filename = $sessionId . '_' . ($i + 1) . '_' . $variant['border'] . '.jpg';
    imagejpeg($canvas, $outputDir . $filename, JPEG_QUALITY);
    imagedestroy($canvas);

    $generatedFiles[] = [
        'filename' => $filename,
        'label'    => $variant['label'],
        'type'     => $variant['type'],
        'token'    => generateDownloadToken($sessionId . '/' . $filename),
    ];
}

imagedestroy($src);

try {
    $db   = getDB();
    $stmt = $db->prepare("INSERT INTO usage_log (session_id, ip_address, action) VALUES (?, ?, 'generate')");
    $stmt->execute([$sessionId, $_SERVER['REMOTE_ADDR']]);
} catch (Exception $e) {
    error_log('Usage log failed: ' . $e->getMessage());
}

$_SESSION['generated'] = [
    'files'     => $generatedFiles,
    'sessionId' => $sessionId,
    'weight'    => $weight,
    'timestamp' => time(),
];

header('Location: result.php');
exit;

// ============================================================
// DRAW STICKER FUNCTION
// ============================================================
function drawSticker(GdImage $canvas, string $type): void {

    $stickers = [
        'free_delivery' => ['text' => 'FREE DELIVERY', 'bg' => [220, 38,  38]],
        'best_seller'   => ['text' => 'BEST SELLER',   'bg' => [234, 88,  12]],
        'special_offer' => ['text' => 'SPECIAL OFFER', 'bg' => [37,  99, 235]],
        'best_deal'     => ['text' => 'BEST DEAL',     'bg' => [22, 163,  74]],
        'best_quality'  => ['text' => 'BEST QUALITY',  'bg' => [124, 58, 237]],
    ];

    $s  = $stickers[$type];
    $sx = 10;
    $sy = CANVAS_SIZE - 90;
    $sw = 220;
    $sh = 60;
    $rx = 12;

    [$r, $g, $b] = $s['bg'];
    $bgColor = imagecolorallocate($canvas, $r, $g, $b);
    $white   = imagecolorallocate($canvas, 255, 255, 255);

    imagefilledrectangle($canvas, $sx + $rx, $sy,        $sx + $sw - $rx, $sy + $sh,       $bgColor);
    imagefilledrectangle($canvas, $sx,       $sy + $rx,  $sx + $sw,       $sy + $sh - $rx, $bgColor);

    imagefilledarc($canvas, $sx + $rx,       $sy + $rx,       $rx*2, $rx*2, 180, 270, $bgColor, IMG_ARC_PIE);
    imagefilledarc($canvas, $sx + $sw - $rx, $sy + $rx,       $rx*2, $rx*2, 270, 360, $bgColor, IMG_ARC_PIE);
    imagefilledarc($canvas, $sx + $rx,       $sy + $sh - $rx, $rx*2, $rx*2,  90, 180, $bgColor, IMG_ARC_PIE);
    imagefilledarc($canvas, $sx + $sw - $rx, $sy + $sh - $rx, $rx*2, $rx*2,   0,  90, $bgColor, IMG_ARC_PIE);

    imagestring($canvas, 5, $sx + 16, $sy + 20, $s['text'], $white);
}

// ============================================================
// CLEANUP
// ============================================================
function cleanupOldSessions(): void {
    $outputsDir = OUTPUTS_PATH;
    $maxAge     = 3600;
    $now        = time();

    if (!is_dir($outputsDir)) return;

    $folders = glob($outputsDir . '*', GLOB_ONLYDIR);
    foreach ($folders as $folder) {
        if ($now - filemtime($folder) > $maxAge) {
            $files = glob($folder . '/*');
            foreach ($files as $file) {
                if (is_file($file)) @unlink($file);
            }
            @rmdir($folder);
        }
    }

    $zips = glob($outputsDir . '*.zip');
    foreach ($zips as $zip) {
        if ($now - filemtime($zip) > $maxAge) @unlink($zip);
    }
}

if (rand(1, 5) === 1) cleanupOldSessions();