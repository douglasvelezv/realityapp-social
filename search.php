<?php
require __DIR__ . '/includes/functions.php';
$me = current_user(); $meId = $me ? (int)$me['id'] : 0;
$q = trim((string)($_GET['q'] ?? ''));
if (str_starts_with($q, '#')) { header('Location: ' . base_url('/hashtag.php?tag=' . rawurlencode(ltrim($q, '#')))); exit; }

$users = []; $postsHtml = '';
if ($q !== '') {
  $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
  $st = authdb()->prepare("SELECT id FROM users WHERE status='active' AND (app_handle LIKE ? OR display_name LIKE ?) LIMIT 12");
  $st->execute([$like, $like]);
  $users = users_by_ids(array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)));

  $st = db()->prepare("SELECT id FROM posts WHERE deleted_at IS NULL AND body LIKE ? ORDER BY id DESC LIMIT 25");
  $st->execute([$like]);
  $refs = array_map(fn($x) => ['post_id' => (int)$x, 'repost_by' => null], $st->fetchAll(PDO::FETCH_COLUMN));
  $postsHtml = render_refs($refs, $meId);
}
$TITLE = $q !== '' ? 'Resultados' : 'Buscar'; $PAGE = 'explore';
require __DIR__ . '/includes/header.php'; ?>
<?php if ($q === ''): ?>
  <div class="empty"><b>Busca en Reality Social</b>Personas, publicaciones o #hashtags.</div>
<?php else: ?>
  <?php if ($users): ?>
    <div class="tabs"><a class="on">Personas</a></div>
    <?php foreach ($users as $u): ?>
      <div class="wrow">
        <a href="<?= e(base_url('/profile.php?u=' . rawurlencode($u['handle']))) ?>"><?= avatar_html($u, 44) ?></a>
        <a href="<?= e(base_url('/profile.php?u=' . rawurlencode($u['handle']))) ?>" style="min-width:0">
          <div class="nm"><?= e($u['name']) ?></div><div class="hd">@<?= e($u['handle']) ?></div></a>
        <?php if ($meId && $meId !== $u['id']): ?><button class="btn-follow" data-follow="<?= $u['id'] ?>">Seguir</button><?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
  <div class="tabs"><a class="on">Publicaciones</a></div>
  <div data-stream><?= $postsHtml !== '' ? $postsHtml : '<div class="empty"><span>Sin publicaciones para «' . e($q) . '».</span></div>' ?></div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
