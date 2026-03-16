<?php
require_once 'config.php';

// ---- Security checks ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

verifyCsrf();

// ---- Rate limiting ----
if (isRateLimited($_SERVER['REMOTE_ADDR'])) {
    die('Too many uploads. Please wait 60 seconds.');
}

// ---- Validate file ----
if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    header('Location: index.php?error=no_file');
    exit;
}

$file = $_FILES['image'];

// Layer 1 — File size
if ($file['size'] > MAX_FILE_SIZE) {
    header('Location: index.php?error=too_large');
    exit;
}

// Layer 2 — Extension whitelist
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
    header('Location: index.php?error=invalid_type');
    exit;
}

// Layer 3 — TRUE MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
    header('Location: index.php?error=invalid_type');
    exit;
}

// Layer 4 — GD can open it
$info = @getimagesize($file['tmp_name']);
if (!$info) {
    header('Location: index.php?error=invalid_image');
    exit;
}

// ---- Load source image ----
$src = match($mime) {
    'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
    'image/png'  => imagecreatefrompng($file['tmp_name']),
    'image/webp' => imagecreatefromwebp($file['tmp_name']),
};

if (!$src) {
    header('Location: index.php?error=invalid_image');
    exit;
}

// ---- Get source dimensions ----
$srcW = imagesx($src);
$srcH = imagesy($src);

// ---- Session folder for this upload ----
$sessionId  = bin2hex(random_bytes(8));
$outputDir  = OUTPUTS_PATH . $sessionId . '/';
mkdir($outputDir, 0755, true);

// ---- Get weight for shipping estimate ----
$weight = 200; // fixed — always slab1

// ---- Define 14 variants ----
$variants = [
    // Clean variants (no sticker)
    ['type' => 'clean', 'border' => 'maroon', 'label' => 'Dark Maroon',  'sticker' => null, 'shift' => 0],
    ['type' => 'clean', 'border' => 'navy',   'label' => 'Deep Navy',    'sticker' => null, 'shift' => 0],
    ['type' => 'clean', 'border' => 'forest', 'label' => 'Forest Green', 'sticker' => null, 'shift' => 0],
    ['type' => 'clean', 'border' => 'white',  'label' => 'Pure White',   'sticker' => null, 'shift' => 0],

    // Sticker variants
    ['type' => 'sticker', 'border' => 'maroon', 'label' => 'Maroon + Free Delivery', 'sticker' => 'free_delivery', 'shift' => 0],
    ['type' => 'sticker', 'border' => 'navy',   'label' => 'Navy + Best Seller',     'sticker' => 'best_seller',   'shift' => 0],
    ['type' => 'sticker', 'border' => 'forest', 'label' => 'Green + Special Offer',  'sticker' => 'special_offer', 'shift' => 0],
    ['type' => 'sticker', 'border' => 'white',  'label' => 'White + Best Deal',      'sticker' => 'best_deal',     'shift' => 0],
    ['type' => 'sticker', 'border' => 'maroon', 'label' => 'Maroon + Best Quality',  'sticker' => 'best_quality',  'shift' => 0],
    ['type' => 'sticker', 'border' => 'navy',   'label' => 'Navy + Free Delivery',   'sticker' => 'free_delivery', 'shift' => 0],
    ['type' => 'sticker', 'border' => 'forest', 'label' => 'Green + Best Seller',    'sticker' => 'best_seller',   'shift' => 0],
    ['type' => 'sticker', 'border' => 'white',  'label' => 'White + Special Offer',  'sticker' => 'special_offer', 'shift' => 0],

    // Frame shift variants
    ['type' => 'shift', 'border' => 'maroon', 'label' => 'Left Shift',  'sticker' => null, 'shift' => -60],
    ['type' => 'shift', 'border' => 'navy',   'label' => 'Right Shift', 'sticker' => null, 'shift' => 60],
];

// ---- Border colors ----
$colors = [
    'maroon' => [128, 0,   0  ],
    'navy'   => [0,   0,   128],
    'forest' => [20,  83,  45 ],
    'white'  => [255, 255, 255],
];

// ---- Process each variant ----
$generatedFiles = [];

