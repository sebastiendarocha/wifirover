<?php
include_once("common.php");
include_once("firewall_plugin.class.php");
include_once("plugable.class.php");

define( 'CF', '/etc/wifi_rover.conf');
define( 'ICF', '/etc/interfaces.conf');

define( 'DHCPLEASEFILE', '/tmp/dhcp.leases');

class firewall extends plugable {
    var $iptables = "";
    var $ipset = "";
    static $type = "firewall";

    function __construct ()
    {
        #getValueFromConf($cf, 'GTWKEY');
    }

    function getName() { return __CLASS__; }

    function startFirewall() {
        $IPTABLES = $this->getFirewallCommand();
        $IPSET = $this->getIpsetCommand();
        $LANIF = getValueFromConf(ICF, 'LANIF');
        $WANIF = getValueFromConf(ICF, 'WANIF');
        $TUNIF = getValueFromConf(ICF, 'TUNIF');

        $CAPTIVENET = getValueFromConf(CF, 'CAPTIVENET');
        $IPPORTAL = getValueFromConf(CF, 'IPPORTAL');

        $GTW = getValueFromConf(CF, 'GTW');
        $GTWPORT = getValueFromConf(CF, 'GTWPORT');
        $GTWPORT_SSL = getValueFromConf(CF, 'GTWPORT_SSL');



        $DOCKER = getValueFromConf(CF, 'DOCKER');
        if( $DOCKER == "1")
        {
            $rules = array();
        }
        else
        {
            $rules = array(
                "$IPTABLES -F -t nat",
                "$IPTABLES -X -t nat",
            );
        }

        $rules += array(
            "$IPTABLES -F",
            "$IPTABLES -X",
            "$IPTABLES -P INPUT ACCEPT",
            "$IPTABLES -P OUTPUT ACCEPT",
            "$IPTABLES -P FORWARD DROP",

            "$IPTABLES -t nat -N BYPASS",
            "$IPTABLES -t nat -N WR_PRE",
            "$IPTABLES -t nat -N PROXY",

            "$IPTABLES -N BL",
            "$IPTABLES -N WL",
            "$IPTABLES -N WR_FWD",

            "$IPSET create whitelist_domain hash:ip -exist",
        );

        // Setup Blacklist, Whitelist and Wifi rover chains
        $rules[] = "$IPTABLES -A FORWARD -j BL";
        $rules[] = "$IPTABLES -A FORWARD -j WL";
        $rules[] = "$IPTABLES -A FORWARD -j WR_FWD";
        $rules[] = "$IPTABLES -t nat -I PREROUTING -j WR_PRE";
        $rules[] = "$IPTABLES -t nat -I PREROUTING -j BYPASS";

        // Weblib: authorize to connect to 443 on the router
        $portalmode = getValueFromConf("/etc/wifi_rover.conf", 'PORTALMODE');
        if( $portalmode == "WEBLIB")
        {
            $rules[] = "$IPTABLES -t nat -A PREROUTING -i $LANIF -p tcp --dport 443 -d $GTW -j ACCEPT";
            $rules[] = "$IPTABLES -A INPUT -j ACCEPT -i $LANIF -p tcp --dport 443 -m comment --comment 'Interface Web LAN'";
        }

        // Redirect to portal
        $rules[] = "$IPTABLES -t nat -A PREROUTING -i $LANIF -p tcp --dport 80 -s $CAPTIVENET -d $IPPORTAL -j ACCEPT";
        $rules[] = "$IPTABLES -t nat -A PREROUTING -i $LANIF -p tcp --dport 443 -s $CAPTIVENET -d $IPPORTAL -j ACCEPT";
        $rules[] = "$IPTABLES -t nat -A PREROUTING -i $LANIF -p tcp --dport 80 -s $CAPTIVENET -j DNAT --to-destination $GTW:$GTWPORT";
        $rules[] = "$IPTABLES -t nat -A PREROUTING -i $LANIF -p tcp --dport 443 -s $CAPTIVENET -j DNAT --to-destination $GTW:$GTWPORT_SSL";

        // Whitelist some domains
        $rules[] = "$IPTABLES -t nat -I PREROUTING -i $LANIF -s $CAPTIVENET  -m set --match-set whitelist_domain dst -j ACCEPT";
        $rules[] = "$IPTABLES -A FORWARD -m set --match-set whitelist_domain dst -j ACCEPT";

        // Proxy
        $PROXY = getValueFromConf(CF, 'PROXY');
        $PROXYPORT = getValueFromConf(CF, 'PROXYPORT');
        $PROXYACTIVE = getValueFromConf(CF, 'PROXYACTIVE');
        $PROXYNETWORKS = getValueFromConf(CF, 'PROXYNETWORKS');

        if(  $PROXYACTIVE == 1 )
        {
            $rules[] = "$IPTABLES -t nat -I PROXY -p tcp --dport 80 -s $PROXYNETWORKS -j DNAT --to-destination $PROXY:$PROXYPORT";
            if( $PROXY == $GTW) // Proxy local
            {
                $rules[] = "$IPTABLES -I INPUT -j ACCEPT -p tcp --dport 10031 -i $LANIF -m comment --comment 'Temp PROXY Local'";
            }
        }
        $rules[] = "$IPTABLES -t nat -A PROXY -j ACCEPT";

        $SMTPREDIRECTACTIVE = getValueFromConf(CF, 'SMTPREDIRECTACTIVE');
        $SMTPREDIRECTSERVER = getValueFromConf(CF, 'SMTPREDIRECTSERVER');
        $SMTPREDIRECTPORT = getValueFromConf(CF, 'SMTPREDIRECTPORT');
        if( $SMTPREDIRECTACTIVE == 1 ) {
            $rules[] = "$IPTABLES -t nat -A PREROUTING -p tcp --dport 25 -s $CAPTIVENET -j DNAT --to-destination $SMTPREDIRECTSERVER:$SMTPREDIRECTPORT";
        }

        // Open INPUT Access
        $CLOUDROUTER = getValueFromConf(CF, 'CLOUDROUTER');
        if( $CLOUDROUTER == 1) {
            $OUT="-d $GTW";
            $rules[] = "$IPTABLES -A INPUT -j ACCEPT -p udp --dport  1294 -m comment --comment 'tunnel from LAN'";
        } else {
            $OUT="-i $LANIF";
        }
        $rules[] = "$IPTABLES -P INPUT DROP";
        $rules[] = "$IPTABLES -A INPUT -j ACCEPT -i lo -m comment --comment 'Local communication'";
        $rules[] = "$IPTABLES -A INPUT -j ACCEPT -i $TUNIF -m comment --comment 'Access from VPN'";
        $rules[] = "$IPTABLES -A INPUT -j ACCEPT -p tcp --dport 22 -m comment --comment 'SSH'";
        $rules[] = "$IPTABLES -A INPUT -j ACCEPT -p udp --dport 53 $OUT -m comment --comment 'DNS from LAN'";
        $rules[] = "$IPTABLES -A INPUT -j ACCEPT -p udp --dport 67 $OUT -m comment --comment 'DHCP from LAN'";
        $rules[] = "$IPTABLES -A INPUT -j DROP   -p tcp --dport $GTWPORT -i $WANIF -m comment --comment 'HTTP DENY FROM WAN'";
        $rules[] = "$IPTABLES -A INPUT -j ACCEPT -p tcp --dport $GTWPORT -m comment --comment 'HTTP Capture from LAN'";
        $rules[] = "$IPTABLES -A INPUT -j DROP   -p tcp --dport $GTWPORT_SSL -i $WANIF -m comment --comment 'HTTPS DENY FROM WAN'";
        $rules[] = "$IPTABLES -A INPUT -j ACCEPT -p tcp --dport $GTWPORT_SSL -m comment --comment 'HTTPS Capture from LAN'";
        $rules[] = "$IPTABLES -I INPUT -p tcp --dport $GTWPORT_SSL -i $LANIF -m state --state NEW -m recent --set -m comment --comment 'Protect local HTTPS'";
        $rules[] = "$IPTABLES -I INPUT -p tcp --dport $GTWPORT_SSL -i $LANIF -m state --state NEW -m recent --update --seconds 5 --hitcount 1 -j DROP -m comment --comment 'Protect local HTTPS'";
        $rules[] = "$IPTABLES -I INPUT -j ACCEPT -m state --state ESTABLISHED,RELATED";

        // Open Standard Access
        $rules[] = "$IPTABLES -A FORWARD -d $IPPORTAL -p tcp --dport 80 -j ACCEPT";
        $rules[] = "$IPTABLES -A FORWARD -d $IPPORTAL -p tcp --dport 443 -j ACCEPT";
        $rules[] = "$IPTABLES -A FORWARD -m state --state ESTABLISHED,RELATED -j ACCEPT";
        $rules[] = "$IPTABLES -t nat -A POSTROUTING -o $WANIF -j MASQUERADE";

        // Open access from corporate
        $CORPORATENET = getValueFromConf(CF, 'CORPORATENET');
        $CORPORATEPROXY = getValueFromConf(CF, 'CORPORATEPROXY');
        $CORPIF = getValueFromConf(ICF, 'CORPIF');
        $CORPORATE_REDIRECT = getValueFromConf(CF, 'CORPORATE_REDIRECT');
        $CORPORATE_PROTECT = getValueFromConf(CF, 'CORPORATE_PROTECT');
        if( $CORPORATENET != "" ) {
            foreach( explode(" ", trim($CORPORATENET,'"')) as $CORPORATENETi)
            {
                $rules[] = "$IPTABLES -A FORWARD -j ACCEPT -s $CORPORATENETi -m comment --comment 'CORPORATE LAN'";
                if( $CORPORATEPROXY != 0)
                {
                    $rules[] = "$IPTABLES -t nat -I PREROUTING -j PROXY -s $CORPORATENETi -m comment --comment 'PROXY for CORPORATE LAN'";
                    $rules[] = "$IPTABLES -t nat -I PROXY -p tcp --dport 80 -s $CORPORATENETi -j DNAT --to-destination $PROXY:$PROXYPORT";
                }
                $rules[] = "$IPTABLES -A INPUT -j ACCEPT -p udp --dport 53 -s $CORPORATENETi -m comment --comment 'DNS from CORPORATE LAN'";
                if( $CORPORATE_PROTECT == "1")
                {
                    $rules[] = "$IPTABLES -I FORWARD -s $CAPTIVENET -d $CORPORATENETi -j DROP -m comment --comment 'Protection GUEST -> CORPORATE'";
                }
            }
        }
        $WAN_PROTECT = getValueFromConf(CF, 'WAN_PROTECT');
        $WAN_NET = getNetworkOfInterface($WANIF);
        if( $WAN_PROTECT == 1)
        {
            $rules[] = "$IPTABLES -I FORWARD -s $CAPTIVENET -d $WAN_NET -j DROP -m comment --comment 'Protection GUEST -> WAN'";
        }

        if( $CORPIF != "") {
            if( $CORPORATE_REDIRECT != "")
            {
                $rules[] = "$IPSET create corp_connected hash:ip -exist";
            }
            foreach( explode(" ", trim($CORPIF,'"')) as $CORPIFi) {
                $rules[] = "$IPTABLES -A INPUT -j ACCEPT -p udp --dport 67 -i $CORPIFi -m comment --comment 'DHCP from CORPORATE LAN'";
                if( $CORPORATE_REDIRECT != "")
                {
                    $ipcorp = getIPFromInterface($CORPIFi);
                    $rules[] = "$IPTABLES -I PREROUTING -t nat -i $CORPIFi -p tcp -m tcp --dport 80 -j DNAT --to-destination $ipcorp:81";
                    $rules[] = "$IPTABLES -I PREROUTING -t nat -i $CORPIFi -m set --match-set corp_connected src -j ACCEPT";
                }
            }
        }


        # If splash is disabled
        $GTWNAME = getValueFromConf(CF, 'GTWNAME');
        if( $GTWNAME == "" )  {
            if( $PROXYACTIVE == 1)
            {
                $rules[] = "$IPTABLES -t nat -I PREROUTING -i $LANIF -j PROXY";
            }
            else
            {
                $rules[] = "$IPTABLES -t nat -I PREROUTING -i $LANIF -j ACCEPT";
            }
            $rules[] = "$IPTABLES -I FORWARD -i $LANIF -j ACCEPT";
        }

        $UNIFI = getValueFromConf(CF, 'UNIFI');
        if( $UNIFI != "" )  {
            $rules[] = "$IPTABLES -I FORWARD -d $UNIFI -p tcp --dport 8080 -j ACCEPT -m comment --comment 'Unifi connection to the controler'";
        }

        # If in Docker environnement
        $DOCKER = getValueFromConf(CF, 'DOCKER');
        if( $GTWNAME == "1" )  {
            $rules[] = "$IPTABLES -A OUTPUT -d 127.0.0.11/32 -j DOCKER_OUTPUT";
            $rules[] = "$IPTABLES -A POSTROUTING -d 127.0.0.11/32 -j DOCKER_POSTROUTING";

        }
        if( isEdgeRouter())
        {
            $rules[] = "$IPTABLES -A INPUT -j ACCEPT -i $TUNIF -p tcp --dport 443 -m comment --comment 'Interface Web VPN'";
            $rules[] = "$IPTABLES -A INPUT -j ACCEPT -i $LANIF -p tcp --dport 443 -m comment --comment 'Interface Web LAN'";

            $rules[] = "$IPTABLES -N VYATTA_POST_FW_FWD_HOOK";
            $rules[] = "$IPTABLES -N VYATTA_POST_FW_OUT_HOOK";
            $rules[] = "$IPTABLES -N VYATTA_POST_FW_IN_HOOK";

            $rules[] = "$IPTABLES -I FORWARD -j VYATTA_POST_FW_FWD_HOOK";
            $rules[] = "$IPTABLES -I OUTPUT -j VYATTA_POST_FW_OUT_HOOK";
            $rules[] = "$IPTABLES -I INPUT -j VYATTA_POST_FW_IN_HOOK";

            $rules[] = "$IPTABLES -A VYATTA_POST_FW_FWD_HOOK -o $LANIF -j ULOG --ulog-nlgroup 5 --ulog-cprange 64 --ulog-qthreshold 10";
            $rules[] = "$IPTABLES -A VYATTA_POST_FW_IN_HOOK -o $LANIF -j ULOG --ulog-nlgroup 5 --ulog-cprange 64 --ulog-qthreshold 10";
            $rules[] = "$IPTABLES -A VYATTA_POST_FW_OUT_HOOK -o $LANIF -j ULOG --ulog-nlgroup 5 --ulog-cprange 64 --ulog-qthreshold 10";
            /*
            $rules[] = "$IPTABLES -A VYATTA_POST_FW_FWD_HOOK -j ACCEPT";
            $rules[] = "$IPTABLES -A VYATTA_POST_FW_IN_HOOK -j ACCEPT";
            $rules[] = "$IPTABLES -A VYATTA_POST_FW_OUT_HOOK -j ACCEPT";
            */

            $CORPORATECAPT = getValueFromConf(CF, 'CORPORATECAPT');
            if( $CORPIF != "" and $CORPORATECAPT=1 ) {
                foreach( explode(" ", trim($CORPIF,'"')) as $CORPIFi) {
                    $rules[] = "$IPTABLES -I VYATTA_POST_FW_FWD_HOOK -o $CORPIFi -j ULOG --ulog-nlgroup 5 --ulog-cprange 64 --ulog-qthreshold 10";
                    $rules[] = "$IPTABLES -I VYATTA_POST_FW_IN_HOOK -o $CORPIFi -j ULOG --ulog-nlgroup 5 --ulog-cprange 64 --ulog-qthreshold 10";
                    $rules[] = "$IPTABLES -I VYATTA_POST_FW_OUT_HOOK -o $CORPIFi -j ULOG --ulog-nlgroup 5 --ulog-cprange 64 --ulog-qthreshold 10";
                }
            }

        }
        $rules = array_merge($rules, $this->foreach_plugins( "startFirewall"));
            
        $this->execCommand( $rules);

        $this->refreshBlackWhitelist(true);
    }

