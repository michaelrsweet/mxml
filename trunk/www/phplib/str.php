<?php
//
// "$Id: str.php,v 1.2 2004/05/19 00:57:33 mike Exp $"
//
// Common STR definitions...
//
// This file should be included using "include_once"...
//

//
// STR constants...
//

$STR_STATUS_RESOLVED   = 1;
$STR_STATUS_UNRESOLVED = 2;
$STR_STATUS_ACTIVE     = 3;
$STR_STATUS_PENDING    = 4;
$STR_STATUS_NEW        = 5;

$STR_PRIORITY_RFE      = 1;
$STR_PRIORITY_LOW      = 2;
$STR_PRIORITY_MODERATE = 3;
$STR_PRIORITY_HIGH     = 4;
$STR_PRIORITY_CRITICAL = 5;

$STR_SCOPE_UNIT        = 1;
$STR_SCOPE_FUNCTION    = 2;
$STR_SCOPE_SOFTWARE    = 3;

//
// String definitions for STR constants...
//

$status_text = array(
  1 => "Resolved",
  2 => "Unresolved",
  3 => "Active",
  4 => "Pending",
  5 => "New"
);

$status_long = array(
  1 => "1 - Closed w/Resolution",
  2 => "2 - Closed w/o Resolution",
  3 => "3 - Active",
  4 => "4 - Pending",
  5 => "5 - New"
);

$priority_text = array(
  1 => "RFE",
  2 => "LOW",
  3 => "MODERATE",
  4 => "HIGH",
  5 => "CRITICAL"
);

$priority_long = array(
  1 => "1 - Request for Enhancement, e.g. asking for a feature",
  2 => "2 - Low, e.g. a documentation error or undocumented side-effect",
  3 => "3 - Moderate, e.g. unable to compile the software",
  4 => "4 - High, e.g. key functionality not working",
  5 => "5 - Critical, e.g. nothing working at all"
);

$scope_text = array(
  1 => "MACH",
  2 => "OS",
  3 => "ALL"
);

$scope_long = array(
  1 => "1 - Specific to a machine",
  2 => "2 - Specific to an operating system",
  3 => "3 - Applies to all machines and operating systems"
);


//
// End of "$Id: str.php,v 1.2 2004/05/19 00:57:33 mike Exp $".
//
?>
