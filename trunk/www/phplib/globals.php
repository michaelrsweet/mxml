<?php
//
// "$Id: globals.php,v 1.2 2004/05/19 00:57:33 mike Exp $"
//
// Global PHP variables...
//
// This file should be included using "include_once"...
//

//
// Global vars...
//

$PROJECT = "Mini-XML";			// Title of project
$EMAIL = "mxml@easysw.com";		// Default notification address
$PAGE_MAX = 10; 			// Max STRs per page

global $_COOKIE, $_FILES, $_POST, $_SERVER;

$argc           = $_SERVER["argc"];
$argv           = $_SERVER["argv"];
$PHP_SELF       = $_SERVER["PHP_SELF"];
$REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
$SERVER_NAME    = $_SERVER["SERVER_NAME"];


//
// End of "$Id: globals.php,v 1.2 2004/05/19 00:57:33 mike Exp $".
//
?>
