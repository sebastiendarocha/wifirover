<?php

define( 'PLUGIN_DIR', '/opt/wifi_rover/lib/plugins/');
abstract class plugable {
    static $plugins = array();

    abstract function getName();
    static function loadPlugins($class_name)
    {
        $plugins_files = scandir(PLUGIN_DIR);
        $plugins = preg_grep( "/^" . $class_name . "_(.*)\.php$/", $plugins_files);
        foreach( $plugins as $plugin)
        {
            include_once( PLUGIN_DIR . '/' . $plugin);
        }
    }

    #static function addPlugin( plugin $obj)
    static function addPlugin( $obj)
    {
        if( !isset(self::$plugins[get_class($obj)]) )
        {
            self::$plugins[get_class($obj)] = $obj;
        }
    }

    function foreach_plugins($method, $args =array())
    {
        $class =  $this->getName();
        $result = array();

        foreach( self::$plugins as $plugin_name => $plugin)
        {
            if( preg_match( "/^" . $class . "/", $plugin_name))
            {
                $result = array_merge($result, $plugin->$method($args));
            }
        }
        return $result;
    }

    static function call_plugin($plugin, $method, $args =array())
    {
        return @self::$plugins[$plugin]->$method($args);
    }
}
