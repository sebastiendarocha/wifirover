<?php

include_once('redirect.class.php');
include_once('common.php');
loadEnv();
/**
 * This script make a simple redirect to 
 **/
if ($_SERVER['SERVER_ADDR'] == $_SERVER['HTTP_HOST']) {
    if (ADMINACTIVE == 1) {
        die(header("Location: /admin/"));
    }
}

$redirect = new redirect();

$ignore = $redirect->getListIgnoreSites();

$version = "";
$dest = $_SERVER['HTTP_HOST'];

if (in_array($dest, $ignore)) {
    exit;
}

$cf = '/etc/wifi_rover.conf';

$mac = getRemoteMac();
//Blocking client if mac not defined
if ($mac == null || $mac == '') {
    echo "Unable to detect your mac address";
    exit;
}

$message = 'Captured connection from ' . IP . ' ' . $mac . ' to ' . $dest;
error_log($message);

// Single Session per day
if ( SINGLESESSION == 1 ) {
    unset($out);
    exec("cat " . SINGLESESSIONFILE . " | grep -c " . $mac, $out, $err);
    if ( $out[0] > 0) {
        die ("Vous vous &ecirc;tes d&eacute;j&agrave; connect&eacute; ce jour. Ce hotspot ne permet qu'une connexion quotidienne.");
    }
}

//Building webstring

$portalmode = getValueFromConf("/etc/wifi_rover.conf", 'PORTALMODE');
$webstr = implode($redirect->getUrlRedirect());
error_log($webstr);

//Finally redirect to portal
echo "<html>";
echo "<head>";
echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache, no-store, must-revalidate\" />";
echo "<meta http-equiv=\"Pragma\" content=\"no-cache\" />";
echo "<meta http-equiv=\"Expires\" content=\"0\" />";
echo "<meta http-equiv=\"refresh\" content=\"0; URL=" . $webstr . "\">";
echo "</head>";
echo "</html>";
