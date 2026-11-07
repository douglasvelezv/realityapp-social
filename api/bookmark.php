<?php
// Guardar / quitar de guardados (privado del usuario).
require __DIR__ . '/../includes/functions.php';
[$u, $b] = require_write();
$me = (int)$u['id']; $id = (int)($b['id'] ?? 0);
$st = db()->prepare('SELECT 1 FROM posts WHERE id = ? AND deleted_at IS NULL'); $st->execute([$id]);
if (!$st->fetchColumn()) json_out(['error' => 'No existe.'], 404);

$ex = db()->prepare('SELECT 1 FROM bookmarks WHERE user_id = ? AND post_id = ?'); $ex->execute([$me, $id]);
if ($ex->fetchColumn()) {
    db()->prepare('DELETE FROM bookmarks WHERE user_id = ? AND post_id = ?')->execute([$me, $id]); $on = false;
} else {
    db()->prepare('INSERT INTO bookmarks (user_id, post_id) VALUES (?,?)')->execute([$me, $id]); $on = true;
}
json_out(['ok' => true, 'bookmarked' => $on]);
