<?php
require __DIR__ . '/includes/functions.php';
$me = current_user(); $meId = $me ? (int)$me['id'] : 0;
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM posts WHERE id = ? AND deleted_at IS NULL'); $st->execute([$id]);
$p = $st->fetch();

$TITLE = 'Publicación'; $PAGE = '';
require __DIR__ . '/includes/header.php';
if (!$p) { echo '<div class="empty"><b>Esta publicación no existe</b>Puede que se haya eliminado.</div>'; require __DIR__ . '/includes/footer.php'; exit; }

// contexto: post padre (un nivel)
$parentHtml = '';
if ($p['reply_to']) {
  $q = db()->prepare('SELECT * FROM posts WHERE id = ? AND deleted_at IS NULL'); $q->execute([(int)$p['reply_to']]);
  if ($pp = $q->fetch()) {
    $us = users_by_ids([(int)$pp['user_id']]); $sstat = post_stats([(int)$pp['id']], $meId);
    $parentHtml = render_post($pp, $us, $sstat, $meId);
  }
}
// post principal
$us = users_by_ids([(int)$p['user_id']]); $sstat = post_stats([(int)$p['id']], $meId);
$au = $us[(int)$p['user_id']];
[$replies, $next] = render_feed('replies', ['post' => $id], $meId, 0, 30);
?>
<?php if ($parentHtml): ?><div class="thread-parent"><?= $parentHtml ?></div><?php endif; ?>

<article class="post post-single">
  <div class="post-head-lg">
    <a href="<?= e(base_url('/profile.php?u=' . rawurlencode($au['handle']))) ?>"><?= avatar_html($au, 48) ?></a>
    <div>
      <a class="post-name" href="<?= e(base_url('/profile.php?u=' . rawurlencode($au['handle']))) ?>"><?= e($au['name']) ?></a>
      <div class="post-handle">@<?= e($au['handle']) ?></div>
    </div>
    <?php if ($meId === (int)$p['user_id']): ?><button class="post-del" data-del="<?= $id ?>" aria-label="Eliminar" style="margin-left:auto"><?= icon('trash') ?></button><?php endif; ?>
  </div>
  <?php if (trim((string)$p['body']) !== ''): ?><div class="post-body-lg"><?= linkify($p['body']) ?></div><?php endif; ?>
  <?php $img = upload_url('posts', $p['image']); if ($img): ?><a class="post-img" href="<?= e($img) ?>" target="_blank"><img src="<?= e($img) ?>" alt=""></a><?php endif; ?>
  <div class="post-time-lg"><?= e(date('H:i', strtotime($p['created_at'].' UTC'))) ?> · <?= e(date('j M Y', strtotime($p['created_at'].' UTC'))) ?></div>
  <?php $s = $sstat[$id]; ?>
  <div class="post-acts post-acts-lg">
    <span class="act"><?= icon('reply') ?><span><?= num($s['replies']) ?></span></span>
    <button class="act act-repost <?= $s['reposted']?'on':'' ?>" data-repost="<?= $id ?>"><?= icon('repost') ?><span><?= num($s['reposts']) ?></span></button>
    <button class="act act-like <?= $s['liked']?'on':'' ?>" data-like="<?= $id ?>"><?= $s['liked']?icon('liked'):icon('like') ?><span><?= num($s['likes']) ?></span></button>
  </div>
</article>

<?php if ($me): $meU = user_by_id($meId); ?>
<form class="composer reply-composer" data-composer enctype="multipart/form-data">
  <input type="hidden" name="reply_to" value="<?= $id ?>">
  <?= avatar_html($meU, 44) ?>
  <div class="composer-main">
    <textarea name="body" maxlength="280" rows="1" placeholder="Publica tu respuesta"></textarea>
    <div class="composer-preview" hidden><img alt=""><button type="button" class="rm-img">✕</button></div>
    <div class="composer-bar">
      <label class="icon-btn"><input type="file" name="image" accept="image/*" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></label>
      <span class="count" data-count>280</span>
      <button class="btn-primary" type="submit" data-submit disabled>Responder</button>
    </div>
  </div>
</form>
<?php else: ?>
<div class="flash"><a href="<?= e(base_url('/auth/login.php')) ?>" style="color:var(--accent)">Inicia sesión</a> para responder.</div>
<?php endif; ?>

<div data-stream>
<?php echo $replies !== '' ? $replies : '<div class="empty" style="padding:28px"><span>Aún no hay respuestas.</span></div>'; ?>
</div>
<?php if ($next !== null): ?><button class="load-more" data-more="<?= e(base_url('/api/stream.php?type=replies&post=' . $id . '&offset=' . $next)) ?>">Mostrar más</button><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
