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
    db()->prepare('UPDATE profiles SET pinned_post = NULL WHERE user_id = ? AND pinned_post = ?')->execute([$me, $id]);
    json_out(['ok' => true]);
}

if ($action === 'pin') {
    $id = (int)($b['id'] ?? 0);
    $st = db()->prepare('SELECT user_id, reply_to FROM posts WHERE id = ? AND deleted_at IS NULL'); $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) json_out(['error' => 'No existe.'], 404);
    if ((int)$row['user_id'] !== $me) json_out(['error' => 'Solo puedes fijar tus publicaciones.'], 403);
    ensure_profile($me);
    $cur = db()->prepare('SELECT pinned_post FROM profiles WHERE user_id = ?'); $cur->execute([$me]);
    $now = (int)$cur->fetchColumn();
    $newVal = ($now === $id) ? null : $id;   // si ya estaba fijado, se suelta
    db()->prepare('UPDATE profiles SET pinned_post = ? WHERE user_id = ?')->execute([$newVal, $me]);
    json_out(['ok' => true, 'pinned' => $newVal !== null]);
}

// crear post, respuesta o cita
$body = trim(mb_substr((string)($b['body'] ?? ($_POST['body'] ?? '')), 0, 280));
$replyTo = (int)($b['reply_to'] ?? ($_POST['reply_to'] ?? 0)) ?: null;
$quoteOf = (int)($b['quote_of'] ?? ($_POST['quote_of'] ?? 0)) ?: null;
$image = save_upload('image', 'posts');   // null si no hay
if ($body === '' && !$image && !$quoteOf) json_out(['error' => 'Escribe algo o añade una imagen.'], 422);

$parentAuthor = null;
if ($replyTo) {
    $st = db()->prepare('SELECT user_id FROM posts WHERE id = ? AND deleted_at IS NULL'); $st->execute([$replyTo]);
    $parentAuthor = $st->fetchColumn();
    if ($parentAuthor === false) json_out(['error' => 'La publicación ya no existe.'], 404);
    $parentAuthor = (int)$parentAuthor;
}
$quoteAuthor = null;
if ($quoteOf) {
    $st = db()->prepare('SELECT user_id FROM posts WHERE id = ? AND deleted_at IS NULL'); $st->execute([$quoteOf]);
    $quoteAuthor = $st->fetchColumn();
    if ($quoteAuthor === false) json_out(['error' => 'La publicación citada ya no existe.'], 404);
    $quoteAuthor = (int)$quoteAuthor;
}

db()->prepare('INSERT INTO posts (user_id, body, image, reply_to, repost_of) VALUES (?,?,?,?,?)')
    ->execute([$me, $body, $image, $replyTo, $quoteOf]);
$id = (int)db()->lastInsertId();
process_post_text($id, $me, $body);
if ($parentAuthor) notify($parentAuthor, $me, 'reply', $id);
if ($quoteAuthor) notify($quoteAuthor, $me, 'repost', $id);

$st = db()->prepare('SELECT * FROM posts WHERE id = ?'); $st->execute([$id]); $p = $st->fetch();
$users = users_by_ids([$me]); $stats = post_stats([$id], $me);
json_out(['ok' => true, 'id' => $id, 'html' => render_post($p, $users, $stats, $me)]);