    function execCommand( $rules)
    {
        $LOG = getValueFromConf(CF, 'LOG_IPTABLES');
        foreach( $rules as $rule)
        {
            if( $LOG == 1)
            {
                error_log( $rule);
            }
            exec($rule);
        }
    }


    function stopFirewall() {
        $IPTABLES = $this->getFirewallCommand();
        $GTW = getValueFromConf(CF, 'GTW');
        $GTWPORT = getValueFromConf(CF, 'GTWPORT');

        $DOCKER = getValueFromConf(CF, 'DOCKER');
        if( $DOCKER == "1")
        {
            $rules = array();
        }
        else
        {
            $rules = array(
                "$IPTABLES -F -t nat",
                "$IPTABLES -X -t nat",
            );
        }

        $rules += array(
            "$IPTABLES -F",
            "$IPTABLES -X",

            "$IPTABLES -P INPUT ACCEPT",
            "$IPTABLES -P OUTPUT ACCEPT",
            "$IPTABLES -P FORWARD DROP",

            "$IPTABLES -A FORWARD -m state --state ESTABLISHED,RELATED -j ACCEPT",

        );

        $rules = array_merge($rules, $this->foreach_plugins( "stopFirewall"));

        $this->refreshBlackWhitelist(false);
        $this->execCommand( $rules);
    }

