<?php
// Dos conexiones: la base PROPIA de social y la base COMPARTIDA del login.
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

function pdo_from(array $c): PDO {
    return new PDO(
        "mysql:host={$c['host']};dbname={$c['name']};charset=utf8mb4",
        $c['user'], $c['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false]
    );
}

// Base de datos propia de Reality Social (posts, likes, follows, etc.)
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) $pdo = pdo_from(social_config()['db']);
    return $pdo;
}

// Base compartida del login (solo lectura de la tabla users): identidad.
function authdb(): PDO {
    static $pdo = null;
    if ($pdo === null) $pdo = pdo_from(social_config()['auth']);
    return $pdo;
}
