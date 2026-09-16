<?php

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    header('Location: ' . $path);
    exit;
}

function flash_set($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get()
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify()
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        die('Permintaan tidak valid (CSRF token tidak cocok). Silakan kembali dan coba lagi.');
    }
}

function is_valid_email($email)
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function is_valid_date($date)
{
    if ($date === null || $date === '') {
        return true;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    $parts = explode('-', $date);
    return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
}

function priority_label($priority)
{
    return match ($priority) {
        'low' => 'Rendah',
        'high' => 'Tinggi',
        default => 'Sedang',
    };
}

function priority_class($priority)
{
    return match ($priority) {
        'low' => 'badge badge-low',
        'high' => 'badge badge-high',
        default => 'badge badge-medium',
    };
}