    /**
     * \param macs_hash
     */
    function synchronizeConnexions($macs_connected)
    {
        $macs_hash = array();
        $ips_hash = array();
        // Load the IP of the connected users
        $mac_ips = $this->getDHCPLeases();
        foreach($macs_connected as $mac_connected)
        {
            if( isset($mac_ips[$mac_connected]))
            {
                $ip_connected = $mac_ips[$mac_connected];
                $macs_hash[$mac_connected] = $ip_connected;
                $ips_hash[$ip_connected] = $mac_connected;
            }
        }

        #echo 'Macs and IP to connect';
        #var_dump( $ips_hash);

        // Get all clients connected in iptables
        $IPTABLES = $this->getFirewallCommand();

        $result = array();
        $command =  $IPTABLES. ' -L WR_PRE -t nat -n'  ;
        $table_wr_pre = array();
        exec( $command, $result);
        array_shift($result); // Remove header
        array_shift($result); // Remove header
        foreach($result as $rule)
        {
            $items = preg_split("/[[:blank:]]+/", $rule);
            $ip = $items[3];
            if( isset($ips_hash[$ip]))
            {
                $table_wr_pre[$ip] = $ips_hash[$ip];
            }
            else // mac lost because user is disconnect
            {
                $table_wr_pre[$ip] = 0;
            }
        }

        $result = array();
        $command =  $IPTABLES. ' -L WR_FWD -n'  ;
        $table_wr_fwd = array();
        exec( $command, $result);
        array_shift($result); // Remove header
        array_shift($result); // Remove header
        foreach($result as $rule)
        {
            $items = preg_split("/[[:blank:]]+/", $rule);
            $ip = $items[3];
            if( isset( $ips_hash[$ip]))
            {
                $table_wr_fwd[$ip] = $ips_hash[$ip];
            }
            else // mac lost because user is disconnect
            {
                $table_wr_fwd[$ip] = 0;
            }
        }

        //echo 'Machines connected';
        //var_dump($table_wr_pre);

        // Destroy connections of clients that are not connected
        $commands = array();
        foreach( $table_wr_pre as $ip => $mac)
        {
            if( $mac === 0)
            {
                $commands[] = $IPTABLES . ' -t nat -D WR_PRE -s ' . $ip . ' -j PROXY';
            }
        }
        foreach( $table_wr_fwd as $ip => $mac)
        {
            if( $mac === 0)
            {
                $commands[] = $IPTABLES . ' -D WR_FWD -s ' . $ip . ' -j ACCEPT';
            }
        }


        // Create connection of clients that have leases
        foreach( $macs_hash as $mac => $ip)
        {
            if( !isset($table_wr_pre[$ip]))
            {
                $commands[] = $IPTABLES . ' -t nat -I WR_PRE -s ' . $ip . ' -j PROXY';
                error_log("Connexion (prerouting) $ip $mac");
            }
            if( !isset($table_wr_fwd[$ip]))
            {
                $commands[] = $IPTABLES . ' -I WR_FWD -s ' . $ip . ' -j ACCEPT';
                error_log("Connexion (forward) $ip $mac");
            }
        }


        $this->execCommand( $commands);
    }

