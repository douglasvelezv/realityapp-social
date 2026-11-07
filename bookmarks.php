<?php
require __DIR__ . '/includes/functions.php';
$me = current_user();
if (!$me) { header('Location: ' . base_url('/auth/login.php')); exit; }
$meId = (int)$me['id'];
[$html, $next] = render_feed('bookmarks', ['uid' => $meId], $meId, 0, 20);
$TITLE = 'Guardados'; $PAGE = 'bookmarks';
require __DIR__ . '/includes/header.php';
?>
<div data-stream>
<?php if ($html === ''): ?>
  <div class="empty"><b>Aún no has guardado nada</b>Toca el icono de guardar en una publicación para tenerla aquí.</div>
<?php else: echo $html; endif; ?>
</div>
<?php if ($next !== null): ?>
<button class="load-more" data-more="<?= e(base_url('/api/stream.php?type=bookmarks&offset=' . $next)) ?>">Mostrar más</button>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
