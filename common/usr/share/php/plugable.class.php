<?php

define( 'PLUGIN_DIR', '/usr/share/php/plugins/');
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

    function call_plugin($plugin, $method, $args =array())
    {
		$class =  $this->getName();
		$plugin_name = $class."_".strtolower($plugin);
		if( isset(self::$plugins[$plugin_name]) )
		{
			return @self::$plugins[$plugin_name]->$method($args);
		}
		else
		{
			return @self::$plugins[$class."_default"]->$method($args);
		}
    }
}
