<?php
include_once( "firewall.class.php");


class FirewallTest extends PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        $fw = new firewall();
        $fw->stopFirewall();
    }

    public function testCorporateProtectionOn()
    {
        $fw = new firewall();

        $output = array();
        //$fw->stopFirewall();
        exec( 'sed -i "s/CORPORATE_PROTECT=.*/CORPORATE_PROTECT=1/" /etc/wifi_rover.conf');
        $fw->startFirewall();
        exec('iptables -S FORWARD', $output);
        $protection_found = false;
        foreach( $output as $line)
        {
            if( $line == '-A FORWARD -s 192.168.22.0/24 -d 192.168.42.0/24 -m comment --comment "Protection GUEST -> CORPORATE" -j DROP')
            {
                $protection_found = true;
            }
        }
        assert($protection_found == true, "CORPORATE protection not found");
    }

    public function testCorporateProtectionOff()
    {
        $fw = new firewall();
        $output = array();
        exec( 'sed -i "s/CORPORATE_PROTECT=.*/CORPORATE_PROTECT=0/" /etc/wifi_rover.conf');

        $fw->startFirewall();
        exec('iptables -S FORWARD', $output);
        $protection_found = false;
        foreach( $output as $line)
        {
            if( $line == '-A FORWARD -s 192.168.22.0/24 -d 192.168.42.0/24 -m comment --comment "Protection GUEST -> CORPORATE" -j DROP')
            {
                $protection_found = true;
            }
        }
        assert($protection_found == false, "CORPORATE protection found");

    }
}
