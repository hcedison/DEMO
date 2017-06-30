<?php
$list = array(1,2,3);
$founded = array_search('2',$list);
var_dump($founded);
if ($founded)
	echo "founded";
array_splice($list,$founded,1);
var_dump($list);
?>