    function checkRules()
    {
        $LANIF = getValueFromConf(ICF, 'LANIF');
        $WANIF = getValueFromConf(ICF, 'WANIF');

        $IPTABLES = $this->getFirewallCommand();
        $PROXY = getValueFromConf(CF, 'PROXY');
        $PROXYPORT = getValueFromConf(CF, 'PROXYPORT');
        $PROXYACTIVE = getValueFromConf(CF, 'PROXYACTIVE');
        $PROXYNETWORKS = getValueFromConf(CF, 'PROXYNETWORKS');

        $GTW = getValueFromConf(CF, 'GTW');

        // Check proxy rules
        if(  $PROXYACTIVE == 1 )
        {
            $rules[] = "$IPTABLES -t nat -C PROXY -p tcp --dport 80 -s $PROXYNETWORKS -j DNAT --to-destination $PROXY:$PROXYPORT";
            if( $PROXY == $GTW) // Proxy local
            {
                $rules[] = "$IPTABLES -C INPUT -j ACCEPT -p tcp --dport 10031 -i $LANIF -m comment --comment 'Temp PROXY Local'";
            }
        }
        $rules[] = "$IPTABLES -t nat -C PROXY -j ACCEPT";

        $CLOUDROUTER = getValueFromConf(CF, 'CLOUDROUTER');
        $GTWPORT = getValueFromConf(CF, 'GTWPORT');
        $GTWPORT_SSL = getValueFromConf(CF, 'GTWPORT_SSL');
        $CAPTIVENET = getValueFromConf(CF, 'CAPTIVENET');
        // Check Input (lighttpd, proxy local, ssh, vpn)
        if( $CLOUDROUTER == 1) {
            $OUT="-d $GTW";
            $rules[] = "$IPTABLES -C INPUT -j ACCEPT -p udp --dport  1294 -m comment --comment 'tunnel from LAN'";
        } else {
            $OUT="-i $LANIF";
        }
        $rules[] = "$IPTABLES -C INPUT -j ACCEPT -i lo -m comment --comment 'Local communication'";
        $rules[] = "$IPTABLES -C INPUT -j ACCEPT -p tcp --dport $GTWPORT -m comment --comment 'HTTP Capture from LAN'";
        $rules[] = "$IPTABLES -C INPUT -j ACCEPT -p tcp --dport $GTWPORT_SSL -m comment --comment 'HTTPS Capture from LAN'";
        $rules[] = "$IPTABLES -C INPUT -j ACCEPT -p tcp --dport 22 -m comment --comment 'SSH'";
        $rules[] = "$IPTABLES -C INPUT -j ACCEPT -p udp --dport 53 $OUT -m comment --comment 'DNS from LAN'";
        $rules[] = "$IPTABLES -C INPUT -j ACCEPT -p udp --dport 67 $OUT -m comment --comment 'DHCP from LAN'";

        $IPPORTAL = getValueFromConf(CF, 'IPPORTAL');
        // Check access portal
        $rules[] = "$IPTABLES -t nat -C PREROUTING -i $LANIF -p tcp --dport 80 -s $CAPTIVENET -d $IPPORTAL -j ACCEPT";
        $rules[] = "$IPTABLES -t nat -C PREROUTING -i $LANIF -p tcp --dport 443 -s $CAPTIVENET -d $IPPORTAL -j ACCEPT";
        $rules[] = "$IPTABLES -C FORWARD -d $IPPORTAL -p tcp --dport 80 -j ACCEPT";
        $rules[] = "$IPTABLES -C FORWARD -d $IPPORTAL -p tcp --dport 443 -j ACCEPT";

        // Check redirection
        $rules[] = "$IPTABLES -t nat -C PREROUTING -i $LANIF -p tcp --dport 80 -s $CAPTIVENET -j DNAT --to-destination $GTW:$GTWPORT";
        $rules[] = "$IPTABLES -t nat -C PREROUTING -i $LANIF -p tcp --dport 443 -s $CAPTIVENET -j DNAT --to-destination $GTW:$GTWPORT_SSL";

        if( isEdgeRouter())
        {
            $rules[] = "$IPTABLES -C FORWARD -j VYATTA_POST_FW_FWD_HOOK";
            $rules[] = "$IPTABLES -C OUTPUT -j VYATTA_POST_FW_OUT_HOOK";
            $rules[] = "$IPTABLES -C INPUT -j VYATTA_POST_FW_IN_HOOK";
        }
        $rules[] = "$IPTABLES -t nat -C POSTROUTING -o $WANIF -j MASQUERADE";

        $rules = array_merge($rules, $this->foreach_plugins( "checkRules"));

        $this->execCommand( $rules);

        $result = true;

        foreach($rules as $rule)
        {
            $output = array();
            $ret = 0;
            @exec( $rule, $output, $ret);
            if( $ret != 0)
            {
                @error_log( 'Error on "' . $rule . '" : "'. $output[0]);
                $result = false;
            }
        }

        return $result;
    }

