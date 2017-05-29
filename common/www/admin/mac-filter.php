<h2>Filtrage adresses mac</h2>
<form method="post" action="">
<fieldset><legend>Ajout d'une adresse mac</legend>
Mac<input type="text" name="mac" value=""/>
Commentaire<input type="text" name="comment" value=""/>
<input type="submit" value="Ajouter"/>
</fieldset>
</form>
<?php
if (file_exists("/etc/whitelist.json")) {                          
            $whitelist = json_decode(file_get_contents("/etc/whitelist.json"));
} else {                            
            $whitelist = null;           
}                        
if(isset($_POST['mac'])) {// Add mac
	if ($whitelist==null) {
		$whitelist = array();
	}
	$whitelist[] = array('mac' => $_POST['mac'], 'comment' => $_POST['comment']);
	file_put_contents("/etc/whitelist.json", json_encode($whitelist));
}
if(isset($_POST['del-mac'])) {// Delete mac
	foreach ($whitelist as $i => $entry) {
		if ($_POST['del-mac'] == $entry->mac) {
			unset($whitelist[$i]);
		}
	}
	file_put_contents("/etc/whitelist.json", json_encode($whitelist));
}

//Display table
if (file_exists("/etc/whitelist.json")) {                          
            $whitelist = json_decode(file_get_contents("/etc/whitelist.json"));
} else {                            
            $whitelist = null;           
}                        
if (is_array($whitelist) && count($whitelist) > 0) {
	echo "<table border=\"1\">";
	echo "<tr><th>Mac</th><th>Commentaire</th><td></td></tr>";
	foreach ($whitelist as $entry) {
		echo "<tr><td>" . $entry->mac . "</td><td>" . $entry->comment . "</td>";
		echo "<td><form method=\"post\" action=\"\"><input type=\"hidden\" name=\"del-mac\" value=\"" . $entry->mac . "\"/><input type=\"submit\" value=\"x\"/></form></td>";
		
		echo "</tr>";
	
	}
	echo "</table>";
	//echo "<pre>"
	//var_dump($whitelist);
	//echo "</pre>";
}
?>
