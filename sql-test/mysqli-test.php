<?php
/**
 * Created by PhpStorm.
 * User: yyli
 * Date: 2015/7/20
 * Time: 10:35
 */


$conn = new PDO("mysql:host=localhost;dbname=chaoge","root","");
$conn->query('set names utf8');


$sql = "select * from babel_node where ? = ?";
$sth = $conn->prepare($sql);
$sth->execute(array("node_id", "2101"));
$row = $sth->fetch();

print_r($row);


//if ($row) {
//    foreach ($this->columns as $objCol => $dbCol) {
//        $this->$objCol = $row["$dbCol"];
//    }
//    return $this;
//} else {
//    return null;
//}