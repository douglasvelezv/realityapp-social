<?php
// Base común de la API. Carga la configuración desde fuera del control de versiones.
declare(strict_types=1);
header('X-Content-Type-Options: nosniff');

function cfg(): array {
    static $c = null;
    if ($c === null) {
        $f = __DIR__ . '/../config/config.php';
        $c = is_file($f) ? (require $f) : [];
    }
    return $c;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $c = cfg();
        $pdo = new PDO(
            "mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4",
            $c['db_user'], $c['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

function json_out($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Sólo acepta escrituras desde el propio dominio
function require_same_origin(): void {
    $o = $_SERVER['HTTP_ORIGIN'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($o !== '' && parse_url($o, PHP_URL_HOST) !== $host) json_out(['error' => 'Origen no permitido'], 403);
}
