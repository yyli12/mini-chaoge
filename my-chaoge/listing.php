<?php

require_once('data.php');

print "<p><a href=index.php>回主页</a></p>";

$id = @$_GET['id'];
$c = new Category();
$c->id = $id ? $id : 2101;

$c->load();


print "<h1>$c->name</h1><p>";

foreach ($c->toRoot() as $cc) {
	print "<a href=listing.php?id={$cc->id}>{$cc->name}</a>|";
}

print "<p>";

foreach ($c->children() as $cc) {
	print "<a href=listing.php?id={$cc->id}>{$cc->name}</a>|";
}

print "<p>";

foreach ($c->ads() as $ad) {
	print "<li><a href=view.php?id={$ad->id}>{$ad->name}</a>";
}

?>
