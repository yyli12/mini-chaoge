<?php

require_once('Kijiji.php');

print "<p><a href=index.php>回主页</a></p>";

$id = @$_GET['id'];
$c = new Category();
$c->id = $id ? $id : 21;
$c->load();

//print_r($c->children());
foreach ($c->children() as $cc) {
//	print_r($cc);
	print "<b><a href=listing.php?id={$cc->id}>{$cc->name}</a></b><p>";
	foreach ($cc->children() as $cs) {
//		print_r($cs);
		print "<li><a href=listing.php?id={$cs->id}>{$cs->name}</a><br>";
	}
}
?>
