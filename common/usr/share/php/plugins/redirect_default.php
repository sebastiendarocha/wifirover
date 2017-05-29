<?php
include_once('common.php');
include_once('redirect.class.php');

redirect::addPlugin(new redirect_default());

class redirect_default extends redirect_plugin {

}
