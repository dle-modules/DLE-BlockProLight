<?php
/*
=============================================================================
BLockProLight
=============================================================================
Автор:   ПафНутиЙ
URL:     https://git.io/JflGt
=============================================================================
 */

if (!defined('DATALIFEENGINE')) {
    header("HTTP/1.1 403 Forbidden");
    header('Location: ../../');
    die("Hacking attempt!");
}

include_once ENGINE_DIR.'/classes/plugins.class.php';

include(DLEPlugins::Check(ENGINE_DIR.'/modules/base/blockpro.light.inc.php'));