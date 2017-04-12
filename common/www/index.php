<?php

include_once('glpi.php');
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

$ignore = array(
    "client-lb.dropbox.com",
    "configuration.apple.com",
    "d.dropbox.com",
    "download.windowsupdate.com",
    "e-cdn-proxy-9.deezer.com",
    "enablers.bouygtel.fr",
    "gllto.glpals.com",
    "images.apple.com",
    "iphone-wu.apple.com",
    "live.deezer.com",
    "mesu.apple.com",
    "notify1.dropbox.com",
    "notify2.dropbox.com",
    "notify4.dropbox.com",
    "notify5.dropbox.com",
    "notify9.dropbox.com",
    "phobos.apple.com",
    "safebrowsing.clients.google.com",
    "samsung-mobile.query.yahooapis.com",
    "www.update.microsoft.com",
    "ctldl.windowsupdate.com",
    "cdn.samsungcloudsolution.com",
);

$version = "";
$dest = $_SERVER['HTTP_HOST'];

if (in_array($dest, $ignore)) {
    exit;
}

$cf = '/etc/wifi_rover.conf';
# redirect corporate
$CORPORATENET = getValueFromConf($cf, 'CORPORATENET');
$CORPORATE_REDIRECT = getValueFromConf($cf, 'CORPORATE_REDIRECT');
if( $CORPORATE_REDIRECT != "" and $CORPORATENET != "" and IPBelongsToNetwork(IP, $CORPORATENET) )
{
    if(@ $CORPORATE_REDIRECT == "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])
    {
        exec(' sudo /sbin/ipset add corp_connected ' . IP . ' 2>&1  >> /tmp/wr.log');
    }

    echo "<html>";
    echo "<head>";
    echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache, no-store, must-revalidate\" />";
    echo "<meta http-equiv=\"Pragma\" content=\"no-cache\" />";
    echo "<meta http-equiv=\"Expires\" content=\"0\" />";
    echo "<meta http-equiv=\"refresh\" content=\"0; URL=" . $CORPORATE_REDIRECT . "\">";
    echo "</head>";
    echo "</html>";

    exit;
}

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
switch( $portalmode)
{
default:
    $webstr="?gtw-name=" . $gtw. "&gtw-ip=" . GTWADDR . "&gtw-port=" . GTWPORT . "&user-ip=" . IP . "&user-mac=" . $mac . "&user-url=" . $dest . "&borne-mac=" . getBorneMac() . "&captive-portal-version=" . $version;
    foreach( $_GET as $key => $value)
    {
        if( substr($key,0,3) == "uke")
        {
            switch( gettype( $value)) {
                case  "boolean":	
                case  "integer":	
                case  "double":	
                case  "string":	
                    $webstr.= "&" . $key . "=" . urlencode($value);
            }
        }
    }
}
error_log($webstr);

//Finally redirect to portal
//header("Location: " . $url . $webstr);
echo "<html>";
echo "<head>";
echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache, no-store, must-revalidate\" />";
echo "<meta http-equiv=\"Pragma\" content=\"no-cache\" />";
echo "<meta http-equiv=\"Expires\" content=\"0\" />";
echo "<meta http-equiv=\"refresh\" content=\"0; URL=" . URL . $webstr . "\">";
echo "</head>";
echo "</html>";
