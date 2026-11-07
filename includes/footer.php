<?php
// Cierra el layout. Opcional: define $SIDEBAR (HTML) antes de incluir.
$SIDEBAR = $SIDEBAR ?? '';
?>
  </main>

  <aside class="side-right" aria-label="Buscar y tendencias">
    <form class="search-box" action="<?= e(base_url('/search.php')) ?>" method="get" role="search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" name="q" placeholder="Buscar en Reality Social" value="<?= e($_GET['q'] ?? '') ?>" maxlength="80">
    </form>
    <?= $SIDEBAR ?>
    <div class="side-foot">
      <a href="/shop/">Tienda</a> · <a href="<?= e(base_url('/explore.php')) ?>">Explorar</a><br>
      Reality Social · comunidad Reality APP
    </div>
  </aside>
</div>

<!-- Modal para escribir -->
<?php if ($me): ?>
<div class="modal" id="composeModal" hidden>
  <div class="modal-card">
    <button class="modal-x" data-close-compose aria-label="Cerrar">✕</button>
    <?php $me2 = user_by_id((int)$me['id']); ?>
    <form class="composer" data-composer enctype="multipart/form-data">
      <?= avatar_html($me2, 44) ?>
      <div class="composer-main">
        <textarea name="body" maxlength="280" rows="3" placeholder="¿Qué está pasando?"></textarea>
        <div class="composer-preview" hidden><img alt=""><button type="button" class="rm-img" aria-label="Quitar imagen">✕</button></div>
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
  </div>
</div>
<?php endif; ?>

<div id="toast" aria-live="polite"></div>
<script src="<?= e(base_url('/assets/js/app.js?v=2')) ?>"></script>
</body>
</html>
