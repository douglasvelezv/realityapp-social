<?php
require __DIR__ . '/includes/session.php';
require __DIR__ . '/includes/functions.php';
$me = current_user();
if ($me) ensure_profile((int)$me['id']);
?><!doctype html>
<html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Buscar · Reality Social</title><meta name="robots" content="noindex">
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#000;color:#e7e9ea;font:16px/1.6 system-ui,sans-serif;text-align:center;padding:24px}
.b{display:inline-block;padding:6px 14px;border-radius:999px;background:#16202a;color:#1d9bf0;font-weight:700;font-size:13px}
h1{font-size:clamp(26px,6vw,40px);margin:16px 0 6px}a{color:#1d9bf0}</style></head>
<body><main>
<span class="b">Reality Social · en construcción</span>
<h1>Buscar</h1>
<?php if ($me): ?>
  <p>Sesión iniciada como <b>@<?= e($me['app_handle'] ?: ('user'.$me['id'])) ?></b> (vía <?= e($me['medium']) ?>).</p>
<?php else: ?>
  <p>No has iniciado sesión. El login es el mismo de <a href="/shop/">la tienda</a>.</p>
<?php endif; ?>
<p style="color:#71767b">Esta sección se construye en los próximos pasos.</p>
</main></body></html>
