<?php
function loadEnv() {
    /*
     * Load environment values after detected Environment Debian/Openwrt
     */
    $cf = '/etc/wifi_rover.conf';
    define('KEY', getValueFromConf($cf, 'GTWKEY'));
    define('TIMEOUT', getValueFromConf($cf, 'GTWTIMEOUT'));
    define('ADMINACTIVE', getValueFromConf($cf, 'ADMINACTIVE'));
    if (file_exists('/etc/debian_version')) {
        define('IPTABLES', 'sudo /sbin/iptables');
        define('ENV', 'debian');
    } else {
        define('IPTABLES', '/usr/sbin/iptables');
        define('ENV', 'openwrt');
    }
    define('ENV_DN', getValueFromConf($cf, 'PORTAL'));
    define('GTW', getValueFromConf($cf, 'GTWNAME'));
    define('LOGFILE', getValueFromConf($cf, 'LOGFILE'));
    define('GTWADDR', getValueFromConf($cf, 'GTW'));
    define('GTWPORT', getValueFromConf($cf, 'GTWPORT'));
    define('URL', getValueFromConf($cf, 'GTWURL'));
    @define('IP', $_SERVER['REMOTE_ADDR']);
    define('SVC_FNAME', 'http://'.ENV_DN.'/services/add_wifi_cnx_ws.php');
    define('SVC_CONFIRM_USER', 'http://'.ENV_DN.'/services/confirm_user.php');
    define('SINGLESESSION', getValueFromConf($cf, 'SINGLESESSION'));
    define('SINGLESESSIONFILE', getValueFromConf($cf, 'SINGLESESSIONFILE'));
    define('AUTOWHITELIST', getValueFromConf($cf, 'AUTOWHITELIST'));
    define('PROXYACTIVE', getValueFromConf($cf, 'PROXYACTIVE'));
    define('PROXY', getValueFromConf($cf, 'PROXY'));
    define('PROXYPORT', getValueFromConf($cf, 'PROXYPORT'));
    define('STOREID', getValueFromConf($cf, 'STOREID'));
}

function getValueFromConf($file, $var, $sep='=') {
    /*
     * Get $var value from configuration file $file
     * optionally take separator $sep (default =)
     */
    $value = null;
    if (file_exists($file)) {
        $h = fopen($file, 'r');
        while (!feof($h)) {
            $line = fgets($h);
            if (preg_match('/^' .$var . $sep . '/', $line)) {
                $tmp = explode($sep, $line);
                $value = trim($tmp[1],';');
                $value = rtrim($value, "\n");
                $value = rtrim($value, ";");
            }
        }
        return $value;
    } else {
        $message = "Error configuration file " . $file . " not found";
        echo $message;
        sendToLog($message);
    }
}

function getValuesFromConf($file, $var) {
    $array_values=array();
    $values =  trim(getValueFromConf($file, $var), '"');
    if( $values != "" )
    {
        $array_values = explode(" ", $values);
    }
    return $array_values;
}
function sendToFile($message, $file) {
    /*
     * Put content of $message into the file $file
     */
    exec('echo ' . $message . ' >> ' . $file);
}

function sendToLog($message) {
    /*
     * Add an entry to LOGFILE after added a date time prefix to $message
     */
    exec('date +%F\ %H:%M:%S', $out, $err);
    $date = $out[0];
    sendToFile($date . " " . $message, LOGFILE);
}

function setHeader($url = "") {
    /*
     * Do a redirect against $url or $_GET['redirect'] or by default on http://www.google.fr
     */
    if( $url == "")
    {
        if ( isset($_GET['redirect']) ) {
            $url=$_GET['redirect'];
            if ( 1 != preg_match('/^http/', $url) ) {
                $url="http://".$url;
            }
            header("Location: ".$url);
        }else{
            header("Location: http://www.google.fr");
        }
    }
    else
    {
        header( $url);
    }
}

function calculateToken($str) {
    /*
     *  Calculate token with constant KEY and $str
     */
    $token = md5(KEY . $str);
    return $token;
}

function compareToken($token, $str) {
    /*
     * Return true of false if localToken calculated with KEY+$str correspond or not
     */
     if (calculateToken($str) == $token) {
        return true;
     } else {
        return false;
     }
}

