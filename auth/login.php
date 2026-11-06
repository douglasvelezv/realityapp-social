<?php
require __DIR__ . '/../includes/functions.php';
if (current_user()) { header('Location: ' . base_url('/')); exit; }
require __DIR__ . '/../includes/gate.php';
