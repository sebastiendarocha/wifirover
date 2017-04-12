#! /usr/bin/php-cgi -qC
<?php
include_once("/www/common.php");
include_once("/www/controler_comm.php");
include_once("firewall.class.php");
loadEnv();
$fw = new firewall();
$iptables =  $fw->getFirewallCommand();


$command = strtolower($argv[1]);
$user_mac = strtolower($argv[2]);
$ip = strtolower($argv[3]);

$CORPORATE_REDIRECT = getValueFromConf("/etc/wifi_rover.conf", 'CORPORATE_REDIRECT');
error_log(print_r($argv,true));
// Si l'autowhitelist est activee
if( $CORPORATE_REDIRECT != "" and in_array( $command, array( "add", "del")) )
{
    error_log( "Deconnexion redirection corporate de " . $ip . "($command)") ;
    exec(' sudo /sbin/ipset -D corp_connected ' . $ip, $out, $err );
}

