<?php

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$loginPath = ($basePath === '' ? '' : $basePath) . '/login';

header('Location: ' . $loginPath, true, 302);
exit;
