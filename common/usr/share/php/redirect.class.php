<?php

include_once("redirect_plugin.class.php");
include_once("plugable.class.php");


class redirect extends plugable {
    function getListIgnoreSites()
    {
        return $this->foreach_plugins( "getListIgnoreSites");
    }

    function getUrlRedirect()
    {
        return $this->call_plugin("redirect_default", "getUrlRedirect");
    }

    function getName() { return __CLASS__; }
}

redirect::loadPlugins("redirect");
