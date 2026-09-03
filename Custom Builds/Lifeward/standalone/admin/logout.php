<?php
require_once __DIR__ . '/../inc/auth.php';
lw_start_session();
$_SESSION = array();
session_destroy();
header( 'Location: login.php' ); exit;
