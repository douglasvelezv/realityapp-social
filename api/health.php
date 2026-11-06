<?php
require __DIR__ . '/../includes/session.php';
header('Content-Type: application/json; charset=utf-8');
$out = ['ok' => true, 'service' => 'realityapp-social'];
try { db()->query('SELECT 1'); $out['db_social'] = true; } catch (Throwable $e) { $out['db_social'] = false; }
try { authdb()->query('SELECT 1 FROM users LIMIT 1'); $out['db_login'] = true; } catch (Throwable $e) { $out['db_login'] = false; }
$u = current_user();
$out['logged_in'] = (bool)$u;
if ($u) $out['as'] = ['handle' => $u['app_handle'], 'medium' => $u['medium']];
echo json_encode($out, JSON_UNESCAPED_UNICODE);
