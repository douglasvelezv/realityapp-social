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
    // --- Migraciones idempotentes: añade columnas si faltan ---
    $hasCol = function (string $table, string $col): bool {
        $q = db()->prepare('SELECT COUNT(*) FROM information_schema.columns
                            WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
        $q->execute([$table, $col]);
        return (int)$q->fetchColumn() > 0;
    };
    $migrated = [];
    $cols = [
        ['profiles', 'pinned_post', 'ALTER TABLE profiles ADD COLUMN pinned_post BIGINT UNSIGNED NULL AFTER banner'],
        ['profiles', 'verified',    'ALTER TABLE profiles ADD COLUMN verified TINYINT(1) NOT NULL DEFAULT 0 AFTER pinned_post'],
        ['profiles', 'live_url',    'ALTER TABLE profiles ADD COLUMN live_url VARCHAR(160) NULL AFTER verified'],
    ];
    foreach ($cols as [$t, $c, $ddl]) {
        if (!$hasCol($t, $c)) { db()->exec($ddl); $migrated[] = "$t.$c"; }
    }

    $tables = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['ok' => true, 'statements' => $done, 'migrated' => $migrated, 'tables' => $tables], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
