<?php
include_once('common.php');
include_once('connexion.class.php');
loadEnv();

/**
 * This script opens the connection to a client that could
 * give right authentication informations
 **/

if( ! isset($_GET["autowhitelist"]))
{
    $_GET["autowhitelist"] = "";
}
$message = 'Connecting ' . $_GET['user-ip'] . ' ' . $_GET['user-mac'] . " autowhitelist? '" . $_GET["autowhitelist"] . "'";
sendToLog($message);

//Given token is correct opens the firewall
if (compareToken($_GET['token'], $_GET['user-ip'] . $_GET['user-mac'] . $_GET['timestamp'] . $_GET["autowhitelist"])) {
    if (checkTokenAge($_GET['timestamp'])) { // Token has not expired opening firewall
        $cnx = new connexion();

        $was_connected = $cnx->connectUser($_GET['user-mac'],  $_GET['user-ip'], $_GET);
        // if user wasn't connected, informe about connexion
        if ( $was_connected == false)
        {
            @informServerAboutCnx();
        }

//        sleep(2);
        setHeader();

    } else {// Token has expired
        $message = 'Token expired';
        echo $message;
        sendToLog($message);
    }
} else { // Token is incorrect
    $message = 'Incorrect token';
    echo $message;
    sendToLog($message);
}
