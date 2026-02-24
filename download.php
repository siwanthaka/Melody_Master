<?php
require_once 'includes/auth.php';
requireLogin(); // keep this if you still want login protection

$filePath = __DIR__ . '/downloads/Melody_Masters_Digital_Download.pdf';

if (!file_exists($filePath)) {
    die("File not found.");
}

// Clear output buffer (important)
if (ob_get_level()) {
    ob_end_clean();
}

// Force PDF download
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Melody_Master_Digital_Download.pdf"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');

readfile($filePath);
exit;