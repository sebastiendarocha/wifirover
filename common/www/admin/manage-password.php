<h2>Mot de passe</h2>
<?php
if (isset($_POST['newpassword'])) {
	if ($_POST['newpassword'] != '') {
		$hash = crypt($_POST['newpassword'], base64_encode($_POST['newpassword']));
		$content = file_get_contents('/root/htpasswd');
		$tmp = explode(":", $content);
		$tmp[1] = $hash . "\n";
		file_put_contents('/root/htpasswd', implode(":", $tmp));
	}
}
?>

<script language="javascript">
function checkForm() {
	if (document.getElementById('newpassword').value != document.getElementById('confirm').value) {
		alert('Les mots de passe ne correspondent pas!');
		return false;
	}
	return true;
}
</script>
<form method="post" action="" autocomplete="off" onSubmit="checkForm();">
<fieldset><legend>Changer le mot de passe</legend>
Mot de passe: <input id="newpassword" name="newpassword" type="password" value=""/><br/>
Confirmation: <input id="confirm" name="confirm" type="password" value=""/><br/>
<input type="submit" value="Valider"/>
</fieldset> 
</form>

