<?php
//
// "$Id: globals.php,v 1.1 2004/05/17 23:02:35 mike Exp $"
//
// Global PHP variables...
//
// This file should be included using "include_once"...
//

//
// Global vars...
//

global $_COOKIE, $_FILES, $_POST, $_SERVER;

$argc           = $_SERVER["argc"];
$argv           = $_SERVER["argv"];
$PHP_SELF       = $_SERVER["PHP_SELF"];
$REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
$SERVER_NAME    = $_SERVER["SERVER_NAME"];

//
// End of "$Id: globals.php,v 1.1 2004/05/17 23:02:35 mike Exp $".
//
?>
