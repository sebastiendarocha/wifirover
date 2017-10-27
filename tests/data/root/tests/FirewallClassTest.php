<?php
include_once( "firewall.class.php");


class FirewallTest extends PHPUnit_Framework_TestCase
{
    var $config = array();

    public function setUp()
    {
        @$config = json_decode(file_get_contents("/root/docker.json"),true);
        @$this->config = $config;
    }
    public function tearDown()
    {
        $fw = @new firewall();
        @$fw->stopFirewall();
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
        assert($protection_found == false, "CORPORATE protection not found");

    }

    // Test IPset connection conf creates matchsets rules
    public function testConnectioIpsetEnabled()
    {
        $fw = new firewall();
        $output = array();
        exec( 'sed -i "s/CONNECTION_IPSET=.*/CONNECTION_IPSET=1/" /etc/wifi_rover.conf');

        $fw->startFirewall();
        // check FORWAD iptables
        exec('iptables -S WR_FWD', $output);
        $nb_forward_found = 0;
        foreach( $output as $line)
        {
            if( $line == '-A WR_FWD -m set --match-set connections src -j ACCEPT')
            {
                $nb_forward_found++;
            }
        }
        assert($nb_forward_found != 0, "IPSET connection matchset FORWARD not found: ".print_r($output,True));
        assert($nb_forward_found == 1, "IPSET connection matchset FORWARD too many found: ".print_r($output,True));

        // check PREROUTING iptables
        $output = array();
        exec('iptables -S WR_PRE -t nat', $output);
        $nb_prerouting_found= 0;
        foreach( $output as $line)
        {
            if( $line == '-A WR_PRE -m set --match-set connections src -j PROXY')
            {
                $nb_prerouting_found++;
            }
        }
        assert($nb_prerouting_found != 0, "IPSET connection matchset PREROUTING not found: ".print_r($output,True));
        assert($nb_prerouting_found == 1, "IPSET connection matchset PREROUTING too many found: ".print_r($output,True));

    }
    
    // Test Ipset connect lease and dhcp leases creates line in set
    public function testConnectionIpsetValid()
    {
        $fw = new firewall();
        $output = array();
        exec( 'sed -i "s/CONNECTION_IPSET=.*/CONNECTION_IPSET=1/" /etc/wifi_rover.conf');
        $fw->startFirewall();
        file_put_contents('/tmp/dhcp.leases', "1509025849 88:83:22:00:bf:a7 172.29.86.24 android-95c887d1cb5ab9ec 01:88:83:22:00:bf:a7"); 
        $fw->synchronizeConnexionsIPSet(array("88:83:22:00:bf:a7"));

        // check ip is present in connection ipset 
        exec('ipset list connections -o save', $output);
        $nb_ipset_found = 0;
        foreach( $output as $line)
        {
            if( $line == 'add connections 172.29.86.24')
            {
                $nb_ipset_found ++;
            }
        }
        assert($nb_ipset_found != 0, "IPSET connection not found: ".print_r($output,True));
        assert($nb_ipset_found == 1, "IPSET connection too many found: ".print_r($output,True));

        // Test Ipset connect no lease and dhcp leases remove line in set
        file_put_contents('/tmp/dhcp.leases', "1509025849 00:00:00:00:00:00 172.29.86.24 android-95c887d1cb5ab9ec 01:88:83:22:00:bf:a7"); 
        $fw->synchronizeConnexionsIPSet(array("88:83:22:00:bf:a7"));
        $output = array();

        // check ip is absent in connection ipset 
        exec('ipset list connections -o save', $output);
        $nb_ipset_found = 0;
        foreach( $output as $line)
        {
            if( $line == 'add connections 172.29.86.24')
            {
                $nb_ipset_found ++;
            }
        }
        assert($nb_ipset_found == 0, "IPSET connection found: ".print_r($output,True));
    }

