<?php
include_once('/www/common.php');
include_once('redirect.class.php');

redirect::addPlugin(new redirect_default());

class redirect_default extends redirect_plugin {

    function getListIgnoreSites( $get = array()) {

        $ignore = array(
                "client-lb.dropbox.com",
                "configuration.apple.com",
                "d.dropbox.com",
                "download.windowsupdate.com",
                "e-cdn-proxy-9.deezer.com",
                "enablers.bouygtel.fr",
                "gllto.glpals.com",
                "images.apple.com",
                "iphone-wu.apple.com",
                "live.deezer.com",
                "mesu.apple.com",
                "notify1.dropbox.com",
                "notify2.dropbox.com",
                "notify4.dropbox.com",
                "notify5.dropbox.com",
                "notify9.dropbox.com",
                "phobos.apple.com",
                "safebrowsing.clients.google.com",
                "samsung-mobile.query.yahooapis.com",
                "www.update.microsoft.com",
                "ctldl.windowsupdate.com",
                "cdn.samsungcloudsolution.com",
                );
        return $ignore;
    }
}
