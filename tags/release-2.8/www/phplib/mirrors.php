<?php
//
// "$Id$"
//
// Mirror selection functions (depends on GeoIP PECL interface).
//
// Contents:
//
//   mirror_closest() - Return the closest mirror to the current IP.
//

//
// List of download servers...
//

$MIRRORS = array(
  "http://ftp.easysw.com/pub" => array("California, USA", 37.7898, -122.3942),
  "http://ftp2.easysw.com/pub" => array("New Jersey, USA", 40.4619, -74.3561),
  "http://ftp.funet.fi/pub/mirrors/ftp.easysw.com/pub" => array("Espoo, Finland", 60.2167, 24.6667),
  "http://ftp.rz.tu-bs.de/pub/mirror/ftp.easysw.com/ftp/pub" => array("Braunschweig, Germany", 52.2667, 10.5333),
);


//
// 'mirror_closest()' - Return the closest mirror to the current IP.
//

function				// O - Closest mirror
mirror_closest()
{
  global $_SERVER, $MIRRORS;


  // Get the current longitude for the client...
  if (!extension_loaded("geoip.so") ||
      $_SERVER["REMOTE_ADDR"] == "::1" ||
      $_SERVER["REMOTE_ADDR"] == "127.0.0.1")
    $lon = -120;
  else
  {
    $current = geoip_record_by_name($_SERVER["REMOTE_ADDR"]);
    $lon     = $current["longitude"];
  }

  // Loop through the mirrors to find the closest one, currently just using
  // the longitude...
  $closest_mirror   = "";
  $closest_distance = 999;

  reset($MIRRORS);
  foreach ($MIRRORS as $mirror => $data)
  {
    $distance = abs($lon - $data[2]);
    if ($distance > 180)
      $distance = 360 - $distance;

    if ($distance < $closest_distance)
    {
      $closest_mirror   = $mirror;
      $closest_distance = $distance;
    }
  }

  return ($closest_mirror);
}


//
// End of "$Id$".
//
?>
