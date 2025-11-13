<?php
session_start();

define('SITE_NAME', 'Rancho Las Trojes');
define('SITE_URL', 'http://localhost/lastrojes');
define('UPLOAD_PATH', '../assets/uploads/');

define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'mov', 'avi']);
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

function generarUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function sanitizar($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>