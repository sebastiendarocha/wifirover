<?php

class redirect_plugin {
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

    function getUrlRedirect()
    {
		$version = file_get_contents("/etc/version_wr");
		$webstr="?gtw-name=" . GTW. "&gtw-ip=" . GTWADDR . "&gtw-port=" . GTWPORT . "&user-ip=" . IP . "&user-mac=" . getRemoteMac() . "&user-url=" . $_SERVER['HTTP_HOST'] . "&borne-mac=" . getBorneMac() . "&captive-portal-version=" . $version;
		foreach( $_GET as $key => $value)
		{
			if( substr($key,0,3) == "uke")
			{
				switch( gettype( $value)) {
					case  "boolean":
					case  "integer":
					case  "double":
					case  "string":
						$webstr.= "&" . $key . "=" . urlencode($value);
				}
			}
		}
		return array(URL.$webstr);

	}
}
