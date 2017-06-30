<?php
class AccountModel extends MosaicModel{
	private $uid;

	function __construct($uid){
		parent::__construct();
		$this->uid = intval($uid);
	}	

	function getAccountList($page = 1, $uid = 0, $is_admin = 0){
		$page = $page >= 1 ? $page : 1;
		$offset = ($page - 1) * 100;

		$uid_order = $uid > 0 ? " uid = $uid desc, " : '';
		$group_filter = $is_admin > 0 ? " and group_id > 0 " : " and group_id = 0 ";
		
		$sql = "select uid,name,idcard_no,phone,balance,group_id from users where 1 $group_filter  order by $uid_order create_time desc limit $offset, 100";

		$stmt = $this->_db->query($sql);

		$ary = $stmt->fetchAll();

		$sql = "SELECT count(1) from users where 1 $group_filter";
		$stmt = $this->_db->query($sql);

		$total = intval($stmt->fetchColumn());
		$total_pages = intval($total / 100) + ($total % 100 == 0 ? 0 : 1);

		return array('total'=>$total, 'total_pages'=>$total_pages, 'list'=>$ary);
	}

	function updateAccount($uid, $profile){
		$valid_keys = explode(',', "balance,group_id,unlimited_search");

		$keys = array();
		$values = array();

		foreach ($valid_keys as $key) {
			if (isset($profile[$key])){
				$keys[] = $key;
				$values[] = $profile[$key]; 
			}
		}

		$sql = "UPDATE `users` set ";
		$sql .= implode(' = ?, ', $keys);
		$sql .= " = ? where uid = $uid";

		$stmt = $this->_db->prepare($sql);
		$stmt->execute($values);
	}

	function deleteAccount($uid, $start_date, $end_date){
		if (!empty($uid)){
			$sql = "DELETE FROM `users` WHERE uid = $uid";
			$row = intval($this->_db->exec($sql));

			return $row;
		}else{
			$sql = "DELETE FROM `users` WHERE register_time >= from_unixtime($start_date) AND register_time <= from_unixtime($end_date)";

			$row = intval($this->_db->exec($sql));

			return $row;
		}
		
	}

	function prepareAccount($name, $idcard_no, $phone, $group_id = 0){
		$password = substr($idcard_no, -6, 6);
		$password = hash_hmac('sha1', $password, 'mosaic');


		$sql = "INSERT INTO `users` (`name`,idcard_no,phone,group_id,password) values (?,?,?,?,?)";
		$stmt = $this->_db->prepare($sql);
		$result = $stmt->execute(array($name,$idcard_no,$phone,$group_id,$password));

		return $result;
	}

	function prepareShopAccount($name, $contact_name, $phone){
		$sql = "INSERT INTO `users` (`name`,phone,group_id) values (?,?,1)";
		$stmt = $this->_db->prepare($sql);
		$result = $stmt->execute(array($contact_name,$phone));

		$uid = intval($this->_db->lastInsertID());

		$sql = "INSERT INTO `shops` (uid, name) VALUES (?,?)";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($uid, $name));

		$shop_id = intval($this->_db->lastInsertID());

		$sql = "UPDATE users SET shop_id = $shop_id WHERE uid = $uid";
		$this->_db->exec($sql);

