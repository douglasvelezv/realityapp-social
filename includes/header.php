<?php
// Cabecera y layout. Antes de incluir, define: $TITLE, $PAGE (clave activa), $me.
require_once __DIR__ . '/functions.php';
$me   = $me ?? current_user();
$TITLE = $TITLE ?? 'Reality Social';
$PAGE  = $PAGE ?? '';
$unread = $me ? unread_count((int)$me['id']) : 0;
$meU = $me ? user_by_id((int)$me['id']) : null;
$nav = [
  'home'    => ['Inicio', '/index.php', 'M3 11.5 12 4l9 7.5M5 10v9h5v-5h4v5h5v-9'],
  'explore' => ['Explorar', '/explore.php', 'M10.5 3a7.5 7.5 0 1 0 4.55 13.46l4.5 4.49 1.4-1.41-4.49-4.5A7.5 7.5 0 0 0 10.5 3Z'],
  'notif'   => ['Notificaciones', '/notifications.php', 'M12 3a6 6 0 0 0-6 6c0 5-2 6-2 6h16s-2-1-2-6a6 6 0 0 0-6-6ZM9.5 20a2.5 2.5 0 0 0 5 0'],
];
if ($me) $nav['bookmarks'] = ['Guardados', '/bookmarks.php', 'M6 3h12a1 1 0 0 1 1 1v17l-7-4.5L5 21V4a1 1 0 0 1 1-1Z'];
$nav['profile']  = ['Perfil', $me ? ('/profile.php?u=' . rawurlencode($meU['handle'] ?? '')) : '/auth/login.php', 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0'];
$nav['settings'] = ['Ajustes', '/settings.php', 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM19.4 13.5a7.9 7.9 0 0 0 0-3l2-1.5-2-3.4-2.3 1a7.9 7.9 0 0 0-2.6-1.5L14 2h-4l-.5 2.6A7.9 7.9 0 0 0 6.9 6l-2.3-1-2 3.4 2 1.5a7.9 7.9 0 0 0 0 3l-2 1.5 2 3.4 2.3-1a7.9 7.9 0 0 0 2.6 1.5L10 22h4l.5-2.6a7.9 7.9 0 0 0 2.6-1.5l2.3 1 2-3.4-2-1.5Z'];
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="color-scheme" content="dark light">
<script>(function(){try{var t=localStorage.getItem('rs-theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
<title><?= e($TITLE) ?></title>
<meta name="robots" content="noindex">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='%231d9bf0'/%3E%3C/svg%3E">
<link rel="stylesheet" href="<?= e(base_url('/assets/css/app.css?v=2')) ?>">
</head>
<body>
<script>window.RS = { base: <?= json_encode(base_url('')) ?>, csrf: <?= json_encode($me ? csrf_token() : '') ?>, me: <?= $me ? (int)$me['id'] : 0 ?> };</script>
<div class="app">
  <nav class="side-nav" aria-label="Principal">
    <a class="logo" href="<?= e(base_url('/')) ?>" aria-label="Reality Social"><span>R</span></a>
    <?php foreach ($nav as $k => $it): ?>
      <a class="nav-item <?= $PAGE === $k ? 'on' : '' ?>" href="<?= e(base_url($it[1])) ?>">
        <span class="ni-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $it[2] ?>"/></svg>
          <?php if ($k === 'notif' && $unread): ?><i class="badge"><?= $unread > 99 ? '99+' : $unread ?></i><?php endif; ?>
        </span>
        <span class="ni-tx"><?= e($it[0]) ?></span>
      </a>
    <?php endforeach; ?>
    <button class="theme-btn" data-theme-toggle title="Cambiar tema" aria-label="Cambiar tema">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/></svg>
      <span class="ni-tx tt-label">Modo claro</span>
    </button>
    <?php if ($me): ?>
      <button class="compose-btn" data-open-compose>Postear</button>
      <div class="me-card" tabindex="0">
        <?= avatar_html($meU, 40) ?>
        <span class="me-meta"><b><?= e($meU['name']) ?></b><small>@<?= e($meU['handle']) ?></small></span>
        <a class="me-out" href="<?= e(base_url('/auth/logout.php')) ?>" title="Cerrar sesión" aria-label="Cerrar sesión">⎋</a>
      </div>
    <?php else: ?>
      <a class="compose-btn" href="<?= e(base_url('/auth/login.php')) ?>">Entrar</a>
    <?php endif; ?>
  </nav>

  <main class="feed">
    <header class="feed-top">
      <h1><?= e($TITLE) ?></h1>
    </header>
