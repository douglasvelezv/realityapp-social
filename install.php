<?php
/**
 * Instalador idempotente de Reality Social.
 * Crea las tablas (CREATE TABLE IF NOT EXISTS) desde database.sql en la base propia.
 * Es seguro ejecutarlo varias veces. Bórralo del repo cuando ya no haga falta.
 */
require __DIR__ . '/includes/db.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $raw = file_get_contents(__DIR__ . '/database.sql');
    $raw = preg_replace('/^--.*$/m', '', (string)$raw);         // quita comentarios de línea
    $done = 0;
    foreach (array_filter(array_map('trim', explode(';', $raw))) as $stmt) {
        if ($stmt === '') continue;
        db()->exec($stmt);
        $done++;
    }
    $tables = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['ok' => true, 'statements' => $done, 'tables' => $tables], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
