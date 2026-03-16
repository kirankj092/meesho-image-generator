<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meesho Image Generator — KJ Pixel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
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
            <div class="header-badge">100% Free</div>
        </div>
    </div>
</header>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <h1 class="hero-title">Generate Low Shipping<br>Images for Meesho</h1>
        <p class="hero-subtitle">Upload your product image — get 14 optimised variants that reduce your Meesho shipping charges instantly.</p>

        <!-- STEPS -->
        <div class="steps">
            <div class="step">
                <div class="step-icon">01</div>
                <div class="step-text">Upload Image</div>
            </div>
            <div class="step-arrow">→</div>
            <div class="step">
                <div class="step-icon">02</div>
                <div class="step-text">Generate 14 Variants</div>
            </div>
            <div class="step-arrow">→</div>
            <div class="step">
                <div class="step-icon">03</div>
                <div class="step-text">Download & Save</div>
            </div>
        </div>
    </div>
</section>

<!-- MAIN -->
<main class="main">
    <div class="container">
        <div class="tool-grid">

            <!-- LEFT: UPLOAD -->
            <div class="upload-panel">
                <div class="panel-title">Upload Product Image</div>

                <form id="uploadForm" action="process.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <!-- DROP ZONE -->
                    <div class="dropzone" id="dropzone">
                        <div class="dropzone-idle" id="dropzoneIdle">
                            <div class="dropzone-icon">🖼️</div>
                            <div class="dropzone-title">Drop your image here</div>
                            <div class="dropzone-sub">or click to browse</div>
                            <div class="dropzone-hint">JPG, PNG, WebP — Max 5MB</div>
                        </div>
                        <div class="dropzone-preview" id="dropzonePreview" style="display:none">
                            <img id="previewImg" src="" alt="Preview">
                            <button type="button" class="remove-btn" id="removeBtn">✕</button>
                        </div>
                        <input type="file" name="image" id="fileInput" accept=".jpg,.jpeg,.png,.webp" hidden>
                    </div>

                    

                    <!-- TIPS -->
                    <div class="tips-box">
                        <div class="tips-title">💡 Tips for best results</div>
                        <ul class="tips-list">
                            <li>Use a clear product image with white or plain background</li>
                            <li>Higher resolution image gives better output quality</li>
                            <li>Avoid images with text or watermarks</li>
                        </ul>
                    </div>

                    <!-- SUBMIT -->
                    <button type="submit" class="generate-btn" id="generateBtn" disabled>
                        <span class="btn-icon">⚡</span>
                        <span class="btn-text">Generate 14 Low Shipping Images</span>
                    </button>

                </form>
            </div>

            <!-- RIGHT: INFO -->
            <div class="info-panel">
                <div class="info-card">
                    <div class="info-card-icon">🎯</div>
                    <div class="info-card-title">What You Get</div>
                    <div class="info-card-body">
                        <div class="info-item">
                            <span class="info-item-count">4</span>
                            <span class="info-item-text">Clean images — no sticker, perfect as Meesho primary image</span>
                        </div>
                        <div class="info-item">
                            <span class="info-item-count">8</span>
                            <span class="info-item-text">Sticker variants — Free Delivery, Best Seller, Special Offer badges</span>
                        </div>
                        <div class="info-item">
                            <span class="info-item-count">2</span>
                            <span class="info-item-text">Frame shift variants — slight left and right shift</span>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-card-icon">💰</div>
                    <div class="info-card-title">How It Saves Shipping</div>
                    <div class="info-card-body">
                        <p>Meesho's AI judges product size from the image frame. Our tool adds a solid color border that makes your product appear smaller — pushing it into a lower weight slab.</p>
                        <div class="saving-badge">Save up to ₹40 per order</div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-card-icon">🔒</div>
                    <div class="info-card-title">Safe & Private</div>
                    <div class="info-card-body">
                        <p>Your images are processed securely and deleted automatically after 1 hour. We never store or share your product images.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <p>© 2025 KJ Pixel · <a href="https://kjpixel.com">kjpixel.com</a> · Free tool for Meesho sellers</p>
    </div>
</footer>

<script src="assets/script.js"></script>
</body>
</html>

