<?php
require __DIR__ . '/../includes/functions.php';
[$u, $b] = require_write();
$me = (int)$u['id'];
$action = $b['action'] ?? 'create';

if ($action === 'delete') {
    $id = (int)($b['id'] ?? 0);
    $st = db()->prepare('SELECT user_id FROM posts WHERE id = ? AND deleted_at IS NULL'); $st->execute([$id]);
    $owner = $st->fetchColumn();
    if ($owner === false) json_out(['error' => 'No existe.'], 404);
    if ((int)$owner !== $me) json_out(['error' => 'No puedes borrar esto.'], 403);
    db()->prepare('UPDATE posts SET deleted_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$id]);
    json_out(['ok' => true]);
}

// crear post o respuesta
$body = trim(mb_substr((string)($b['body'] ?? ($_POST['body'] ?? '')), 0, 280));
$replyTo = (int)($b['reply_to'] ?? ($_POST['reply_to'] ?? 0)) ?: null;
$image = save_upload('image', 'posts');   // null si no hay
if ($body === '' && !$image) json_out(['error' => 'Escribe algo o añade una imagen.'], 422);

$parentAuthor = null;
if ($replyTo) {
    $st = db()->prepare('SELECT user_id FROM posts WHERE id = ? AND deleted_at IS NULL'); $st->execute([$replyTo]);
    $parentAuthor = $st->fetchColumn();
    if ($parentAuthor === false) json_out(['error' => 'La publicación ya no existe.'], 404);
    $parentAuthor = (int)$parentAuthor;
}

db()->prepare('INSERT INTO posts (user_id, body, image, reply_to) VALUES (?,?,?,?)')
    ->execute([$me, $body, $image, $replyTo]);
$id = (int)db()->lastInsertId();
process_post_text($id, $me, $body);
if ($parentAuthor) notify($parentAuthor, $me, 'reply', $id);

$st = db()->prepare('SELECT * FROM posts WHERE id = ?'); $st->execute([$id]); $p = $st->fetch();
$users = users_by_ids([$me]); $stats = post_stats([$id], $me);
json_out(['ok' => true, 'id' => $id, 'html' => render_post($p, $users, $stats, $me)]);
