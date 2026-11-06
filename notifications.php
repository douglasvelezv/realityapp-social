<?php
require __DIR__ . '/includes/functions.php';
$me = current_user();
if (!$me) { header('Location: ' . base_url('/auth/login.php')); exit; }
$meId = (int)$me['id'];
$rows = db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 50');
$rows->execute([$meId]); $rows = $rows->fetchAll();
db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$meId]);
$actors = users_by_ids(array_map(fn($r) => (int)$r['actor_id'], $rows));
$verbs = ['follow'=>'te empezó a seguir','like'=>'le dio me gusta a tu post','reply'=>'respondió a tu post','mention'=>'te mencionó','repost'=>'reposteó tu post'];
$TITLE = 'Notificaciones'; $PAGE = 'notif';
require __DIR__ . '/includes/header.php'; ?>
<div>
<?php if (!$rows): ?><div class="empty"><b>Sin notificaciones todavía</b>Cuando alguien interactúe contigo, aparecerá aquí.</div><?php endif; ?>
<?php foreach ($rows as $n): $a = $actors[(int)$n['actor_id']] ?? null; if (!$a) continue;
  $href = $n['post_id'] ? base_url('/post.php?id=' . (int)$n['post_id']) : base_url('/profile.php?u=' . rawurlencode($a['handle'])); ?>
  <a class="wrow" href="<?= e($href) ?>" style="border-bottom:1px solid var(--line)">
    <?= avatar_html($a, 40) ?>
    <div style="min-width:0"><span class="nm" style="display:inline"><?= e($a['name']) ?></span>
      <span class="hd"><?= e($verbs[$n['type']] ?? 'interactuó') ?> · <?= e(time_ago($n['created_at'])) ?></span></div>
  </a>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
