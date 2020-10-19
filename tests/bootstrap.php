<?php

// Set timezone
date_default_timezone_set('Asia/Tehran');

// Prevent session cookies
ini_set('session.use_cookies', 0);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/getallheaders.php';
require __DIR__ . '/Assets/PhpFunctionOverrides.php';
require __DIR__ . '/Assets/PhpHttpFunctionOverrides.php';