    // Test Ipset connect no lease and dhcp leases match : line not created in set
    public function testConnectionIpsetNoLease()
    {
        $fw = new firewall();
        $output = array();
        exec( 'sed -i "s/CONNECTION_IPSET=.*/CONNECTION_IPSET=1/" /etc/wifi_rover.conf');
        $fw->startFirewall();

        // Test Ipset connect no lease and dhcp leases remove line in set
        file_put_contents('/tmp/dhcp.leases', "1509025849 00:00:00:00:00:00 172.29.86.24 android-95c887d1cb5ab9ec 01:88:83:22:00:bf:a7"); 
        $fw->synchronizeConnexionsIPSet(array("88:83:22:00:bf:a7"));
        $output = array();

        // check ip is absent in connection ipset 
        exec('ipset list connections -o save', $output);
        $nb_ipset_found = 0;
        foreach( $output as $line)
        {
            if( $line == 'add connections 172.29.86.24')
            {
                $nb_ipset_found ++;
            }
        }
        assert($nb_ipset_found == 0, "IPSET connection found: ".print_r($output,True));
    }

    // Test IPset connection stop firewall clears connections
    public function testConnectionIpsetStop()
    {
        $fw = new firewall();
        $output = array();
        exec( 'sed -i "s/CONNECTION_IPSET=.*/CONNECTION_IPSET=1/" /etc/wifi_rover.conf');
        $fw->startFirewall();

        file_put_contents('/tmp/dhcp.leases', "1509025849 88:83:22:00:bf:a7 172.29.86.24 android-95c887d1cb5ab9ec 01:88:83:22:00:bf:a7"); 
        $fw->synchronizeConnexionsIPSet(array("88:83:22:00:bf:a7"));

        // check ip is present in connection ipset 
        exec('ipset list connections -o save', $output);
        $nb_ipset_found = 0;
        foreach( $output as $line)
        {
            if( $line == 'add connections 172.29.86.24')
            {
                $nb_ipset_found ++;
            }
        }
        assert($nb_ipset_found != 0, "IPSET connection not found: ".print_r($output,True));
        assert($nb_ipset_found == 1, "IPSET connection too many found: ".print_r($output,True));

        // Test Ipset cleared when firewall stoped
        $fw->stopFirewall();
        $output = array();

        // check connection ipset  doesn't exist
        exec('ipset list connections -o save 2> /dev/null', $output, $err);
        assert($err != 0, "IPSET connexions exists : ".print_r($output,True));
        assert(count($output) == 0, "IPSET connection is not empty : ".print_r($output,True));

        // Prevent error messages on teardown
        $fw->startFirewall();
    }

    // Test IPset connection disabled in conf does not creates matchsets rules
    public function testConnectionIpsetDisabled()
    {
        $fw = new firewall();
        $output = array();
        exec( 'sed -i "s/CONNECTION_IPSET=.*/CONNECTION_IPSET=0/" /etc/wifi_rover.conf');
        $fw->startFirewall();

        // check connection ipset  doesn't exist
        exec('ipset list connections -o save 2> /dev/null', $output, $err);
        assert($err != 0, "IPSET connexions exists : ".print_r($output,True));
        assert(count($output) == 0, "IPSET connection is not empty : ".print_r($output,True));


        // Check matchsets are not created
        exec("iptables -C WR_FWD -m set --match-set connections src -j ACCEPT 2> /dev/null", $output, $err);
        assert($err != 0, "Matchset Forward exists : ".print_r($output,True));
        exec("iptables -C WR_PRE -t nat -m set --match-set connections src -j PROXY 2> /dev/null", $output, $err);
        assert($err != 0, "Matchset Prerouting  exists : ".print_r($output,True));
        

        file_put_contents('/tmp/dhcp.leases', "1509025849 88:83:22:00:bf:a7 172.29.86.24 android-95c887d1cb5ab9ec 01:88:83:22:00:bf:a7"); 
        $fw->synchronizeConnexionsIPSet(array("88:83:22:00:bf:a7"));

        // check connection ipset  doesn't exist
        exec('ipset list connections -o save 2> /dev/null', $output, $err);
        assert($err != 0, "IPSET connexions exists : ".print_r($output,True));
        assert(count($output) == 0, "IPSET connection is not empty : ".print_r($output,True));
    }

