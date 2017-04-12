<?php
include('../common.php');
loadEnv();
if (ADMINACTIVE==0) {
    header("Location: /");
}
?>

<html>
<head>
<style>
* { font-family: arial; font-size=10px; }
h2 { background:#0044AA; color:#ffffff; }
form { padding:0px; margin:0px; }
.menu { float:left; clear:right; height:100%; background:#0044AA; color:#ffffff;padding:8px; margin-right:10px; }
.menu * { color:#ffffff; text-decoration:none; font-weight:bold;  }
</style>

</head>
<body>
<div class="menu">
Menu<br/>
<a href="/admin">Status</a></br>
<a href="?dhcp">Gestion DHCP</a><br/>
<a href="?mac-filter">Filtrage adresses mac</a><br/>
<a href="?manage-password">Mot de passe</a><br/>
</div>
<div class="content">
<?php
$action = array_keys($_REQUEST);
if (isset($action[0])) {
	include($action[0] . ".php");
} else {
	include("status.php");
}
?>
</div>
</body>
</html>
