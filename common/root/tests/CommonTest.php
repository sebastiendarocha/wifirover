<?php
include_once( "common.php");

class CommonTest extends PHPUnit_Framework_TestCase {
    public function testCorporateProtectionOn() {
        assert( getNetworkOfInterface("lo") == "127.0.0.1/8");
    }

}