    function connexions()
    {

        $IPTABLES = $this->getFirewallCommand();
        $result = array();


        // Check BL
        $output = array();
        $command = $IPTABLES . ' -L BL -n';
        exec($command, $output);
        $result['blacklist'] = count($output) - 2;

        // Check WL
        $output = array();
        $command = $IPTABLES . ' -L WL -n';
        exec($command, $output);
        $result['whitelist'] = count($output) - 2;
      
        // Check connexions
        $output = array();
        $command = $IPTABLES . ' -L WR_FWD -n';
        exec($command, $output);
        $result['connexions'] = count($output) - 2;

        $result = array_merge($result, $this->foreach_plugins( "connexions"));

        return $result;
    }


    function getDHCPLeases()
    {
        $LANIF = getValueFromConf(ICF, 'LANIF');
        $result = array();
        $leases = file( DHCPLEASEFILE);
        foreach( $leases as $lease)
        {
            list( $ts, $mac, $ip, $dns, ) = explode( ' ', $lease);
            $result[strtoupper($mac)] = $ip;
        }
        $arp = file( '/proc/net/arp');
        foreach( $arp as $lease)
        {
            list( $ip, $hw, $flag, $mac, $mask, $device ) = preg_split( '/\s+/', $lease);
            if( $device == $LANIF and !isset($result[strtoupper($mac)]))
            {
                $result[strtoupper($mac)] = $ip;
            }
        }
        return $result;
    }

