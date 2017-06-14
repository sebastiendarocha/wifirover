#! /usr/bin/php-cgi -qC
<?php
include_once("common.php");
loadEnv();

$ip="192.168.23.124";
$user_mac="b8:27:eb:6a:de:f6";
$timestamp=time() -10;
$token=calculateToken($ip.$user_mac.$timestamp);
//$autowhitelist="&whitelist=2";


print("http://192.168.22.1:81/connect.php?user-ip=$ip&user-id=&user-mac=$user_mac&timestamp=$timestamp&redirect=http://www.google.fr&token=$token$autowhitelist");
