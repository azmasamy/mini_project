<?php

define("PRIVATE_PATH", dirname(__FILE__));
define("CONTACTS_LOG_PATH", '../' . PRIVATE_PATH);
define("GUI_PATH", CONTACTS_LOG_PATH . '/gui');

$public_end = strpos($_SERVER['SCRIPT_NAME'], '/contacts_log') + 13;
$doc_root = substr($_SERVER['SCRIPT_NAME'], 0, $public_end);
define("WWW_ROOT",'http://' . $_SERVER['SERVER_NAME'] .  $doc_root);

 ?>
