<?php
require __DIR__ . '/_bootstrap.php';
$c = cfg();
$out = ['ok' => true, 'service' => basename(dirname(__DIR__)), 'config' => !empty($c)];
try { if (!empty($c)) { db()->query('SELECT 1'); $out['db'] = true; } }
catch (Throwable $e) { $out['db'] = false; }
json_out($out);
