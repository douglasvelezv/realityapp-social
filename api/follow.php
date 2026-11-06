<?php
require __DIR__ . '/../includes/functions.php';
[$u, $b] = require_write();
$me = (int)$u['id']; $target = (int)($b['id'] ?? 0);
if ($target === $me) json_out(['error' => 'No puedes seguirte a ti mismo.'], 422);
$chk = authdb()->prepare('SELECT 1 FROM users WHERE id = ? AND status = "active"'); $chk->execute([$target]);
if (!$chk->fetchColumn()) json_out(['error' => 'Usuario no encontrado.'], 404);
$ex = db()->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND followee_id = ?'); $ex->execute([$me, $target]);
if ($ex->fetchColumn()) {
    db()->prepare('DELETE FROM follows WHERE follower_id = ? AND followee_id = ?')->execute([$me, $target]); $f = false;
} else {
    db()->prepare('INSERT INTO follows (follower_id, followee_id) VALUES (?,?)')->execute([$me, $target]); $f = true;
    notify($target, $me, 'follow', null);
}
json_out(['ok' => true, 'following' => $f]);