    function getFirewallCommand() {
        if( $this->iptables == "" )
        {
            if( file_exists('/etc/debian_version')) { // Debian
                $IPTABLES = '/usr/bin/sudo /sbin/iptables';
            } else { // Openwrt
                $IPTABLES = "/usr/sbin/iptables"; // Attitude adjustment
            }
            $result = 0;
            $output = array();
            exec($IPTABLES . ' -L -w -vn > /dev/null 2> /dev/null', $output, $result);
            if( $result == 0) { // Barrier braker and greater
                $IPTABLES .= " -w";
            }
            $this->iptables = $IPTABLES;
        }
        return $this->iptables;
    }
    function getIpsetCommand() {
        if( $this->ipset == "" )
        {
            if( file_exists('/etc/debian_version')) { // Debian
                if( file_exists('/sbin/ipset')) { // Jessie and +
                    $this->ipset = '/usr/bin/sudo /sbin/ipset';
                }
                else // Wheezy
                {
                    $this->ipset = '/usr/bin/sudo /usr/sbin/ipset';
                }
            } else { // Openwrt
                $this->ipset = "/usr/sbin/ipset"; // Openwrt
            }
        }
        return $this->ipset;
    }

    function autoWhitelistMAC( $mac)
    {
        return self::call_plugin('firewall_autowhitelist','autoWhitelistMAC', array('mac' => $mac));
    }

