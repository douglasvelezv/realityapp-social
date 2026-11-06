<?php
require __DIR__ . '/includes/functions.php';
$me = current_user();
if (!$me) require __DIR__ . '/includes/gate.php';
$meId = (int)$me['id'];
ensure_profile($meId);

// a quién sigo (+ yo mismo)
$st = db()->prepare('SELECT followee_id FROM follows WHERE follower_id = ?');
$st->execute([$meId]);
$ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
$ids[] = $meId;

[$html, $next] = render_feed('home', ['ids' => $ids], $meId, 0, 20);
$TITLE = 'Inicio'; $PAGE = 'home';
require __DIR__ . '/includes/header.php';
$meU = user_by_id($meId);
?>
<form class="composer" data-composer enctype="multipart/form-data">
  <?= avatar_html($meU, 44) ?>
  <div class="composer-main">
    <textarea name="body" maxlength="280" rows="2" placeholder="¿Qué está pasando?"></textarea>
    <div class="composer-preview" hidden><img alt=""><button type="button" class="rm-img" aria-label="Quitar">✕</button></div>
    <div class="composer-bar">
      <label class="icon-btn" title="Añadir imagen">
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
      </label>
      <span class="count" data-count>280</span>
      <button class="btn-primary" type="submit" data-submit disabled>Postear</button>
    </div>
  </div>
</form>

<div data-stream>
<?php if ($html === ''): ?>
  <div class="empty"><b>Tu inicio está vacío</b>Sigue a gente o publica tu primer post. También puedes ir a <a href="<?= e(base_url('/explore.php')) ?>" style="color:var(--accent)">Explorar</a>.</div>
<?php else: echo $html; endif; ?>
</div>
<?php if ($next !== null): ?>
<button class="load-more" data-more="<?= e(base_url('/api/stream.php?type=home&offset=' . $next)) ?>">Mostrar más</button>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
