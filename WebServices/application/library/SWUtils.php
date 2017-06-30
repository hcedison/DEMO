<?php
require_once dirname(__FILE__)."/XingeApp.php";

function print_sw($param){
	$DEBUG_MODE = false;

	if ($DEBUG_MODE)
		echo $param."<br/>";
}

class SWUtils{
	const MAX_NICKNAME_LENGTH = 7;

	static function getPDO($table_name,$is_test = false){
		$host = "rdsvnuuuauvvuae.mysql.rds.aliyuncs.com";

		if (!($_SERVER['SERVER_ADDR']=='218.244.130.177' || $_SERVER['SERVER_ADDR']=='10.162.50.101')){
        	$host = 'rdsvnuuuauvvuaepublic.mysql.rds.aliyuncs.com';
    	}

		$dn = "mysql:host=$host;dbname=$table_name";
		$pdo = new PDO($dn,"mosaic","showmeovrnylk");

		$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 
		$pdo->exec("set names 'utf8';");

		return $pdo;
	}
}




?>