<?php
require_once 'config.php';

// ---- Check session has generated images ----
if (empty($_SESSION['generated'])) {
    header('Location: index.php');
    exit;
}

$data      = $_SESSION['generated'];
$files     = $data['files'];
$sessionId = $data['sessionId'];
$weight    = $data['weight'];

// ---- Shipping estimate ----
if ($weight <= 500) {
    $shipping = ['local' => '₹35–₹45', 'national' => '₹60–₹70'];
} elseif ($weight <= 1000) {
    $shipping = ['local' => '₹55–₹65', 'national' => '₹90–₹100'];
} else {
    $shipping = ['local' => '₹75–₹85', 'national' => '₹120–₹140'];
}

// ---- Separate by type ----
$clean   = array_filter($files, fn($f) => $f['type'] === 'clean');
$sticker = array_filter($files, fn($f) => $f['type'] === 'sticker');
$shift   = array_filter($files, fn($f) => $f['type'] === 'shift');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generated Images — Meesho Image Generator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/result.css">
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="container">
        <div class="header-inner">
            <div class="logo">
                <span class="logo-icon">⚡</span>
                <span class="logo-text">Meesho <strong>Image Generator</strong></span>
            </div>
            <a href="index.php" class="new-upload-btn">+ New Upload</a>
        </div>
    </div>
</header>

<!-- RESULT HERO -->
<section class="result-hero">
    <div class="container">
        <div class="result-hero-inner">
            <div class="result-hero-left">
                <div class="success-badge">✅ 14 Images Generated!</div>
                <h1 class="result-title">Your Optimised Images Are Ready</h1>
                <div class="shipping-info">
                    <span class="shipping-label">Estimated Shipping:</span>
                    <span class="shipping-local">Local: <?= e($shipping['local']) ?></span>
                    <span class="shipping-national">National: <?= e($shipping['national']) ?></span>
                </div>
            </div>
            <div class="result-hero-right">
                <button class="select-all-btn" id="selectAllBtn" onclick="toggleSelectAll()">Select All</button>
                <a href="download.php?session=<?= e($sessionId) ?>&type=zip&token=<?= e(generateDownloadToken($sessionId . '/all')) ?>" class="download-zip-btn" id="downloadZipBtn">
                    ⬇ Download ZIP
                </a>
            </div>
        </div>
    </div>
</section>

<!-- TABS + GRID -->
<main class="result-main">
    <div class="container">

        <!-- TABS -->
        <div class="tabs">
            <button class="tab active" onclick="filterImages('all', this)">All <span class="tab-count">14</span></button>
            <button class="tab" onclick="filterImages('clean', this)">Clean <span class="tab-count">4</span></button>
            <button class="tab" onclick="filterImages('sticker', this)">Sticker <span class="tab-count">8</span></button>
            <button class="tab" onclick="filterImages('shift', this)">Shift <span class="tab-count">2</span></button>
        </div>

        <!-- IMAGE GRID -->
        <div class="image-grid" id="imageGrid">
            <?php foreach ($files as $file): ?>
            <div class="image-card" data-type="<?= e($file['type']) ?>">
                <div class="image-wrap">
                    <img
                        src="outputs/<?= e($sessionId) ?>/<?= e($file['filename']) ?>"
                        alt="<?= e($file['label']) ?>"
                        loading="lazy"
                    >
                    <div class="image-overlay">
                        <a href="download.php?file=<?= e($file['filename']) ?>&session=<?= e($sessionId) ?>&token=<?= e($file['token']) ?>" class="overlay-download">⬇ Download</a>
                    </div>
                    <label class="image-checkbox">
                        <input type="checkbox" class="img-check" value="<?= e($file['filename']) ?>">
                        <span class="checkmark"></span>
                    </label>
                    <div class="type-badge type-<?= e($file['type']) ?>"><?= e(ucfirst($file['type'])) ?></div>
                </div>
                <div class="image-info">
                    <div class="image-label"><?= e($file['label']) ?></div>
                    <div class="image-shipping">
                        <span class="ship-local"><?= e($shipping['local']) ?></span>
                        <span class="ship-national"><?= e($shipping['national']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- BACK BUTTON -->
        <div class="back-wrap">
            <a href="index.php" class="back-btn">← Generate New Images</a>
        </div>

    </div>
</main>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <p>© 2025 KJ Pixel · <a href="https://kjpixel.com">kjpixel.com</a> · Free tool for Meesho sellers</p>
    </div>
</footer>

<script>
// ---- Tab filter ----
function filterImages(type, btn) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.image-card').forEach(card => {
        if (type === 'all' || card.dataset.type === type) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// ---- Select all toggle ----
let allSelected = false;
function toggleSelectAll() {
    allSelected = !allSelected;
    document.querySelectorAll('.img-check').forEach(cb => {
        if (cb.closest('.image-card').style.display !== 'none') {
            cb.checked = allSelected;
        }
    });
    document.getElementById('selectAllBtn').textContent = allSelected ? 'Deselect All' : 'Select All';
}
</script>
</body>
</html>

