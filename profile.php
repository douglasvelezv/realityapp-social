<?php
require __DIR__ . '/includes/functions.php';
$me = current_user(); $meId = $me ? (int)$me['id'] : 0;
$handle = ltrim((string)($_GET['u'] ?? ''), '@');
$u = $handle !== '' ? user_by_handle($handle) : null;

if (!$u) {
  $TITLE = 'Perfil'; $PAGE = 'profile';
  require __DIR__ . '/includes/header.php';
  echo '<div class="empty"><b>Esta cuenta no existe</b>Prueba a buscar otra.</div>';
  require __DIR__ . '/includes/footer.php'; exit;
}
$uid = (int)$u['id'];
$tab = in_array($_GET['tab'] ?? '', ['replies','media','likes'], true) ? $_GET['tab'] : 'posts';

// fecha de alta (base compartida)
$st = authdb()->prepare('SELECT created_at FROM users WHERE id = ?'); $st->execute([$uid]);
$joined = (string)$st->fetchColumn();

$cnt = fn($sql, $a) => (function($s,$a){ $q=db()->prepare($s); $q->execute($a); return (int)$q->fetchColumn(); })($sql,$a);
$nPosts = $cnt('SELECT COUNT(*) FROM posts WHERE user_id=? AND reply_to IS NULL AND deleted_at IS NULL', [$uid]);
$following = $cnt('SELECT COUNT(*) FROM follows WHERE follower_id=?', [$uid]);
$followers = $cnt('SELECT COUNT(*) FROM follows WHERE followee_id=?', [$uid]);
$iFollow = $meId && $cnt('SELECT COUNT(*) FROM follows WHERE follower_id=? AND followee_id=?', [$meId, $uid]) > 0;

// post fijado (solo en la pestaña Posts)
$pinnedId = (int)($u['pinned_post'] ?? 0);
$pinnedHtml = '';
if ($tab === 'posts' && $pinnedId) {
    $pst = db()->prepare('SELECT * FROM posts WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
    $pst->execute([$pinnedId, $uid]);
    if ($pp = $pst->fetch()) {
        $pu = users_by_ids([$uid]); $ps = post_stats([$pinnedId], $meId);
        $pinnedHtml = render_post($pp, $pu, $ps, $meId, true, true);
    } else { $pinnedId = 0; }
}

$type = ['replies'=>'user_replies','media'=>'user_media','likes'=>'user_likes'][$tab] ?? 'user';
$fp = ['uid' => $uid]; if ($type === 'user' && $pinnedId) $fp['exclude'] = $pinnedId;
[$html, $next] = render_feed($type, $fp, $meId, 0, 20);

$TITLE = $u['name']; $PAGE = 'profile';
require __DIR__ . '/includes/header.php';
$banner = upload_url('banners', $u['banner']);
$live = trim((string)($u['live_url'] ?? ''));
$liveUrl = $live !== '' ? (preg_match('#^https?://#', $live) ? $live : 'https://' . $live) : '';
?>
<div class="p-banner"><?php if ($banner): ?><img src="<?= e($banner) ?>" alt=""><?php endif; ?></div>
<div class="p-head">
  <div class="p-av"><?= avatar_html($u, 112) ?></div>
  <div class="p-actions">
    <?php if ($liveUrl): ?><a class="live-chip" href="<?= e($liveUrl) ?>" target="_blank" rel="noopener"><span class="pulse"></span>EN DIRECTO</a><?php endif; ?>
    <?php if ($meId === $uid): ?>
      <a class="btn-outline" href="<?= e(base_url('/settings.php')) ?>">Editar perfil</a>
    <?php elseif ($meId): ?>
      <button class="btn-follow <?= $iFollow ? 'ing' : '' ?>" data-follow="<?= $uid ?>"><?= $iFollow ? 'Siguiendo' : 'Seguir' ?></button>
    <?php else: ?>
      <a class="btn-follow" href="<?= e(base_url('/auth/login.php')) ?>">Seguir</a>
    <?php endif; ?>
  </div>
  <h2 class="p-name"><?= e($u['name']) ?><?= verified_badge($u) ?></h2>
  <div class="p-handle">@<?= e($u['handle']) ?> · entró con <?= e($u['medium']) ?></div>
  <?php if (!empty($u['bio'])): ?><div class="p-bio"><?= linkify($u['bio']) ?></div><?php endif; ?>
  <div class="p-meta">
    <?php if (!empty($u['location'])): ?><span>📍 <?= e($u['location']) ?></span><?php endif; ?>
    <?php if (!empty($u['link'])): ?><a href="<?= e(preg_match('#^https?://#', $u['link']) ? $u['link'] : 'https://' . $u['link']) ?>" target="_blank" rel="noopener nofollow">🔗 <?= e(preg_replace('#^https?://#', '', $u['link'])) ?></a><?php endif; ?>
    <a class="p-shop" href="/shop/" title="Tienda de la comunidad">🛍️ Reality APP Market</a>
    <span>📅 <?= e(date_long($joined)) ?></span>
  </div>
  <div class="p-stats">
    <a href="<?= e(base_url('/profile.php?u=' . rawurlencode($u['handle']) . '&tab=following')) ?>"><b><?= num($following) ?></b> <span>Siguiendo</span></a>
    <a href="<?= e(base_url('/profile.php?u=' . rawurlencode($u['handle']) . '&tab=followers')) ?>"><b><?= num($followers) ?></b> <span>Seguidores</span></a>
    <span><b><?= num($nPosts) ?></b> <span>Posts</span></span>
  </div>
</div>
<div class="tabs">
  <a class="<?= $tab==='posts'?'on':'' ?>" href="?u=<?= rawurlencode($u['handle']) ?>">Posts</a>
  <a class="<?= $tab==='replies'?'on':'' ?>" href="?u=<?= rawurlencode($u['handle']) ?>&tab=replies">Respuestas</a>
  <a class="<?= $tab==='media'?'on':'' ?>" href="?u=<?= rawurlencode($u['handle']) ?>&tab=media">Media</a>
  <a class="<?= $tab==='likes'?'on':'' ?>" href="?u=<?= rawurlencode($u['handle']) ?>&tab=likes">Me gusta</a>
</div>
<div data-stream>
<?php
  echo $pinnedHtml;
  if ($html !== '') echo $html;
  elseif ($pinnedHtml === '') echo '<div class="empty"><b>Nada por aquí todavía</b></div>';
?>
</div>
<?php if ($next !== null): ?>
<button class="load-more" data-more="<?= e(base_url('/api/stream.php?' . http_build_query(['type'=>$type,'uid'=>$uid,'offset'=>$next] + ($type==='user'&&$pinnedId?['exclude'=>$pinnedId]:[])))) ?>">Mostrar más</button>
<?php endif; ?>
<?php $SIDEBAR = render_aside($meId); require __DIR__ . '/includes/footer.php'; ?>