    /*
     * Check if macaddress is already authorized AutoWhitelist
     */
    function isMacAutoWhiteListed($mac) {
        return self::call_plugin('firewall_autowhitelist','isMacAutoWhiteListed', array('mac' => $mac));
    }
    function purgeAutoWhitelist() {
        $this->execCommand(self::call_plugin('firewall_autowhitelist','purgeAutoWhitelist'));
    }

    function getProxyInUse()
    {
        $result = "";
        $out = array();
        $IPTABLES = $this->getFirewallCommand();
        exec( $IPTABLES." -L PROXY -t nat  -n | egrep '^DNAT.*tcp dpt:80 to:' | sed 's/.*to:\\(.*\\)/\\1/'", $out);
        if( isset($out[0]))
        {
            #$tmp = preg_split("/ +/", $out[0]);
            $result = $out[0];
        }
        return $result;
    }

    function refreshBlackWhitelist( $start = true) {

        $BLACKLIST = getValueFromConf(CF, 'BLACKLIST');
        $WHITELIST = getValueFromConf(CF, 'WHITELIST');
        $WHITELIST_PROXY = getValueFromConf(CF, 'WHITELIST_PROXY');
        $SUPRAWHITELIST = getValueFromConf(CF, 'SUPRAWHITELIST');
        $IPTABLES = $this->getFirewallCommand();

        $commands = array(
                $IPTABLES . " -F BL ",
                $IPTABLES . " -F WL ",
                $IPTABLES . " -F BYPASS -t nat"
                );


        foreach( explode(" ", trim($SUPRAWHITELIST, '"')) as $mac) {
            if( $mac != "")
            {
                $commands[] = $IPTABLES . " -t nat -I BYPASS -m mac --mac " . $mac . " -j ACCEPT";
                $commands[] = $IPTABLES . " -I WL -m mac --mac " . $mac . " -j ACCEPT";
            }
        }

        if( $start == true) {
            foreach( explode(" ", trim($WHITELIST_PROXY,'"')) as $mac) {
                if( $mac != "")
                {
                    $commands[] = $IPTABLES . " -t nat -I BYPASS -m mac --mac " . $mac . " -j PROXY";
                    $commands[] = $IPTABLES . " -I WL -m mac --mac " . $mac . " -j ACCEPT";
                }
            }
            foreach( explode(" ", trim($WHITELIST,'"')) as $mac) {
                if( $mac != "")
                {
                    $commands[] = $IPTABLES . " -t nat -I BYPASS -m mac --mac " . $mac . " -j ACCEPT";
                    $commands[] = $IPTABLES . " -I WL -m mac --mac " . $mac . " -j ACCEPT";
                }
            }
            foreach( explode(" ", trim($BLACKLIST,'"')) as $mac) {
                if( $mac != "")
                {
                    $commands[] = $IPTABLES . " -t nat -I BYPASS -m mac --mac " . $mac . " -j ACCEPT";
                    $commands[] = $IPTABLES . " -I BL -m mac --mac " . $mac . " -j DROP";
                }
            }
        }


        $this->execCommand( $commands);
    }

}

firewall::loadPlugins("firewall");

