<?php
require __DIR__ . '/includes/functions.php';
$me = current_user(); $meId = $me ? (int)$me['id'] : 0;
[$html, $next] = render_feed('explore', [], $meId, 0, 20);
$SIDEBAR = render_aside($meId);
$TITLE = 'Explorar'; $PAGE = 'explore';
require __DIR__ . '/includes/header.php'; ?>
<div data-stream>
<?php echo $html !== '' ? $html : '<div class="empty"><b>Todavía no hay publicaciones</b>¡Sé el primero en publicar!</div>'; ?>
</div>
<?php if ($next !== null): ?>
<button class="load-more" data-more="<?= e(base_url('/api/stream.php?type=explore&offset=' . $next)) ?>">Mostrar más</button>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