foreach ($variants as $i => $variant) {

    $canvas = imagecreatetruecolor(CANVAS_SIZE, CANVAS_SIZE);

    // Fill background with border color
    [$r, $g, $b] = $colors[$variant['border']];
    $bg = imagecolorallocate($canvas, $r, $g, $b);
    imagefill($canvas, 0, 0, $bg);

    // Center offset with shift
    $offset   = (CANVAS_SIZE - PRODUCT_SIZE) / 2;
    $offsetX  = $offset + $variant['shift'];
    $offsetY  = $offset;

    // Clamp offsetX so product stays inside canvas
    $offsetX = max(0, min(CANVAS_SIZE - PRODUCT_SIZE, $offsetX));

    // Copy and resize product onto canvas
    imagecopyresampled(
        $canvas, $src,
        $offsetX, $offsetY,
        0, 0,
        PRODUCT_SIZE, PRODUCT_SIZE,
        $srcW, $srcH
    );

    // Draw sticker if needed
    if ($variant['sticker']) {
        drawSticker($canvas, $variant['sticker']);
    }

    // Save file
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

// ---- Log usage ----
try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO usage_log (session_id, ip_address, action) VALUES (?, ?, 'generate')");
    $stmt->execute([$sessionId, $_SERVER['REMOTE_ADDR']]);
} catch (Exception $e) {
    error_log('Usage log failed: ' . $e->getMessage());
}

// ---- Store in session and redirect ----
$_SESSION['generated'] = [
    'files'     => $generatedFiles,
    'sessionId' => $sessionId,
    'weight'    => $weight,
    'timestamp' => time(),
];

header('Location: result.php');
exit;

// ============================================================
// DRAW STICKER FUNCTION — PHP GD (no external files needed)
// ============================================================
function drawSticker(GdImage $canvas, string $type): void {

    $stickers = [
        'free_delivery' => ['text' => 'FREE DELIVERY', 'bg' => [220, 38,  38 ], 'icon' => '🚚'],
        'best_seller'   => ['text' => 'BEST SELLER',   'bg' => [234, 88,  12 ], 'icon' => '⭐'],
        'special_offer' => ['text' => 'SPECIAL OFFER', 'bg' => [37,  99,  235], 'icon' => '🎁'],
        'best_deal'     => ['text' => 'BEST DEAL',     'bg' => [22,  163, 74 ], 'icon' => '💰'],
        'best_quality'  => ['text' => 'BEST QUALITY',  'bg' => [124, 58,  237], 'icon' => '✅'],
    ];

    $s = $stickers[$type];

    // Sticker dimensions
    $sx = 10;
    $sy = CANVAS_SIZE - 90;
    $sw = 220;
    $sh = 60;
    $rx = 12;

    // Draw rounded rectangle background
    [$r, $g, $b] = $s['bg'];
    $bgColor   = imagecolorallocate($canvas, $r, $g, $b);
    $white     = imagecolorallocate($canvas, 255, 255, 255);

    // Fill main rectangle
    imagefilledrectangle($canvas, $sx + $rx, $sy, $sx + $sw - $rx, $sy + $sh, $bgColor);
    imagefilledrectangle($canvas, $sx, $sy + $rx, $sx + $sw, $sy + $sh - $rx, $bgColor);

    // Draw corner arcs
    imagefilledarc($canvas, $sx + $rx,        $sy + $rx,        $rx*2, $rx*2, 180, 270, $bgColor, IMG_ARC_PIE);
    imagefilledarc($canvas, $sx + $sw - $rx,  $sy + $rx,        $rx*2, $rx*2, 270, 360, $bgColor, IMG_ARC_PIE);
    imagefilledarc($canvas, $sx + $rx,        $sy + $sh - $rx,  $rx*2, $rx*2,  90, 180, $bgColor, IMG_ARC_PIE);
    imagefilledarc($canvas, $sx + $sw - $rx,  $sy + $sh - $rx,  $rx*2, $rx*2,   0,  90, $bgColor, IMG_ARC_PIE);

    // Draw text
    $font = 5; // built-in GD font
    $textX = $sx + 16;
    $textY = $sy + 20;
    imagestring($canvas, $font, $textX, $textY, $s['text'], $white);
}
