<?php
require __DIR__ . '/includes/functions.php';
$me = current_user(); $meId = $me ? (int)$me['id'] : 0;
$tag = ltrim((string)($_GET['tag'] ?? ''), '#');
[$html, $next] = $tag !== '' ? render_feed('hashtag', ['tag' => $tag], $meId, 0, 20) : ['', null];
$TITLE = '#' . $tag; $PAGE = 'explore';
require __DIR__ . '/includes/header.php'; ?>
<div data-stream>
<?php echo $html !== '' ? $html : '<div class="empty"><b>No hay publicaciones con #' . e($tag) . '</b></div>'; ?>
</div>
<?php if ($next !== null): ?><button class="load-more" data-more="<?= e(base_url('/api/stream.php?type=hashtag&tag=' . rawurlencode($tag) . '&offset=' . $next)) ?>">Mostrar más</button><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
