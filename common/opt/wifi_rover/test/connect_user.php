#! /usr/bin/php-cgi -qC
<?php
include( 'connexion.class.php');

$fw = new connexion();
echo $fw->connectUser("192.168.22.34", "ab:cd:ef:12:34:56");

