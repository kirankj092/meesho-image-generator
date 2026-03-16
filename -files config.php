[1mdiff --git a/download.php b/download.php[m
[1mindex ca7a8c1..473f833 100644[m
[1m--- a/download.php[m
[1m+++ b/download.php[m
[36m@@ -1,96 +1,102 @@[m
[31m-(Get-Item download.php).length<?php[m
[32m+[m[32m<?php[m
 require_once 'config.php';[m
 [m
[31m-// ---- Get parameters ----[m
[31m-$type      = $_GET['type']    ?? 'single';[m
[31m-$session   = $_GET['session'] ?? '';[m
[31m-$token     = $_GET['token']   ?? '';[m
[31m-$file      = $_GET['file']    ?? '';[m
[32m+[m[32mif (ob_get_level()) ob_end_clean();[m
[32m+[m
[32m+[m[32m$type    = $_GET['type']    ?? $_POST['type']    ?? 'single';[m
[32m+[m[32m$session = $_GET['session'] ?? $_POST['session'] ?? '';[m
[32m+[m[32m$token   = $_GET['token']   ?? $_POST['token']   ?? '';[m
[32m+[m[32m$file    = $_GET['file']    ?? '';[m
 [m
[31m-// ---- Validate session ID (alphanumeric only) ----[m
 if (!preg_match('/^[a-f0-9]{16}$/', $session)) {[m
     http_response_code(400);[m
     die('Invalid request.');[m
 }[m
 [m
[31m-// ---- Session folder path ----[m
 $sessionDir = OUTPUTS_PATH . $session . '/';[m
 [m
[31m-// ---- Check session folder exists ----[m
 if (!is_dir($sessionDir)) {[m
     http_response_code(404);[m
     die('Files not found or expired.');[m
 }[m
 [m
 // ============================================================[m
[31m-// SINGLE FILE DOWNLOAD[m
[32m+[m[32m// SELECTED FILES ZIP[m
 // ============================================================[m
[31m-if ($type !== 'zip') {[m
[32m+[m[32mif ($type === 'selected_zip') {[m
[32m+[m
[32m+[m[32m    $files = $_POST['files'] ?? [];[m
 [m
[31m-    // Verify token[m
     $verified = verifyDownloadToken($token);[m
     if (!$verified) {[m
         http_response_code(403);[m
[31m-        die('Invalid or expired download link.');[m
[32m+[m[32m        die('Invalid or expired link.');[m
     }[m
 [m
[31m-    // Validate filename (no path traversal)[m
[31m-    $filename = basename($file);[m
[31m-    $filepath = $sessionDir . $filename;[m
[32m+[m[32m    if (!class_exists('ZipArchive')) {[m
[32m+[m[32m        http_response_code(500);[m
[32m+[m[32m        die('ZIP not supported.');[m
[32m+[m[32m    }[m
 [m
[31m-    if (!file_exists($filepath)) {[m
[31m-        http_response_code(404);[m
[31m-        die('File not found.');[m
[32m+[m[32m    $zipPath = OUTPUTS_PATH . $session . '_selected.zip';[m
[32m+[m[32m    $zip     = new ZipArchive();[m
[32m+[m
[32m+[m[32m    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {[m
[32m+[m[32m        http_response_code(500);[m
[32m+[m[32m        die('Could not create ZIP.');[m
     }[m
 [m
[31m-    // Log download[m
[31m-    try {[m
[31m-        $db   = getDB();[m
[31m-        $stmt = $db->prepare("INSERT INTO usage_log (session_id, ip_address, action, filename) VALUES (?, ?, 'download_free', ?)");[m
[31m-        $stmt->execute([$session, $_SERVER['REMOTE_ADDR'], $filename]);[m
[31m-    } catch (Exception $e) {[m
[31m-        error_log('Download log failed: ' . $e->getMessage());[m
[32m+[m[32m    foreach ($files as $f) {[m
[32m+[m[32m        $filename = basename($f);[m
[32m+[m[32m        $filepath = $sessionDir . $filename;[m
[32m+[m[32m        if (file_exists($filepath)) {[m
[32m+[m[32m            $zip->addFile($filepath, 'meesho_selected/' . $filename);[m
[32m+[m[32m        }[m
     }[m
 [m
[31m-    // Serve file[m
[31m-    header('Content-Type: image/jpeg');[m
[31m-    header('Content-Disposition: attachment; filename="meesho_' . $filename . '"');[m
[31m-    header('Content-Length: ' . filesize($filepath));[m
[31m-    header('Cache-Control: no-cache');[m
[31m-    readfile($filepath);[m
[32m+[m[32m    $zip->close();[m
[32m+[m
[32m+[m[32m    ob_end_clean();[m
[32m+[m[32m    header('Content-Type: application/zip');[m
[32m+[m[32m    header('Content-Disposition: attachment; filename="meesho_selected.zip"');[m
[32m+[m[32m    header('Content-Length: ' . filesize($zipPath));[m
[32m+[m[32m    header('Cache-Control: no-store, no-cache, must-revalidate');[m
[32m+[m[32m    header('Pragma: no-cache');[m
[32m+[m
[32m+[m[32m    $handle = fopen($zipPath, 'rb');[m
[32m+[m[32m    while (!feof($handle)) {[m
[32m+[m[32m        echo fread($handle, 8192);[m
[32m+[m[32m        flush();[m
[32m+[m[32m    }[m
[32m+[m[32m    fclose($handle);[m
[32m+[m[32m    @unlink($zipPath);[m
     exit;[m
 }[m
 [m
 // ============================================================[m
[31m-// SELECTED FILES ZIP DOWNLOAD[m
[32m+[m[32m// ALL FILES ZIP[m
 // ============================================================[m
[31m-if ($type === 'selected_zip') {[m
[32m+[m[32mif ($type === 'zip') {[m
 [m
[31m-    $token   = $_POST['token']   ?? '';[m
[31m-    $session = $_POST['session'] ?? '';[m
[31m-    $files   = $_POST['files']   ?? [];[m
[31m-[m
[31m-    // Validate session[m
[31m-    if (!preg_match('/^[a-f0-9]{16}$/', $session)) {[m
[31m-        http_response_code(400);[m
[31m-        die('Invalid request.');[m
[31m-    }[m
[31m-[m
[31m-    // Verify token[m
     $verified = verifyDownloadToken($token);[m
     if (!$verified) {[m
         http_response_code(403);[m
[31m-        die('Invalid or expired download link.');[m
[32m+[m[32m        die('Invalid or expired link.');[m
     }[m
 [m
[31m-    $sessionDir = OUTPUTS_PATH . $session . '/';[m
[31m-[m
     if (!class_exists('ZipArchive')) {[m
         http_response_code(500);[m
         die('ZIP not supported.');[m
     }[m
 [m
[31m-    $zipPath = OUTPUTS_PATH . $session . '_selected.zip';[m
[32m+[m[32m    $allFiles = glob($sessionDir . '*.jpg');[m
[32m+[m
[32m+[m[32m    if (empty($allFiles)) {[m
[32m+[m[32m        http_response_code(404);[m
[32m+[m[32m        die('No files found.');[m
[32m+[m[32m    }[m
[32m+[m
[32m+[m[32m    $zipPath = OUTPUTS_PATH . $session . '_download.zip';[m
     $zip     = new ZipArchive();[m
 [m
     if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {[m
[36m@@ -98,19 +104,23 @@[m [mif ($type === 'selected_zip') {[m
         die('Could not create ZIP.');[m
     }[m
 [m
[31m-    foreach ($files as $f) {[m
[31m-        $filename = basename($f); // prevent path traversal[m
[31m-        $filepath = $sessionDir . $filename;[m
[31m-        if (file_exists($filepath)) {[m
[31m-            $zip->addFile($filepath, 'meesho_selected/' . $filename);[m
[31m-        }[m
[32m+[m[32m    foreach ($allFiles as $f) {[m
[32m+[m[32m        $zip->addFile($f, 'meesho_images/' . basename($f));[m
     }[m
 [m
     $zip->close();[m
 [m
[32m+[m[32m    try {[m
[32m+[m[32m        $db   = getDB();[m
[32m+[m[32m        $stmt = $db->prepare("INSERT INTO usage_log (session_id, ip_address, action) VALUES (?, ?, 'download_pro')");[m
[32m+[m[32m        $stmt->execute([$session, $_SERVER['REMOTE_ADDR']]);[m
[32m+[m[32m    } catch (Exception $e) {[m
[32m+[m[32m        error_log('ZIP log failed: ' . $e->getMessage());[m
[32m+[m[32m    }[m
[32m+[m
     ob_end_clean();[m
     header('Content-Type: application/zip');[m
[31m-    header('Content-Disposition: attachment; filename="meesho_selected_' . $session . '.zip"');[m
[32m+[m[32m    header('Content-Disposition: attachment; filename="meesho_images_' . $session . '.zip"');[m
     header('Content-Length: ' . filesize($zipPath));[m
     header('Cache-Control: no-store, no-cache, must-revalidate');[m
     header('Pragma: no-cache');[m
[36m@@ -126,62 +136,42 @@[m [mif ($type === 'selected_zip') {[m
 }[m
 [m
 // ============================================================[m
[31m-// ZIP DOWNLOAD — all files in session[m
[32m+[m[32m// SINGLE FILE DOWNLOAD[m
 // ============================================================[m
[31m-[m
[31m-// Verify token[m
 $verified = verifyDownloadToken($token);[m
 if (!$verified) {[m
     http_response_code(403);[m
[31m-    die('Invalid or expired download link.');[m
[31m-}[m
[31m-[m
[31m-// Check ZipArchive is available[m
[31m-if (!class_exists('ZipArchive')) {[m
[31m-    http_response_code(500);[m
[31m-    die('ZIP not supported on this server.');[m
[32m+[m[32m    die('Invalid or expired link.');[m
 }[m
 [m
[31m-// Get all JPG files in session folder[m
[31m-$files = glob($sessionDir . '*.jpg');[m
[32m+[m[32m$filename = basename($file);[m
[32m+[m[32m$filepath = $sessionDir . $filename;[m
 [m
[31m-if (empty($files)) {[m
[32m+[m[32mif (!file_exists($filepath)) {[m
     http_response_code(404);[m
[31m-    die('No files found.');[m
[32m+[m[32m    die('File not found.');[m
 }[m
 [m
[31m-// Create ZIP in temp location[m
[31m-$zipPath = OUTPUTS_PATH . $session . '_download.zip';[m
[31m-$zip     = new ZipArchive();[m
[31m-[m
[31m-if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {[m
[31m-    http_response_code(500);[m
[31m-    die('Could not create ZIP file.');[m
[31m-}[m
[31m-[m
[31m-foreach ($files as $f) {[m
[31m-    $zip->addFile($f, 'meesho_images/' . basename($f));[m
[31m-}[m
[31m-[m
[31m-$zip->close();[m
[31m-[m
[31m-// Log ZIP download[m
 try {[m
     $db   = getDB();[m
[31m-    $stmt = $db->prepare("INSERT INTO usage_log (session_id, ip_address, action) VALUES (?, ?, 'download_pro')");[m
[31m-    $stmt->execute([$session, $_SERVER['REMOTE_ADDR']]);[m
[32m+[m[32m    $stmt = $db->prepare("INSERT INTO usage_log (session_id, ip_address, action, filename) VALUES (?, ?, 'download_free', ?)");[m
[32m+[m[32m    $stmt->execute([$session, $_SERVER['REMOTE_ADDR'], $filename]);[m
 } catch (Exception $e) {[m
[31m-    error_log('ZIP log failed: ' . $e->getMessage());[m
[32m+[m[32m    error_log('Download log failed: ' . $e->getMessage());[m
 }[m
 [m
[31m-// Serve ZIP[m
[31m-header('Content-Type: application/zip');[m
[31m-header('Content-Disposition: attachment; filename="meesho_images_' . $session . '.zip"');[m
[31m-header('Content-Length: ' . filesize($zipPath));[m
[31m-header('Cache-Control: no-cache');[m
[31m-readfile($zipPath);[m
[31m-[m
[31m-// Cleanup ZIP after serving[m
[31m-[m
[31m-@unlink($zipPath);[m
[32m+[m[32mob_end_clean();[m
[32m+[m[32mheader('Content-Type: image/jpeg');[m
[32m+[m[32mheader('Content-Disposition: attachment; filename="meesho_' . $filename . '"');[m
[32m+[m[32mheader('Content-Length: ' . filesize($filepath));[m
[32m+[m[32mheader('Cache-Control: no-store, no-cache, must-revalidate');[m
[32m+[m[32mheader('Pragma: no-cache');[m
[32m+[m[32mheader('Expires: 0');[m
[32m+[m
[32m+[m[32m$handle = fopen($filepath, 'rb');[m
[32m+[m[32mwhile (!feof($handle)) {[m
[32m+[m[32m    echo fread($handle, 8192);[m
[32m+[m[32m    flush();[m
[32m+[m[32m}[m
[32m+[m[32mfclose($handle);[m
 exit;[m
\ No newline at end of file[m
