<?php
//
// "$Id: globals.php,v 1.7 2004/05/20 12:31:54 mike Exp $"
//
// Global PHP constants and variables...
//
// This file should be included using "include_once"...
//

//
// Global vars...
//

$PROJECT_NAME = "Mini-XML";		// Title of project
$PROJECT_EMAIL = "mxml@easysw.com";	// Default notification address
$PROJECT_MODULE = "mxml";		// CVS module
$PAGE_MAX = 10; 			// Max items per page


//
// PHP transition stuff...
//

global $_COOKIE, $_FILES, $_GET, $_POST, $_SERVER;

$argc           = $_SERVER["argc"];
$argv           = $_SERVER["argv"];
$PHP_SELF       = $_SERVER["PHP_SELF"];
$REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
$SERVER_NAME    = $_SERVER["SERVER_NAME"];
$REMOTE_ADDR    = $_SERVER["REMOTE_ADDR"];

if (array_key_exists("ISHTTPS", $_SERVER))
  $PHP_URL = "https://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]$_SERVER[PHP_SELF]";
else
  $PHP_URL = "http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]$_SERVER[PHP_SELF]";

//
// End of "$Id: globals.php,v 1.7 2004/05/20 12:31:54 mike Exp $".
//
?>
