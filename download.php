(Get-Item download.php).length<?php
require_once 'config.php';

// ---- Get parameters ----
$type      = $_GET['type']    ?? 'single';
$session   = $_GET['session'] ?? '';
$token     = $_GET['token']   ?? '';
$file      = $_GET['file']    ?? '';

// ---- Validate session ID (alphanumeric only) ----
if (!preg_match('/^[a-f0-9]{16}$/', $session)) {
    http_response_code(400);
    die('Invalid request.');
}

// ---- Session folder path ----
$sessionDir = OUTPUTS_PATH . $session . '/';

// ---- Check session folder exists ----
if (!is_dir($sessionDir)) {
    http_response_code(404);
    die('Files not found or expired.');
}

// ============================================================
// SINGLE FILE DOWNLOAD
// ============================================================
if ($type !== 'zip') {

    // Verify token
    $verified = verifyDownloadToken($token);
    if (!$verified) {
        http_response_code(403);
        die('Invalid or expired download link.');
    }

    // Validate filename (no path traversal)
    $filename = basename($file);
    $filepath = $sessionDir . $filename;

    if (!file_exists($filepath)) {
        http_response_code(404);
        die('File not found.');
    }

    // Log download
    try {
        $db   = getDB();
        $stmt = $db->prepare("INSERT INTO usage_log (session_id, ip_address, action, filename) VALUES (?, ?, 'download_free', ?)");
        $stmt->execute([$session, $_SERVER['REMOTE_ADDR'], $filename]);
    } catch (Exception $e) {
        error_log('Download log failed: ' . $e->getMessage());
    }

    // Serve file
    header('Content-Type: image/jpeg');
    header('Content-Disposition: attachment; filename="meesho_' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache');
    readfile($filepath);
    exit;
}

// ============================================================
// SELECTED FILES ZIP DOWNLOAD
// ============================================================
if ($type === 'selected_zip') {

    $token   = $_POST['token']   ?? '';
    $session = $_POST['session'] ?? '';
    $files   = $_POST['files']   ?? [];

    // Validate session
    if (!preg_match('/^[a-f0-9]{16}$/', $session)) {
        http_response_code(400);
        die('Invalid request.');
    }

    // Verify token
    $verified = verifyDownloadToken($token);
    if (!$verified) {
        http_response_code(403);
        die('Invalid or expired download link.');
    }

    $sessionDir = OUTPUTS_PATH . $session . '/';

    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        die('ZIP not supported.');
    }

    $zipPath = OUTPUTS_PATH . $session . '_selected.zip';
    $zip     = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        die('Could not create ZIP.');
    }

    foreach ($files as $f) {
        $filename = basename($f); // prevent path traversal
        $filepath = $sessionDir . $filename;
        if (file_exists($filepath)) {
            $zip->addFile($filepath, 'meesho_selected/' . $filename);
        }
    }

    $zip->close();

    ob_end_clean();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="meesho_selected_' . $session . '.zip"');
    header('Content-Length: ' . filesize($zipPath));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    $handle = fopen($zipPath, 'rb');
    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);
    @unlink($zipPath);
    exit;
}

// ============================================================
// ZIP DOWNLOAD — all files in session
// ============================================================

// Verify token
$verified = verifyDownloadToken($token);
if (!$verified) {
    http_response_code(403);
    die('Invalid or expired download link.');
}

// Check ZipArchive is available
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    die('ZIP not supported on this server.');
}

// Get all JPG files in session folder
$files = glob($sessionDir . '*.jpg');

if (empty($files)) {
    http_response_code(404);
    die('No files found.');
}

// Create ZIP in temp location
$zipPath = OUTPUTS_PATH . $session . '_download.zip';
$zip     = new ZipArchive();

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    die('Could not create ZIP file.');
}

foreach ($files as $f) {
    $zip->addFile($f, 'meesho_images/' . basename($f));
}

$zip->close();

// Log ZIP download
try {
    $db   = getDB();
    $stmt = $db->prepare("INSERT INTO usage_log (session_id, ip_address, action) VALUES (?, ?, 'download_pro')");
    $stmt->execute([$session, $_SERVER['REMOTE_ADDR']]);
} catch (Exception $e) {
    error_log('ZIP log failed: ' . $e->getMessage());
}

// Serve ZIP
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="meesho_images_' . $session . '.zip"');
header('Content-Length: ' . filesize($zipPath));
header('Cache-Control: no-cache');
readfile($zipPath);

// Cleanup ZIP after serving

@unlink($zipPath);
exit;