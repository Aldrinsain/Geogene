<?php
// GeoGene User - Profile Photo Upload (AJAX endpoint)
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

function respond($ok, $data = []) {
    echo json_encode(array_merge(['success' => $ok], $data));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['photo'])) {
    respond(false, ['message' => 'No file was uploaded.']);
}

$file = $_FILES['photo'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    respond(false, ['message' => 'An error occurred while uploading the file.']);
}

// Validate: image type only, max 3MB
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!isset($allowed[$mime])) {
    respond(false, ['message' => 'Only JPG, PNG, or WEBP image files are allowed.']);
}

if ($file['size'] > 3 * 1024 * 1024) {
    respond(false, ['message' => 'File size exceeds the 3MB limit. Please select a smaller image.']);
}

$ext = $allowed[$mime];
$userId = $_SESSION['user_id'];
$filename = 'user_' . $userId . '_' . time() . '.' . $ext;

$uploadDir = __DIR__ . '/../uploads/profile-photos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    respond(false, ['message' => 'Unable to save the file on the server.']);
}

// Remove the previous photo file (if any) to avoid piling up old uploads
$stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$old = $stmt->fetchColumn();
if ($old) {
    $oldPath = __DIR__ . '/../' . $old;
    if (is_file($oldPath)) {
        @unlink($oldPath);
    }
}

$relativePath = 'uploads/profile-photos/' . $filename;

$pdo->prepare("UPDATE users SET profile_photo = ? WHERE user_id = ?")
    ->execute([$relativePath, $userId]);

respond(true, ['url' => '../' . $relativePath]);
