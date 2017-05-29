<?php
include_once( "common.php");

echo "Test getNetworkOfInterface:";
assert( getNetworkOfInterface("eth0") == "172.25.0.2/16");
echo '.';

echo "\n";
