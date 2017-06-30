<?php
class MemcachedContentModel extends MosaicModel{
	const TOTAL_PRIVATE_USER_KEY = "total.private.user.[:gender]";

	protected $_db = null;

	function getPrivateUserNum($gender = 0){
		if ($gender < 1 || $gender > 2)
			return 0;

		$key = str_replace('[:gender]', $gender, self::TOTAL_PRIVATE_USER_KEY);

		$total = intval($this->_memcached->get($key));

		return $total;
	}

	function updatePrivateUserNum($gender = 0){
		if ($gender < 1 || $gender > 2)
			return;

		$key = str_replace('[:gender]', $gender, self::TOTAL_PRIVATE_USER_KEY);

		$sql = "select count(1) from users where gender = $gender and private_num > 0";
		$stmt = $this->_db->query($sql);

		$total = intval($stmt->fetchColumn());

		$this->_memcached->set($key,$total);
	}

	


}
?>