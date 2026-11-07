<?php
require __DIR__ . '/includes/functions.php';
$me = current_user();
if (!$me) { header('Location: ' . base_url('/auth/login.php')); exit; }
$meId = (int)$me['id']; ensure_profile($meId);
$u = user_by_id($meId);
$TITLE = 'Ajustes'; $PAGE = 'settings';
require __DIR__ . '/includes/header.php'; ?>
<form class="set-form" data-profile enctype="multipart/form-data" style="padding:16px;max-width:560px">
  <div class="p-banner" style="border-radius:16px;margin-bottom:56px">
    <?php $b = upload_url('banners', $u['banner']); if ($b): ?><img src="<?= e($b) ?>" alt=""><?php endif; ?>
    <label class="icon-btn" style="position:absolute;right:10px;top:10px;background:rgba(0,0,0,.6);color:#fff;width:40px;height:40px">
      <input type="file" name="banner" accept="image/*" hidden>✎</label>
    <div class="p-av" style="left:16px"><?= avatar_html($u, 96) ?>
      <label class="icon-btn" style="position:absolute;inset:0;margin:auto;background:rgba(0,0,0,.45);color:#fff;width:40px;height:40px">
        <input type="file" name="avatar" accept="image/*" hidden>✎</label>
    </div>
  </div>
  <label class="lbl">Nombre</label>
  <input class="in" value="<?= e($u['name']) ?>" disabled>
  <label class="lbl">Usuario</label>
  <input class="in" value="@<?= e($u['handle']) ?>" disabled>
  <small style="color:var(--muted)">El nombre y el usuario se gestionan en tu cuenta (son los mismos en toda la comunidad).</small>
  <label class="lbl">Biografía</label>
  <textarea class="in" name="bio" maxlength="160" rows="3"><?= e($u['bio']) ?></textarea>
  <label class="lbl">Ubicación</label>
  <input class="in" name="location" maxlength="60" value="<?= e($u['location']) ?>">
  <label class="lbl">Enlace</label>
  <input class="in" name="link" maxlength="120" value="<?= e($u['link']) ?>" placeholder="tusitio.com">
  <label class="lbl">En directo (Twitch / YouTube)</label>
  <input class="in" name="live_url" maxlength="160" value="<?= e($u['live_url'] ?? '') ?>" placeholder="twitch.tv/tucanal — se mostrará un botón EN DIRECTO en tu perfil">
  <small style="color:var(--muted)">Pega el enlace de tu canal cuando transmitas. Déjalo vacío para ocultar el botón.</small>
  <button class="btn-primary" type="submit" style="margin-top:16px">Guardar</button>
  <p data-set-msg style="min-height:18px;color:var(--accent)"></p>
</form>
<style>
.set-form .lbl{display:block;margin:14px 0 4px;color:var(--muted);font-size:13px}
.set-form .in{width:100%;padding:12px;border-radius:8px;border:1px solid var(--line);background:#000;color:var(--text);font:inherit}
.set-form .in:disabled{opacity:.6}
</style>
<script>
document.querySelector('[data-profile]').addEventListener('submit', async function(e){
  e.preventDefault(); const f = e.target, msg = f.querySelector('[data-set-msg]');
  const fd = new FormData(f); fd.append('csrf', window.RS.csrf);
  try {
    const r = await fetch(window.RS.base + '/api/profile.php', {method:'POST', headers:{'X-Requested-With':'fetch'}, body: fd});
    const j = await r.json(); if(!r.ok) throw new Error(j.error||'Error');
    msg.textContent = 'Guardado ✓'; setTimeout(()=>location.reload(), 600);
  } catch(ex){ msg.style.color='var(--like)'; msg.textContent = ex.message; }
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
