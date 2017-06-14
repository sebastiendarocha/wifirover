#! /usr/bin/php-cgi -qC
<?php
include 'common.php';
define( 'CF', '/etc/wifi_rover.conf');
define( 'ICF', '/etc/interfaces.conf');

// Get ip of eth0 to eth2
$ips = array( getIPFromInterface('eth0'),getIPFromInterface('eth1'),getIPFromInterface('eth2'));
$wanif = "";
$lanif = "";
$corpif = "";

$config = array();

// For each
foreach( $ips as $num => $ip)
{
    $CAPTIVENET = getValueFromConf(CF, 'CAPTIVENET');
    $CORPORATENET = getValueFromConf(CF, 'CORPORATENET');
    // detect if it LAN
    if( IPBelongsToNetwork($ip, $CAPTIVENET))
    {
        $config['lanif'] = "eth".$num;
    }
        
    // Or if its CORPORATE
    elseif( IPBelongsToNetwork($ip, $CORPORATENET))
    {
        $config['corpif'] = "eth".$num;
    }
    // Or if its WAN
    else
    {
        $wanif = "eth".$num;
        $config['wanif'] = $wanif;
        $config['wanip'] = getIPFromInterface($wanif);
        $config['wannet'] = getNetworkOfInterface($wanif);
    }
}
file_put_contents("/root/docker.json", json_encode($config));
?>
WANIF=<?php echo $config['wanif']; ?>;
LANIF=<?php echo $config['lanif'];?>;
TUNIF=tun0;
CORPIF=<?php echo $config['corpif'];?>;

