<?php
// Usa EXACTAMENTE la misma sesión que la tienda (cookie "ramsid", path "/"),
// así el inicio de sesión se comparte en todo realityapp.org. Aquí solo se LEE.
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('ramsid');
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Usuario conectado (identidad desde la base compartida) o null.
function current_user(): ?array {
    start_session();
    if (empty($_SESSION['uid'])) return null;
    $st = authdb()->prepare(
        'SELECT id, display_name, app_handle, avatar, email, google_uid, x_uid, x_handle
           FROM users WHERE id = ? AND status = "active"');
    $st->execute([$_SESSION['uid']]);
    $u = $st->fetch();
    if (!$u) return null;
    // "medio por el que se conectó", para mostrarlo en el perfil
    $u['medium'] = $u['google_uid'] ? 'google' : ($u['x_uid'] ? 'x' : 'correo');
    return $u;
}

function require_user(): array {
    $u = current_user();
    if (!$u) { header('Location: ' . base_url('/auth/login.php')); exit; }
    return $u;
}

// Asegura que exista la fila de perfil social del usuario (se crea al vuelo).
function ensure_profile(int $uid): void {
    db()->prepare('INSERT IGNORE INTO profiles (user_id) VALUES (?)')->execute([$uid]);
}

function base_url(string $path = ''): string {
    return rtrim(social_config()['base'] ?? '/social', '/') . $path;
}
