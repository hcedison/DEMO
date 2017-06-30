<?php
class ShopModel extends MosaicModel{
	function  getShopInfo($shop_id){
		$sql = "SELECT * FROM shops WHERE shop_id = $shop_id";

		$stmt = $this->_db->query($sql);

		$ary = $stmt->fetchAll();

		$shopInfo = $ary[0];

		//	get discount info
		$sql = "SELECT * FROM discounts WHERE shop_id = $shop_id AND is_valid > 0";
		$stmt = $this->_db->query($sql);

		$discounts = $stmt->fetchAll();

		$shopInfo['discounts'] = $discounts;

		return $shopInfo;
	}

	function discountedPrice($shop_id, $price){
		$sql = "SELECT * FROM discounts WHERE shop_id = $shop_id AND is_valid > 0";

		$stmt = $this->_db->query($sql);
		$discounts = $stmt->fetchAll();

		$discounted_price = $price;

		$min_price = $price;

		$price_info = array();
		$price_info['price'] = $price;
		$price_info['origin_price'] = $price;

		foreach ($discounts as $discount) {
			$discount_type = intval($discount['discount_type']);

			$factor1 = floatval($discount['factor1']);
			$factor2 = floatval($discount['factor1']);

			switch ($discount_type){
				case 0:
					$discounted_price = $price * $factor1 + $factor2;
					break;
				case 1:
					$discounted_price = $price > $factor1 ? ($price - $factor2) : $price;
					break;
				case 2:
					$discounted_times = intval($price / $factor1);
					$discounted_price = $price - $discounted_times * $factor2;
				default:
					break;
			}

			if ($discounted_price < $min_price){
				$min_price = $discounted_price;
				$price_info = $discount;
				$price_info['price'] = $min_price;
			}
		}

		return $price_info;
	}

	function getShopList($name, $contact_name, $phone, $page = 1){
		$page = $page >= 1 ? $page : 1;
		$offset = ($page - 1) * 100;

		$page = $page >= 1 ? $page : 1;
		$offset = ($page - 1) * 100;

		$uid = $this->uid;

		$sql = "SELECT * from `shops` where 1 ";
		$sql = "SELECT s.*, u.name as `owner_name`, u.phone FROM shops s LEFT JOIN users u ON u.uid = s.uid WHERE 1 ";
		$sql_total = "SELECT count(1) FROM `shops` WHERE 1 ";
		$values = array();

		if (!empty($name)){
			$sql .= " and s.`name` like concat(?,'%') ";
			$sql_total .= " and s.`name` like concat(?,'%') ";
			$values[] = $name;
		}

		if (!empty($contact_name)){
			$sql .= " and u.`name` like concat(?,'%') ";
			$sql_total .= " and u.`name` like concat(?,'%') ";
			$values[] = $contact_name;
		}

		if (!empty($phone)){
			$sql .= " and u.`phone` like concat(?,'%') ";
			$sql_total .= " and u.`phone` like concat(?,'%') ";
			$values[] = $phone;
		}


		$sql .= " order by u.shop_id desc limit $offset,100";
	
		$stmt = $this->_db->prepare($sql);
		$stmt->execute($values);

		$ary = $stmt->fetchAll();

		$ary = $this->appendDiscountInfo($ary);

		// get total num
		$stmt = $this->_db->prepare($sql_total);
		$stmt->execute($values);

		$total = intval($stmt->fetchColumn());
		$total_pages = intval($total / 100) + ($total % 100 == 0 ? 0 : 1);

		return array('total'=>$total, 'total_pages'=>$total_pages, 'list'=>$ary);
	}

	function getOrderList($shop_id, $uid, $page) {
		$shop_id_filter = $shop_id > 0 ? " AND o.shop_id = $shop_id " : " ";
		$uid_filter = $uid > 0 ? " AND o.uid = $uid " : " ";

		if ($page < 1)
			$page = 1;
		$offset = ($page - 1) * 100;

		$sql = "SELECT o.*, s.name AS shop_name, u.name as customer_name FROM orders o LEFT JOIN shops s ON s.shop_id = o.shop_id LEFT JOIN users u ON u.uid = o.uid WHERE o.status > 0 $shop_id_filter $uid_filter ORDER BY order_id DESC LIMIT $offset, 100";

		$stmt = $this->_db->query($sql);

		$orderList = $stmt->fetchAll();

		$sql = "SELECT count(1) FROM orders o WHERE o.status > 0 $shop_id_filter $uid_filter";
		$stmt = $this->_db->query($sql);

		$total = intval($stmt->fetchColumn());
		$total_pages = intval($total / 100) + ($total % 100 == 0 ? 0 : 1);

		return array('total'=>$total, 'total_pages'=>$total_pages, 'list'=>$orderList);
	}

	function getDiscountList($shop_id, $page) {
		$shop_id_filter = $shop_id > 0 ? " AND shop_id = $shop_id " : " ";

		if ($page < 1)
			$page = 1;
		$offset = ($page - 1) * 100;

		$sql = "SELECT * FROM discounts WHERE is_valid > 0 $shop_id_filter ORDER BY discount_id DESC LIMIT $offset, 100";

		$stmt = $this->_db->query($sql);

		$orderList = $stmt->fetchAll();

		return $orderList;
	}

	function appendDiscountInfo($shop_list) {
		if (empty($shop_list))
			return null;



		$shop_ids = array();

		foreach ($shop_list as $shop) {
			$shop_ids[] = $shop['shop_id'];
		}

		$shop_ids_str = implode(',', $shop_ids);

		$sql = "SELECT * FROM discounts WHERE shop_id IN ({$shop_ids_str})";


		$stmt = $this->_db->query($sql);

		$discount_list = $stmt->fetchAll();

		$discountInfo = array();

		foreach ($discount_list as $discount) {
			$shop_id = $discount['shop_id'];

			if (empty($discountInfo[$shop_id])){
				$discountInfo[$shop_id] = array($discount);
			} else {
				$ary = $discountInfo[$shop_id];
				$ary[] = $discount;

				$discountInfo = $ary;
			}
		}

		$ary = array();
		foreach ($shop_list as $shop) {
			$shop['discounts'] = $discountInfo[$shop['shop_id']];

			$ary[] = $shop;
		}

		return $ary;
	}

	function removeDiscount($discount_id){
		$sql = "DELETE FROM discounts WHERE discount_id = $discount_id";
		$this->_db->exec($sql);
	}

	function addDiscount($shop_id, $discount_type, $factor1 = 1, $factor2 = 0) {
		$sql = "INSERT INTO discounts (shop_id, discount_type, factor1, factor2) VALUES ($shop_id, $discount_type, $factor1, $factor2)";

		$this->_db->exec($sql);
	}
}
?>