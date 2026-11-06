<?php
require __DIR__ . '/../includes/functions.php';
[$u, $b] = require_write();
$me = (int)$u['id']; $id = (int)($b['id'] ?? 0);
$st = db()->prepare('SELECT user_id FROM posts WHERE id = ? AND deleted_at IS NULL'); $st->execute([$id]);
$author = $st->fetchColumn();
if ($author === false) json_out(['error' => 'No existe.'], 404);
$ex = db()->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?'); $ex->execute([$me, $id]);
if ($ex->fetchColumn()) {
    db()->prepare('DELETE FROM likes WHERE user_id = ? AND post_id = ?')->execute([$me, $id]); $liked = false;
} else {
    db()->prepare('INSERT INTO likes (user_id, post_id) VALUES (?,?)')->execute([$me, $id]); $liked = true;
    notify((int)$author, $me, 'like', $id);
}
$c = db()->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?'); $c->execute([$id]);
json_out(['ok' => true, 'liked' => $liked, 'count' => (int)$c->fetchColumn()]);
