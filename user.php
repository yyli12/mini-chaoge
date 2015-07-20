<?php

require_once('Kijiji.php');

$id = @$_GET['id'];
$c = new User();
$c->id = $id ? $id : 10000002;
$c->load();

print "<h1>$c->name</h1><p>";

foreach ($c->ads() as $ad) {
	print "<li><a href=view.php?id={$ad->id}>{$ad->name}</a>";
}

?>
