<?php
/*  chat-GPT ERP sign: sysempire@gmail.com  */

include_once __DIR__ . '/../common.php';

if (!defined('_GNUBOARD_')) exit;

if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = array();
    @session_destroy();
}

set_cookie('ck_mb_id', '', 0);
goto_url('/admin');
