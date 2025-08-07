<?php
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatDate($date, $format = DATE_FORMAT) {
    if (!$date) return '';
    return date($format, strtotime($date));
}

function formatTime($time, $format = TIME_FORMAT) {
    if (!$time) return '';
    return date($format, strtotime($time));
}

function formatCurrency($amount, $decimals = 2) {
    return number_format($amount, $decimals);
}

function calculatePercentageReturn($entry, $exit, $direction) {
    if (!$entry || !$exit) return 0;
    
    if ($direction === 'long') {
        return (($exit - $entry) / $entry) * 100;
    } else {
        return (($entry - $exit) / $entry) * 100;
    }
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

function isValidImageFile($file) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }
    
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return false;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    return in_array($mime_type, ALLOWED_IMAGE_TYPES);
}

function uploadImage($file, $directory) {
    if (!isValidImageFile($file)) {
        return false;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generateToken(16) . '.' . $extension;
    $upload_path = UPLOAD_PATH . $directory . '/' . $filename;
    
    if (!file_exists(dirname($upload_path))) {
        mkdir(dirname($upload_path), 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $directory . '/' . $filename;
    }
    
    return false;
}

function deleteFile($path) {
    $full_path = UPLOAD_PATH . $path;
    if (file_exists($full_path)) {
        return unlink($full_path);
    }
    return true;
}

function paginate($page, $per_page, $total) {
    $total_pages = ceil($total / $per_page);
    $current_page = max(1, min($page, $total_pages));
    $offset = ($current_page - 1) * $per_page;
    
    return [
        'current_page' => $current_page,
        'per_page' => $per_page,
        'total_pages' => $total_pages,
        'total_items' => $total,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}

function buildQueryString($params, $exclude = []) {
    $filtered = array_diff_key($params, array_flip($exclude));
    return http_build_query($filtered);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    
    return formatDate($datetime);
}

function debug($data, $die = false) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    if ($die) die();
}
?>