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
if( ! isset($_GET['date_end']))
{
    $_GET['date_end'] = "";
}
if( ! isset($_GET['lifespan']))
{
    $_GET['lifespan'] = "";
}
$message = 'Connecting ' . $_GET['user-ip'] . ' ' . $_GET['user-mac'].' '. $_GET['date_end'].' '. $_GET['lifespan'] .  " autowhitelist? '" . $_GET["autowhitelist"] . "'";
sendToLog($message);

//Given token is correct opens the firewall
if (compareToken($_GET['token'], $_GET['user-ip'] . $_GET['user-mac'] . $_GET['timestamp'] . $_GET['date_end'] . $_GET['lifespan'] . $_GET["autowhitelist"])) {
    if (checkTokenAge($_GET['timestamp'])) { // Token has not expired opening firewall
        $cnx = new connexion();

        $was_connected = $cnx->connectUser($_GET['user-mac'], $_GET['user-ip'], $_GET['date_end'], $_GET['lifespan'], $_GET);

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
