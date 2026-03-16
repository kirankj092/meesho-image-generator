<?php
require_once 'config.php';

if (ob_get_level()) ob_end_clean();

$type    = $_GET['type']    ?? $_POST['type']    ?? 'single';
$session = $_GET['session'] ?? $_POST['session'] ?? '';
$token   = $_GET['token']   ?? $_POST['token']   ?? '';
$file    = $_GET['file']    ?? '';

if (!preg_match('/^[a-f0-9]{16}$/', $session)) {
    http_response_code(400);
    die('Invalid request.');
}

$sessionDir = OUTPUTS_PATH . $session . '/';

if (!is_dir($sessionDir)) {
    http_response_code(404);
    die('Files not found or expired.');
}

// ============================================================
// SELECTED FILES ZIP
// ============================================================
if ($type === 'selected_zip') {

    $files = $_POST['files'] ?? [];

    $verified = verifyDownloadToken($token);
    if (!$verified) {
        http_response_code(403);
        die('Invalid or expired link.');
    }

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
        $filename = basename($f);
        $filepath = $sessionDir . $filename;
        if (file_exists($filepath)) {
            $zip->addFile($filepath, 'meesho_selected/' . $filename);
        }
    }

    $zip->close();

    ob_end_clean();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="meesho_selected.zip"');
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
// ALL FILES ZIP
// ============================================================
if ($type === 'zip') {

    $verified = verifyDownloadToken($token);
    if (!$verified) {
        http_response_code(403);
        die('Invalid or expired link.');
    }

    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        die('ZIP not supported.');
    }

    $allFiles = glob($sessionDir . '*.jpg');

    if (empty($allFiles)) {
        http_response_code(404);
        die('No files found.');
    }

    $zipPath = OUTPUTS_PATH . $session . '_download.zip';
    $zip     = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        die('Could not create ZIP.');
    }

    foreach ($allFiles as $f) {
        $zip->addFile($f, 'meesho_images/' . basename($f));
    }

    $zip->close();

    try {
        $db   = getDB();
        $stmt = $db->prepare("INSERT INTO usage_log (session_id, ip_address, action) VALUES (?, ?, 'download_pro')");
        $stmt->execute([$session, $_SERVER['REMOTE_ADDR']]);
    } catch (Exception $e) {
        error_log('ZIP log failed: ' . $e->getMessage());
    }

    ob_end_clean();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="meesho_images_' . $session . '.zip"');
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
// SINGLE FILE DOWNLOAD
// ============================================================
$verified = verifyDownloadToken($token);
if (!$verified) {
    http_response_code(403);
    die('Invalid or expired link.');
}

$filename = basename($file);
$filepath = $sessionDir . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    die('File not found.');
}

try {
    $db   = getDB();
    $stmt = $db->prepare("INSERT INTO usage_log (session_id, ip_address, action, filename) VALUES (?, ?, 'download_free', ?)");
    $stmt->execute([$session, $_SERVER['REMOTE_ADDR'], $filename]);
} catch (Exception $e) {
    error_log('Download log failed: ' . $e->getMessage());
}

ob_end_clean();
header('Content-Type: image/jpeg');
header('Content-Disposition: attachment; filename="meesho_' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$handle = fopen($filepath, 'rb');
while (!feof($handle)) {
    echo fread($handle, 8192);
    flush();
}
fclose($handle);
exit;