<?php
// El login es COMPARTIDO con la tienda. Por ahora enviamos al login existente;
// en el paso 3 se integra el botón de Google/X dentro de Reality Social.
require __DIR__ . '/../includes/session.php';
if (current_user()) { header('Location: ' . base_url('/')); exit; }
header('Location: /shop/#acceder');