function checkTokenAge($ts) {
    /*
     * Check if a token timestamp has expired regarding TIMEOUT
     */
    $localTs = time();
    if ($localTs < ($ts + TIMEOUT)) {
        return true;
    } else {
        return false;
    }
}

function IPBelongsToNetwork($ip, $ip_with_mask) {
    list($net_ip, $mask_int) = explode('/', $ip_with_mask);
    $mask_nr = (pow(2, $mask_int) - 1) << (32 - $mask_int);
    //pow(2, $x) - 1 changes the number to a number made of that many set bits
    //and $y << (32 - $x) shifts it to the end of the 32 bits
    $long_subnet = ip2long($net_ip) & $mask_nr;
    $long_ip_subnet = ip2long($ip) & $mask_nr;
    return $long_subnet == $long_ip_subnet;
}

function getGatewayName( $cf)
{
    $value = null;
    $nb_net = getValueFromConf($cf, 'CAPTIVENETNB');

    if( $nb_net == null)
    {
        $value = getValueFromConf($cf, 'GTWNAME');
    }
    else
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        for( $i=1; $i<=$nb_net; ++$i)
        {
            $captivenet = getValueFromConf($cf, 'CAPTIVENET'.$i);
            if( IPBelongsToNetwork( $ip, $captivenet)) {
                $value = getValueFromConf($cf, 'GTWNAME'.$i);
            }
        }
    }

    return $value;
}

// Récuper l'IP de l'interface tun0
function getIPFromInterface( $interface) {
    $command = "/sbin/ifconfig $interface | head -n 2 | tail -n 1 | awk '{print $2}' | cut -d : -f 2";
    $ip = exec( $command);
    return $ip;
}

function getTunInterface()
{
    $tunif = getValueFromConf("/etc/interfaces.conf", 'TUNIF');
    if( $tunif == "")
    {
        $tunif = "tun0";
    }
    return $tunif;
}

function getNetworkOfInterface( $interface) {
    $command = "/sbin/ip addr show dev $interface | grep 'inet '| awk '{print $2}'";
    $net = exec( $command);
    return $net;
}


function getIPFrontal()
{
    $ip_borne = getIPFromInterface( getTunInterface());
    return long2ip(((ip2long($ip_borne) >> 11) <<11)+1);
}

function isIPwithPort($var)
{
    return preg_match('/^([0-1]{0,1}[0-9]{1,2}\.|[2]{1}[0-5]{2}\.){3}([0-1]{0,1}[0-9]{1,2}|[2]{1}(5[0-5]|[0-4][0-9])):([0-5]{0,1}[0-9]{1,4}|6[0-5]{2}[0-3][0-5])$/', $var);
}

function getBorneMac() {
    $out = array();
    exec( '/sbin/ifconfig eth0', $out);
    if( isset($out[0]))
    {
        $tmp = preg_split("/ +/", $out[0]);
        $borne_mac = $tmp[4];
    }
    else
    {
        error_log("impossible to get router MAC address");
    }
    return $borne_mac;
}

function getRemoteMac()
{
    $out = array();
    $err = '';
    //Detecting mac address via ARP
    exec('cat /proc/net/arp | grep "' . IP . ' " | awk \'{print $4}\'', $out, $err);
    $mac = @$out[0];

    //Detecting mac address via DHCP
    if ($mac == null || $mac == '') {
        unset($out);
        exec('cat /tmp/dhcp.leases | grep "' . IP . '" | awk \'{print $2}\'', $out, $err);
        $mac = $out[0];
    }
    return $mac;
}

function getLinuxFlavour() {

    if (file_exists('/etc/debian_version')) {
        $result = 'debian';
    } else {
        $result = 'openwrt';
    }

    return $result;
}
function isEdgeRouter() {

    return file_exists('/etc/debian_version') and ! file_exists( '/etc/default/fprobe');
}

function getRealNagiosName($name, $mac="") {
    $forbidden_chars=array(' ', '(', ')', "'");
    $name = str_replace($forbidden_chars, '_', $name);
    $tmp = explode(':', $mac);
    if( !isset($tmp[4]) or !isset($tmp[5]))
    {
        $result = strtoupper($name);
    }
    else
    {
        $result = strtoupper($name . '_' . $tmp[4] . $tmp[5]);
    }
    return $result;
}

function  ping( $ip) {
    $out = array();
    exec("ping -c 1 -w 1 -q $ip", $out, $err);
    return $err == 0;
}

