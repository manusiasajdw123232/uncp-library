<?php
session_name('uncp_library_session');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$pdo = require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../middleware/auth.php';

require_once __DIR__ . '/../helpers/library.php';
