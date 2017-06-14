<?php
include_once( "firewall.class.php");


class FirewallTest extends PHPUnit_Framework_TestCase
{
    var $config = array();

    public function setUp()
    {
        $config = json_decode(file_get_contents("/root/docker.json"),true);
        $this->config = $config;
    }
    public function tearDown()
    {
        $fw = new firewall();
        $fw->stopFirewall();
    }

    public function testWANProtectionOn()
    {
        $fw = new firewall();

        $output = array();
        //$fw->stopFirewall();
        exec( 'sed -i "s/WAN_PROTECT=.*/WAN_PROTECT=1/" /etc/wifi_rover.conf');
        $fw->startFirewall();
        exec('iptables -S FORWARD | grep "Protection GUEST -> WAN"', $output);
        $protection_found = false;

        foreach( $output as $line)
        {

            if( preg_match( '@-A FORWARD -s 192.168.22.0/24 -d ([^ ]+) -m comment --comment "Protection GUEST -> WAN" -j DROP@', $line, $matches))
            {
                $wannet = $matches[1];
                if( IPBelongsToNetwork( $this->config['wanip'],$wannet))
                {
                    $protection_found = true;
                }
            }
        }
        assert($protection_found == true, "WAN protection found");
    }

    public function testWANProtectionOff()
    {
        $fw = new firewall();

        $output = array();
        //$fw->stopFirewall();
        exec( 'sed -i "s/WAN_PROTECT=.*/WAN_PROTECT=0/" /etc/wifi_rover.conf');
        $fw->startFirewall();
        exec('iptables -S FORWARD | grep "Protection GUEST -> WAN"', $output);
        $protection_found = false;

        foreach( $output as $line)
        {

            if( preg_match( '@-A FORWARD -s 192.168.22.0/24 -d ([^ ]+) -m comment --comment "Protection GUEST -> WAN" -j DROP@', $line, $matches))
            {
                $wannet = $matches[1];
                if( IPBelongsToNetwork( $this->config['wanip'],$wannet))
                {
                    $protection_found = true;
                }
            }
        }
        assert($protection_found == false, "WAN protection not found");
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
            if( $line == '-A FORWARD -s 192.168.22.0/24 -d 192.168.32.0/24 -m comment --comment "Protection GUEST -> CORPORATE" -j DROP')
            {
                $protection_found = true;
            }
        }
        assert($protection_found == true, "CORPORATE protection found");
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
            if( $line == '-A FORWARD -s 192.168.22.0/24 -d 192.168.32.0/24 -m comment --comment "Protection GUEST -> CORPORATE" -j DROP')
            {
                $protection_found = true;
            }
        }
        assert($protection_found == false, "CORPORATE protectionnot not found");

    }
}
