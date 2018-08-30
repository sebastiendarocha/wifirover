<?php
include_once( "common.php");

class CommonTest extends PHPUnit_Framework_TestCase {
    public function testCorporateProtectionOn() {
        assert( getNetworkOfInterface("lo") == "127.0.0.1/8");
    }

    public function test_isIPwithPort() {
        assert( !isIPwithPort(""));
        assert( !isIPwithPort("chocolat"));
        assert( !isIPwithPort("chocolat:1"));
        assert( !isIPwithPort("abc.def.ijk.lmn:1"));
        assert( !isIPwithPort("0.0.0.0:abs"));
        assert( isIPwithPort("0.0.0.0:1"));
        assert( isIPwithPort("1.1.1.1:1"));
        assert( isIPwithPort("192.168.0.1:1"));
        assert( isIPwithPort("163.172.227.113:37714"));
        assert( isIPwithPort("255.255.255.255:65535"));
        assert( !isIPwithPort("255.256.255.255:65535"));
        assert( !isIPwithPort("255.255.255.255:65536"));
        assert( !isIPwithPort("255.-1.255.255:65536"));
        assert( !isIPwithPort("255.255.255.255:-1"));
    }
}