		return $result;
	}

	function rechargeAccount($uid, $balance){
		$sql = "UPDATE `users` set balance = balance + $balance where uid = $uid";

		$this->_db->exec($sql);
	}

	function searchAccount($name, $idcard_no, $phone, $is_admin = 0) {
		$page = $page >= 1 ? $page : 1;
		$offset = ($page - 1) * 100;

		$uid = $this->uid;

		$sql = "SELECT * from `users` where 1 ";
		$sql_total = "SELECT count(1) from `users` where 1 ";
		$values = array();

		if (!empty($name)){
			$sql .= " and `name` like concat(?,'%') ";
			$sql_total .= " and `name` like concat(?,'%') ";
			$values[] = $name;
		}

		if (!empty($idcard_no)){
			$sql .= " and `idcard_no` like concat(?,'%') ";
			$sql_total .= " and `idcard_no` like concat(?,'%') ";
			$values[] = $idcard_no;
		}

		if (!empty($phone)){
			$sql .= " and `phone` like concat(?,'%') ";
			$sql_total .= " and `phone` like concat(?,'%') ";
			$values[] = $phone;
		}


		$sql .= $is_admin > 0 ? " and `group_id` > 0 " : " and `group_id` = 0 ";
		$sql_total .= $is_admin > 0 ? " and `group_id` > 0 " : " and `group_id` = 0 ";



		$uid_filter = $uid > 0 ? " uid = $uid desc, " : '';

		$sql .= " order by $uid_filter uid desc limit $offset,100";
	
		$stmt = $this->_db->prepare($sql);
		$stmt->execute($values);

		$ary = $stmt->fetchAll();

		// get total num
		$stmt = $this->_db->prepare($sql_total);
		$stmt->execute($values);

		$total = intval($stmt->fetchColumn());
		$total_pages = intval($total / 100) + ($total % 100 == 0 ? 0 : 1);

		return array('total'=>$total, 'total_pages'=>$total_pages, 'list'=>$ary);
	}

	function submitProfile($profile){
		$valid_keys = explode(',', "name,idcard_no,phone,area,has_disease,marriage_status,has_kids,give_alimony,education,drive_license,job,salary,alipay_score,house,car,interest,resident_score,has_guarantor");

		$keys = array();
		$values = array();

		foreach ($valid_keys as $key) {
			if (isset($profile[$key])){
				$keys[] = $key;
				$values[] = $profile[$key];
			}
		}

		$score = 0;

		if (intval($profile['has_disease']) == 1)
			$score += 10;

		switch (intval($profile['marriage_status'])){
			case 1:
			case 2:
				$score += 6;break;
			default:
				$score += 3;break;
		}

		if (intval($profile['has_kids']) == 2)
			$score += 3;
		else
			$score += 1;

		if (intval($profile['give_alimony']) == 2)
			$score += 10;

		switch (intval($profile['education'])){
			case 2:
				$score += 1;break;
			case 3:
				$score += 3;break;
			case 4:
			case 5:
				$score += 5;break;
			default:break;
		} 
			$score += 5;

			if (intval($profile['drive_license']) == 2)
			$score += 3;

		if (intval($profile['resident_score']) > 0)
			$score += 10;
		if (intval($profile['has_guarantor']) == 2)
			$score += 10;
		
		switch (intval($profile['job'])){
			case 2:
				$score += 10;break;
			case 3:
				$score += 4;break;
			case 4:
				$score += 2;break;
			default:break;
		}

		if (intval($profile['salary']) >= 8000)
			$score += 4;
		else
			$score += 2;

		
		

		
		if (intval($profile['house']) == 1)
			$score += 5;
		else
			$score += 16;

		

		
		if (intval($profile['car']) == 1)
			$score += 4;
		else
			$score += 8;

		if (intval($profile['alipay_score']) >= 650)
			$score += 15;
		else
			$score += 5;

		$keys[] = 'score';
		$values[] = $score;

		$keys_string = implode(',', $keys);
		$qms = implode(',', array_fill(0, count($keys), '?'));

		$sql = "INSERT INTO `score_records` ({$keys_string}) VALUES ({$qms})";
		$stmt = $this->_db->prepare($sql);



		$stmt->execute($values);

		return $score;
	}

	function importUser($names, $idcard_nos, $phones){
		$passwords = array();

		foreach ($idcard_nos as $idcard_no) {
			$password = substr($idcard_no, -6, 6);
			$password = hash_hmac('sha1', $password, 'mosaic');

			$passwords[] = $password;
		}

		$sql = "INSERT INTO `users` (idcard_no, `name`, phone, password) values (?,?,?,?)";

		$stmt = $this->_db->prepare($sql);

		$total = count($names);

		for ($i=0; $i < $total; $i++) { 
			$stmt->execute(array($idcard_nos[$i], $names[$i], $phones[$i], $passwords[$i]));
		}
	}

	function getScoresList($page, $name, $idcard_no, $phone){
		$page = $page >= 1 ? $page : 1;
		$offset = ($page - 1) * 100;

		$uid = $this->uid;

		$sql = "SELECT * from `score_records` where 1 ";
		$sql_total = "SELECT count(1) from `score_records` where 1 ";
		$values = array();

		if (!empty($name)){
			$sql .= " and `name` like concat(?,'%') ";
			$sql_total .= " and `name` like concat(?,'%') ";
			$values[] = $name;
		}

		if (!empty($idcard_no)){
			$sql .= " and `idcard_no` like concat(?,'%') ";
			$sql_total .= " and `idcard_no` like concat(?,'%') ";
			$values[] = $idcard_no;
		}

		if (!empty($phone)){
			$sql .= " and `phone` like concat(?,'%') ";
			$sql_total .= " and `phone` like concat(?,'%') ";
			$values[] = $phone;
		}

		$sql .= " order by create_time desc limit $offset,100";
	
		$stmt = $this->_db->prepare($sql);
		$stmt->execute($values);

		$ary = $stmt->fetchAll();


		// get total num
		$stmt = $this->_db->prepare($sql_total);
		$stmt->execute($values);

		$total = intval($stmt->fetchColumn());
		$total_pages = intval($total / 100) + ($total % 100 == 0 ? 0 : 1);

		return array('total'=>$total, 'total_pages'=>$total_pages, 'list'=>$ary);
	}

	
}
?>