    // Test SynchronizeConnexions iptables mode
    public function testSynchronizeConnectionIptables()
    {
        $fw = new firewall();
        $output = array();
        exec( 'sed -i "s/CONNECTION_IPSET=.*/CONNECTION_IPSET=0/" /etc/wifi_rover.conf');
        $fw->startFirewall();

        file_put_contents('/tmp/dhcp.leases', "1509025849 88:83:22:00:bf:a7 172.29.86.24 android-95c887d1cb5ab9ec 01:88:83:22:00:bf:a7"); 
        $fw->synchronizeConnexions(array("88:83:22:00:bf:a7"));

        // check connection ipset  doesn't exist
        exec('ipset list connections -o save 2> /dev/null', $output, $err);
        assert($err != 0, "IPSET connexions exists : ".print_r($output,True));
        assert(count($output) == 0, "IPSET connection is not empty : ".print_r($output,True));

        // Test connection in IPtable
        exec("iptables -C WR_FWD -s 172.29.86.24 -j ACCEPT 2>&1 #/dev/null", $output, $err);
        assert($err == 0, "Iptables Forward don't exists : ".print_r($output,True));
        exec("iptables -C WR_PRE -t nat -s 172.29.86.24 -j PROXY 2> /dev/null", $output, $err);
        assert($err == 0, "Iptables Prerouting don't exists : ".print_r($output,True));
    }

    // Test SynchronizeConnexions ipSet mode
    public function testSynchronizeConnectionIpset()
    {
        $fw = new firewall();
        $output = array();
        exec( 'sed -i "s/CONNECTION_IPSET=.*/CONNECTION_IPSET=1/" /etc/wifi_rover.conf');
        $fw->startFirewall();

        file_put_contents('/tmp/dhcp.leases', "1509025849 88:83:22:00:bf:a7 172.29.86.24 android-95c887d1cb5ab9ec 01:88:83:22:00:bf:a7"); 
        $fw->synchronizeConnexions(array("88:83:22:00:bf:a7"));

        // check connection ipset exists
        exec('ipset list connections -o save', $output);
        $nb_ipset_found = 0;
        foreach( $output as $line)
        {
            if( $line == 'add connections 172.29.86.24')
            {
                $nb_ipset_found ++;
            }
        }
        assert($nb_ipset_found != 0, "IPSET connection not found: ".print_r($output,True));
        assert($nb_ipset_found == 1, "IPSET connection too many found: ".print_r($output,True));

        // Test connection in IPtable not exists
        exec("iptables -C WR_FWD -s 172.29.86.24 -j ACCEPT 2> /dev/null", $output, $err);
        assert($err != 0, "Iptables Forward exists : ".print_r($output,True));
        exec("iptables -C WR_PRE -t nat -s 172.29.86.24 -j PROXY 2> /dev/null", $output, $err);
        assert($err != 0, "Iptables Prerouting exists : ".print_r($output,True));
    }
    
    // TODO Test IPset connection disabled conf and stop firewall clears connections
    // TODO Test IPSET connection count
    public function testConnectionsIpset()
    {
        $fw = new firewall();
        $output = array();
        exec( 'sed -i "s/CONNECTION_IPSET=.*/CONNECTION_IPSET=1/" /etc/wifi_rover.conf');
        $fw->startFirewall();

        $connexions = $fw->connexions(); 
        assert( $connexions['connexions'] == 0, "No connections expected: " . print_r($connexions,true));

        file_put_contents('/tmp/dhcp.leases', "1509025849 88:83:22:00:bf:a7 172.29.86.24 android-95c887d1cb5ab9ec 01:88:83:22:00:bf:a7"); 
        $fw->synchronizeConnexions(array("88:83:22:00:bf:a7"));

        $connexions = $fw->connexions(); 
        assert( $connexions['connexions'] == 1, "One connection expected: " . print_r($connexions,true));
    }

    // Test IPTables connection count
    public function testConnectionsIptables()
    {
        $fw = new firewall();
        $output = array();
        exec( 'sed -i "s/CONNECTION_IPSET=.*/CONNECTION_IPSET=0/" /etc/wifi_rover.conf');
        $fw->startFirewall();

        $connexions = $fw->connexions(); 
        assert( $connexions['connexions'] == 0, "No connections expected: " . print_r($connexions,true));

        file_put_contents('/tmp/dhcp.leases', "1509025849 88:83:22:00:bf:a7 172.29.86.24 android-95c887d1cb5ab9ec 01:88:83:22:00:bf:a7"); 
        $fw->synchronizeConnexions(array("88:83:22:00:bf:a7"));

        $connexions = $fw->connexions(); 
        assert( $connexions['connexions'] == 1, "One connection expected: " . print_r($connexions,true));
    }

}
