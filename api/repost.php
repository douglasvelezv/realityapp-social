<?php
require __DIR__ . '/../includes/functions.php';
[$u, $b] = require_write();
$me = (int)$u['id']; $id = (int)($b['id'] ?? 0);
$st = db()->prepare('SELECT user_id FROM posts WHERE id = ? AND deleted_at IS NULL'); $st->execute([$id]);
$author = $st->fetchColumn();
if ($author === false) json_out(['error' => 'No existe.'], 404);
$ex = db()->prepare('SELECT 1 FROM reposts WHERE user_id = ? AND post_id = ?'); $ex->execute([$me, $id]);
if ($ex->fetchColumn()) {
    db()->prepare('DELETE FROM reposts WHERE user_id = ? AND post_id = ?')->execute([$me, $id]); $on = false;
} else {
    db()->prepare('INSERT INTO reposts (user_id, post_id) VALUES (?,?)')->execute([$me, $id]); $on = true;
    notify((int)$author, $me, 'repost', $id);
}
$c = db()->prepare('SELECT COUNT(*) FROM reposts WHERE post_id = ?'); $c->execute([$id]);
json_out(['ok' => true, 'reposted' => $on, 'count' => (int)$c->fetchColumn()]);
