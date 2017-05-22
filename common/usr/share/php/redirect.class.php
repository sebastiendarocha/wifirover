<?php

include_once("redirect_plugin.class.php");
include_once("plugable.class.php");
include_once('common.php');

class redirect extends plugable {
    function getListIgnoreSites()
    {
        return $this->foreach_plugins( "getListIgnoreSites");
    }

    function getUrlRedirect()
    {
        return $this->call_plugin( getValueFromConf("/etc/wifi_rover.conf", 'PORTALMODE'), "getUrlRedirect");
    }

    function getName() { return __CLASS__; }
}

redirect::loadPlugins("redirect");
