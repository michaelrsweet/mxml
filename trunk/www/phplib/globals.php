<?php
//
// "$Id$"
//
// Global PHP constants and variables...
//
// This file should be included using "include_once"...
//

//
// Global vars...
//

$PROJECT_NAME = "Mini-XML";		// Title of project
$PROJECT_EMAIL = "webmaster@minixml.org";
					// Default notification address
$PROJECT_REGISTER = "webmaster@minixml.org";
					// User registration email
$PROJECT_MODULE = "mxml";		// CVS module
$PAGE_MAX = 10; 			// Max items per page


//
// PHP transition stuff...
//

global $_COOKIE, $_FILES, $_POST, $_SERVER;

$argc           = $_SERVER["argc"];
$argv           = $_SERVER["argv"];
$REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
$SERVER_NAME    = $_SERVER["SERVER_NAME"];
$REMOTE_ADDR    = $_SERVER["REMOTE_ADDR"];

// Handle PHP_SELF differently - we need to quote it properly...
if (array_key_exists("PHP_SELF", $_SERVER))
  $PHP_SELF = htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES);
else
  $PHP_SELF = "";

if (array_key_exists("ISHTTPS", $_SERVER))
  $PHP_URL = "https://$_SERVER[SERVER_NAME]$_SERVER[PHP_SELF]";
else
  $PHP_URL = "http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]$_SERVER[PHP_SELF]";

//
// End of "$Id$".
//
?>
