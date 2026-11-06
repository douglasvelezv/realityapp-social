<?php
require __DIR__ . '/includes/functions.php';
$me = current_user(); $meId = $me ? (int)$me['id'] : 0;
[$html, $next] = render_feed('explore', [], $meId, 0, 20);
// tendencias
$trends = db()->query("SELECT h.tag, COUNT(*) n FROM post_hashtags ph JOIN hashtags h ON h.id=ph.hashtag_id
                       GROUP BY h.id ORDER BY n DESC, h.id DESC LIMIT 8")->fetchAll();
ob_start(); ?>
<div class="widget"><h2>Tendencias</h2>
<?php if (!$trends): ?><div class="trend"><span class="c">Aún no hay tendencias.</span></div><?php endif; ?>
<?php foreach ($trends as $t): ?>
  <a class="trend" href="<?= e(base_url('/hashtag.php?tag=' . rawurlencode($t['tag']))) ?>">
    <div class="t">#<?= e($t['tag']) ?></div><div class="c"><?= (int)$t['n'] ?> publicaciones</div></a>
<?php endforeach; ?>
</div>
<?php $SIDEBAR = ob_get_clean();
$TITLE = 'Explorar'; $PAGE = 'explore';
require __DIR__ . '/includes/header.php'; ?>
<div data-stream>
<?php echo $html !== '' ? $html : '<div class="empty"><b>Todavía no hay publicaciones</b>¡Sé el primero en publicar!</div>'; ?>
</div>
<?php if ($next !== null): ?>
<button class="load-more" data-more="<?= e(base_url('/api/stream.php?type=explore&offset=' . $next)) ?>">Mostrar más</button>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
