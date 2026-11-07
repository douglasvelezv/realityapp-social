<?php
// Pantalla de acceso de Reality Social. El login es COMPARTIDO con la tienda:
// se usa /api/auth.php (mismo dominio) y la sesión ramsid. Al entrar, se vuelve a social.
require_once __DIR__ . '/functions.php';
$back = base_url('/');
?><!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="dark"><title>Entrar · Reality Social</title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(base_url('/assets/css/app.css?v=1')) ?>">
</head><body>
<div class="gate">
  <div class="mark">R</div>
  <h1>Entra en Reality Social</h1>
  <p>Una sola cuenta para toda la comunidad Reality APP. Si ya tienes cuenta en la tienda, entra con la misma.</p>

  <div class="oauth" id="oauth"></div>
  <div class="divider">o con tu correo</div>

  <form id="loginForm" autocomplete="on">
    <input type="email" name="email" placeholder="Correo" required autocomplete="email">
    <input type="password" name="password" placeholder="Contraseña" required autocomplete="current-password">
    <button class="btn-primary" type="submit">Entrar</button>
  </form>

  <details id="reg" style="margin-top:14px;text-align:left">
    <summary style="cursor:pointer;color:var(--accent);text-align:center;list-style:none">Crear una cuenta nueva</summary>
    <form id="regForm" style="margin-top:10px">
      <input type="text" name="display_name" placeholder="Tu nombre o alias" required maxlength="40">
      <input type="text" name="app_handle" placeholder="Usuario (@handle)" maxlength="30">
      <input type="email" name="email" placeholder="Correo" required autocomplete="email">
      <input type="password" name="password" placeholder="Contraseña (mínimo 8)" required minlength="8" autocomplete="new-password">
      <button class="btn-primary" type="submit">Crear cuenta</button>
    </form>
  </details>

  <p id="msg" style="color:var(--like);min-height:20px;margin-top:12px"></p>
  <p class="divider"><a href="<?= e(base_url('/explore.php')) ?>" style="color:var(--accent)">Explorar sin cuenta</a> · <a href="/shop/" style="color:var(--muted)">Ir a la tienda</a></p>
</div>

<script src="/assets/google.js?v=2"></script>
<script>
(function(){
  var back = <?= json_encode($back) ?>;
  var msg = document.getElementById('msg');
  function err(m){ msg.textContent = m || 'No se pudo completar'; }
  function go(){ location.href = back; }

  // Botones de Google / X (reutilizan el login de la tienda)
  try {
    if (window.RAMGoogle) document.getElementById('oauth').appendChild(RAMGoogle.buttons(go, err));
  } catch(e){}

  async function send(action, data){
    const r = await fetch('/api/auth.php?action='+action, {method:'POST', credentials:'same-origin',
      headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)});
    let j={}; try{ j = await r.json(); }catch(e){}
    if(!r.ok) throw new Error(j.error || 'Error');
    return j;
  }
  document.getElementById('loginForm').addEventListener('submit', async function(e){
    e.preventDefault(); msg.textContent='';
    const f = e.target;
    try { await send('login', {email:f.email.value, password:f.password.value}); go(); }
    catch(ex){ err(ex.message); }
  });
  document.getElementById('regForm').addEventListener('submit', async function(e){
    e.preventDefault(); msg.textContent='';
    const f = e.target;
    try { await send('register', {display_name:f.display_name.value, app_handle:f.app_handle.value, email:f.email.value, password:f.password.value}); go(); }
    catch(ex){ err(ex.message); }
  });
})();
</script>
</body></html>
<?php exit;
