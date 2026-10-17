<?php
require_once __DIR__ . '/auth.php';
authLogout();
header('Location: login.php');
exit;
