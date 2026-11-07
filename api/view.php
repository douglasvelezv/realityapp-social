<?php
// Registra visualizaciones reales: una única por usuario y post (no infla contadores).
// Solo cuenta a usuarios con sesión; acepta una lista de ids (impresiones del feed).
require __DIR__ . '/../includes/functions.php';
$u = current_user();
if (!$u) json_out(['ok' => true, 'counted' => 0]);          // sin sesión no se cuenta
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_out(['error' => 'Método no permitido.'], 405);
if (!same_origin()) json_out(['error' => 'Origen no permitido.'], 403);
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = $_POST;

$me = (int)$u['id'];
$ids = $body['ids'] ?? [];
if (!is_array($ids)) $ids = [];
$ids = array_slice(array_values(array_unique(array_filter(array_map('intval', $ids)))), 0, 50);
if (!$ids) json_out(['ok' => true, 'counted' => 0]);

$st = db()->prepare('INSERT IGNORE INTO post_views (post_id, user_id) VALUES (?, ?)');
$n = 0;
foreach ($ids as $id) { $st->execute([$id, $me]); $n += $st->rowCount(); }
json_out(['ok' => true, 'counted' => $n]);
