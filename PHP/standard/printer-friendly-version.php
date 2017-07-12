<?php

/*
 * This file generates a printer-friendly version from a signature file
 */

require __DIR__ . '/vendor/autoload.php';

// Get file ID from query string
$fileId = isset($_GET['file']) ? $_GET['file'] : null;
if (empty($fileId)) {
    throw new \Exception("No file was uploaded");
}

// Locate document and read content
$filePath = 'app-data/' . $fileId;

// Check if doc already has a verification code registered on storage
$verificationCode = getVerificationCode($fileId);
if (!isset($verificationCode)) {
    // If not, generate a code and register it
    $verificationCode = generateVerificationCode();
    setVerificationCode($fileId, $verificationCode);
}

// Generate the printer-friendly version
$pfvContent = generatePrinterFriendlyVersion($filePath, formatVerificationCode($verificationCode));

// Redirect to the generated file
$pdfPath = 'app-data/' . formatVerificationCode($verificationCode) . '.pdf';
createAppData();
file_put_contents($pdfPath, $pfvContent);
header("Location: {$pdfPath}");
exit;