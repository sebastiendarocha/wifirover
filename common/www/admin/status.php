<h2>Status</h2>
<?php
exec("ifconfig br-lan", $lan, $errlan);
exec("ifconfig eth0.2", $wan, $errwan);
exec("ifconfig tun0",  $vpn, $errvpn);
?>
<fieldset><legend>R&eacute;seau</legend>
<h3>Connexion r&eacute;seau local</h3>
<?php
if ($errlan == 0) {
	echo "<pre>";
	echo implode("\n", $lan);
	echo "</pre>";

} else {
	echo "Non connect&eacute;";
}
?>
<h3>Connexion &agrave; Internet</h3>
<?php
if ($errwan == 0) {
	echo "<pre>";
	echo implode("\n", $wan);
	echo "</pre>";

} else {
	echo "Non connect&eacute;";
}
?>
<h3>Connexion Vpn</h3>
<?php
if ($errvpn == 0) {
	echo "<pre>";
	echo implode("\n", $vpn);
	echo "</pre>";

} else {
	echo "Non connect&eacute;";
}
?>
</fieldset>
