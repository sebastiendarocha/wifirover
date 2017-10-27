<?php
define('LOCKFILE', '/tmp/leases.lock');
define('LEASEFILE', '/tmp/leases.wr');

if( file_exists( LEASEFILE) == false)
{
    touch(LEASEFILE);

    if( file_exists('/etc/debian_version')) { // Debian
        chown(LEASEFILE, 'www-data');
    }
}

if( file_exists( LOCKFILE) == false)
{
    touch(LOCKFILE);

    if( file_exists('/etc/debian_version')) { // Debian
        chown(LOCKFILE, 'www-data');
    }
}

include_once('firewall.class.php');
include_once("connexion_plugin.class.php");
include_once("plugable.class.php");

class connexion extends plugable {
    static $plugins = array();
    static $type = "connexion";

    function __construct ()
    {
        $this->cf = '/etc/wifi_rover.conf';
        $this->lock = fopen(LOCKFILE , 'r+');
        if( $this->lock === false)
        {
            die("Error opening " . LOCKFILE);
        }
    }

    function getName() { return __CLASS__; }

    /**
     * \param mac
     * \param $ip
     * \param date_end
     * \param lifespan
     */
    function connectUser($mac,$ip,$date_end="",$lifespan="", $get = array()) {
        if ( is_numeric($date_end) ) {
            $lease_time = $date_end;
        } elseif (is_numeric($lifespan)) {
            $lease_time = time() + $lifespan;
        } else {
            $lease_time = time() + getValueFromConf($this->cf, 'CTIMEOUT');
        }
        $this->foreach_plugins( "connectUser", $get);

        $was_connected = $this->isUserConnected( $mac);
        // If user isn't already connected
        if( $was_connected == false) 
        {
            // Create a lease
            $this->lockLeaseFile();
            $lease = $lease_time . '#' . $ip . '#' . strtoupper($mac);
            file_put_contents( LEASEFILE, $lease.PHP_EOL, FILE_APPEND);
            $this->unlockLeaseFile();

            // Get an array of connected user
            $macs_connected = $this->getConnectedUsers();
            if( ! in_array(strtoupper($mac), $macs_connected))
            {
                error_log( "connection not recorded");
            }   

            // Synchronise firewall on array
            $fw = new firewall();
            $fw->synchronizeConnexions( $macs_connected);

            $this->foreach_plugins( "connectedUser", $get);
        }
        return $was_connected;
    }

    function isUserConnected($mac) {
        $this->lockLeaseFile(true);
        $connected = preg_match( "/$mac/i", file_get_contents(LEASEFILE));
        $this->unlockLeaseFile();

        if( $connected === false) 
        {
            error_log( "error reading leases file"); 
        } 

        return ($connected === 1);

    }

    function clearConnexions() {
        $this->lockLeaseFile();
        file_put_contents( LEASEFILE, "");
        if( getLinuxFlavour() == 'debian')
        {
            chown( LEASEFILE, "www-data");
            chgrp( LEASEFILE, "www-data");
        }
        $this->unlockLeaseFile();
    }

    /**
     * \param $lease_time connection time
     */
    function disconnectUsers() {
        $new_leases = array();
        $users_disconnected = array();

        // Remove all expired connection
        $this->lockLeaseFile();
        $leases = file( LEASEFILE);
        foreach( $leases as $lease)
        {
            $lease = trim($lease);
            if($lease != "" )
            {
                list($time, $ip, $mac) = explode("#", $lease);
                if( $time > time())
                {
                    $new_leases[] = $lease;
                }
                else
                {
                    $users_disconnected[$ip] = $mac;
                }
            }
        }
        if( count($users_disconnected) != 0)
        {
            file_put_contents(LEASEFILE, implode(PHP_EOL,$new_leases).PHP_EOL);
        }
        $this->unlockLeaseFile();

        // Get an array of connected user
        $macs_connected = $this->getConnectedUsers();

        // Synchronise firewall on array
        $fw = new firewall();
        $fw->synchronizeConnexions( $macs_connected);
        return $users_disconnected;
    }

    function lockLeaseFile( $readOnly = false) {
        $operation = LOCK_EX;
        if( $readOnly == true)
        {
            $operation = LOCK_SH;
        }

        return flock($this->lock, $operation);
    }

    function unlockLeaseFile() {
        $operation = LOCK_UN;
        return flock($this->lock, $operation);
    }
    
    function getConnectedUsers() {
        $result = array();

        $this->lockLeaseFile(true);
        $leases = file( LEASEFILE);
        $this->unlockLeaseFile();

        foreach( $leases as $lease)
        {
            $lease = trim($lease);
            if($lease != "" )
            {
                list($time, $ip, $mac) = explode("#", $lease);
                $result[] = $mac;
            }
        }

        return $result;
    }
}

connexion::loadPlugins("connexion");

