<?php
require __DIR__ . '/../includes/functions.php';
$u = current_user();
if (!$u) json_out(['error' => 'Inicia sesión.'], 401);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_out(['error' => 'Método no permitido.'], 405);
if (!same_origin()) json_out(['error' => 'Origen no permitido.'], 403);
if (!csrf_ok($_POST['csrf'] ?? null)) json_out(['error' => 'Sesión caducada, recarga.'], 419);
$me = (int)$u['id']; ensure_profile($me);

$bio = mb_substr(trim((string)($_POST['bio'] ?? '')), 0, 160);
$loc = mb_substr(trim((string)($_POST['location'] ?? '')), 0, 60);
$link = mb_substr(trim((string)($_POST['link'] ?? '')), 0, 120);
$live = mb_substr(trim((string)($_POST['live_url'] ?? '')), 0, 160);
// Solo aceptamos enlaces de transmisión http(s) (o vacío para quitar).
if ($live !== '' && !preg_match('#^https?://#i', $live)) $live = 'https://' . $live;
if ($live !== '' && !filter_var($live, FILTER_VALIDATE_URL)) json_out(['error' => 'El enlace de directo no es válido.'], 422);
db()->prepare('UPDATE profiles SET bio = ?, location = ?, link = ?, live_url = ? WHERE user_id = ?')
    ->execute([$bio ?: null, $loc ?: null, $link ?: null, $live ?: null, $me]);

if ($av = save_upload('avatar', 'avatars')) db()->prepare('UPDATE profiles SET avatar = ? WHERE user_id = ?')->execute([$av, $me]);
if ($bn = save_upload('banner', 'banners')) db()->prepare('UPDATE profiles SET banner = ? WHERE user_id = ?')->execute([$bn, $me]);
json_out(['ok' => true]);
