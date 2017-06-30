<?php
class SummaryModel extends MosaicModel{
	function getRechargeSummary(){
		//	获取今天和昨天充值金额
		$today = date('Y-m-d',time());	
		$yesterday = date('Y-m-d',time()-24*3600);

		$today_time = strtotime($today);
		$yesterday_time = strtotime($yesterday);

		$sql = "select sum(price) as total,source,left(recharge_time,10) as recharge_day from recharge_log where status>0 and recharge_time > from_unixtime($yesterday_time) group by left(recharge_time,10),`source`";
		
		$stmt = $this->_db->query($sql);
		
		$ary = $stmt->fetchAll();

		

		$t_total = 0;
		$y_total = 0;
		$t_apple = 0;
		$y_apple = 0;
		$t_alipay = 0;
		$y_alipay = 0;

		foreach ($ary as $info){
			$total = intval($info['total']);
			$recharge_day = $info['recharge_day'];
			$source = $info['source'];
			if ($recharge_day==$today){
				if ($source=='apple')
					$t_apple += floatval($total);
				else
					$t_alipay += floatval($total);
			}else{
				if ($source=='apple')
					$y_apple += floatval($total);
				else
					$y_alipay += floatval($total);
			}
		} 

		$t_total = $t_apple + $t_alipay;
		$y_total = $y_apple + $y_alipay;

		$y_info = array('total'=>$y_total,'apple'=>$y_apple,'alipay'=>$y_alipay);
		$t_info = array('total'=>$t_total,'apple'=>$t_apple,'alipay'=>$t_alipay);

		$day_info = array('today'=>$t_info,'yesterday'=>$y_info);
		//	获取本月和上月充值金额
		$c_month = date('Y-m',time());
		$p_month = date('Y-m',strtotime($c_month)-24*3600);
		$p_time = strtotime($p_month);

		$sql = "select sum(price) as total,source,left(recharge_time,7) as recharge_month from recharge_log where status>0 and recharge_time > from_unixtime($p_time) group by left(recharge_time,7),`source`";
		$stmt = $this->_db->query($sql);
		
		$ary = $stmt->fetchAll();
		$p_total = 0;
		$c_total = 0;
		$p_apple = 0;
		$c_apple = 0;
		$p_alipay = 0;
		$c_alipay = 0;

		foreach ($ary as $info){
			$total = intval($info['total']);
			$recharge_month = $info['recharge_month'];
			$source = $info['source'];
			if ($recharge_month==$p_month){
				if ($source=='apple')
					$p_apple += floatval($total);
				else
					$p_alipay += floatval($total);
			}else{
				if ($source=='apple')
					$c_apple += floatval($total);
				else
					$c_alipay += floatval($total);
			}
		} 

		$p_total = $p_apple + $p_alipay;
		$c_total = $c_apple + $c_alipay;

		$p_info = array('total'=>$p_total,'apple'=>$p_apple,'alipay'=>$p_alipay);
		$c_info = array('total'=>$c_total,'apple'=>$c_apple,'alipay'=>$c_alipay);

		$month_info = array('last_month'=>$p_info,'current_month'=>$c_info);
		
		


		return array('day'=>$day_info,'month'=>$month_info);
	}

	function getRegisterSummary(){
		//	获取今天和昨天用户注册量
		$today = date('Y-m-d',time());	
		$yesterday = date('Y-m-d',time()-24*3600);

		$today_time = strtotime($today);
		$yesterday_time = strtotime($yesterday);

		$sql = "select count(*) as total,left(register_time,10) as register_day,gender from `users` where is_internal=0 and register_time > from_unixtime($yesterday_time) group by left(register_time,10),gender";
		
		$stmt = $this->_db->query($sql);
		
		$ary = $stmt->fetchAll();

		

		$t_total = 0;
		$y_total = 0;
		$t_male = 0;
		$y_male = 0;
		$t_female = 0;
		$y_female = 0;

		foreach ($ary as $info){
			$total = intval($info['total']);
			$register_day = $info['register_day'];
			$gender = intval($info['gender']);
			if ($register_day==$today){
				if ($gender==1)
					$t_male += floatval($total);
				else
					$t_female += floatval($total);
			}else{
				if ($gender==1)
					$y_male += floatval($total);
				else
					$y_female += floatval($total);
			}
		} 

		$t_total = $t_male + $t_female;
		$y_total = $y_male + $y_female;

		$y_info = array('total'=>$y_total,'male'=>$y_male,'female'=>$y_female);
		$t_info = array('total'=>$t_total,'male'=>$t_male,'female'=>$t_female);

		$day_info = array('today'=>$t_info,'yesterday'=>$y_info);
		//	获取本月和上月用户注册量
		$c_month = date('Y-m',time());
		$p_month = date('Y-m',strtotime($c_month)-24*3600);
		$p_time = strtotime($p_month);

		$sql = "select count(*) as total,left(register_time,7) as register_month,gender from `users` where is_internal=0 and register_time > from_unixtime($p_month) group by left(register_time,7),gender";
		$stmt = $this->_db->query($sql);
		
		$ary = $stmt->fetchAll();
		$p_total = 0;
		$c_total = 0;
		$p_male = 0;
		$c_male = 0;
		$p_female = 0;
		$c_female = 0;

		foreach ($ary as $info){
			$total = intval($info['total']);
			$recharge_month = $info['recharge_month'];
			$gender = intval($info['gender']);
			if ($recharge_month==$p_month){
				if ($gender==1)
					$p_male += floatval($total);
				else
					$p_female += floatval($total);
			}else{
				if ($gender==1)
					$c_male += floatval($total);
				else
					$c_female += floatval($total);
			}
		} 

		$p_total = $p_male + $p_female;
		$c_total = $c_male + $c_female;

		$p_info = array('total'=>$p_total,'male'=>$p_male,'female'=>$p_female);
		$c_info = array('total'=>$c_total,'male'=>$c_male,'female'=>$c_female);

		$month_info = array('last_month'=>$p_info,'current_month'=>$c_info);

		return array('day'=>$day_info,'month'=>$month_info);
	}

	function getRechargeLog($recharge_time = 0){
		if (0 == $recharge_time)
			$recharge_time = time();

		$sql = "select u.uid,u.nickname,r.`subject`,r.`source`,r.`price`,u.gender,u.province,u.city,u.marriage_status,unix_timestamp(r.recharge_time) as recharge_time,unix_timestamp(u.register_time) as register_time,unix_timestamp(u.last_visit_time) as last_visit_time,(unix_timestamp(r.recharge_time) - unix_timestamp(u.register_time)) as recharge_duration,(year(curdate()) - `year`) as `age`,avatar from recharge_log r left join users u on u.uid = r.uid where r.status = 1 and r.recharge_time < from_unixtime({$recharge_time}) and u.uid is not null order by r.recharge_time desc limit 20";
		$stmt = $this->_db->query($sql);

		$ary = $stmt->fetchAll();

		$user_model = new UserModel();
		$ary = $user_model->appendAvatars($ary);

		return $ary;
	}

	function getRegisterLog(){
		$sql = "select count(1) as total,count(if(gender=1,1,null)) as male,count(if(gender=2,1,null)) as female,left(register_time,10) as register_day from `users`  group by left(register_time,10) order by register_time desc limit 100";

		$stmt = $this->_db->query($sql);

		$ary = $stmt->fetchAll();

		return $ary;
	}

	function getAllStatics() {
		$sql = "SELECT count(1) AS `pays`, left(create_time,7) AS `date` FROM orders WHERE status > 0 GROUP BY left(create_time,7) ORDER BY create_time DESC";
		$stmt = $this->_db->query($sql);
		$pays_per_month = $stmt->fetchAll();

		$sql = "SELECT count(1) AS `pays`, o.shop_id, s.name FROM orders o LEFT JOIN `shops` s ON o.shop_id = s.shop_id WHERE o.status > 0 GROUP BY o.shop_id";
		$stmt = $this->_db->query($sql);
		$pays_per_restaurant = $stmt->fetchAll();

		$sql = "SELECT SUM(price) `amount` FROM orders WHERE status > 0 GROUP BY shop_id";
		$stmt = $this->_db->query($sql);
		$amount_per_restaurant = $stmt->fetchAll();

		$sql = "SELECT SUM(price) `amount` FROM orders WHERE status > 0 GROUP BY uid";
		$stmt = $this->_db->query($sql);
		$amount_per_account = $stmt->fetchAll();

		$sql = "SELECT count(1) from `orders` where status > 0";
		$stmt = $this->_db->query($sql);
		$total_pays = intval($stmt->fetchColumn());

		$sql = "SELECT sum(price) from `orders` where status > 0";
		$stmt = $this->_db->query($sql);
		$total_amount = floatval($stmt->fetchColumn());

		$sql = "SELECT count(1) from `users` where shop_id = 0";
		$stmt = $this->_db->query($sql);
		$total_account = floatval($stmt->fetchColumn());

		$sql = "SELECT count(1) from `shops` where shop_id > 0";
		$stmt = $this->_db->query($sql);
		$total_restaurant = intval($stmt->fetchColumn());


		$sql = "SELECT count(1) FROM orders group by left(create_time,7)";
		$stmt = $this->_db->query($sql);
		$total_month = intval($stmt->fetchColumn());

		$ppm = $total_month > 0 ? $total_pays / $total_month : 0;
		$ppr = $total_restaurant > 0 ? $total_pays / $total_restaurant : 0;
		$apr = $total_restaurant > 0 ? $total_amount / $total_restaurant : 0;
		$apa = $total_account > 0 ? $total_account / $total_account : 0;

		$summaryInfo = array('ppm'=>$ppm,'ppr'=>$ppr,'apr'=>$apr,'apa'=>$apa);

		$sql = "SELECT count(1) as ranking, o.shop_id, s.name FROM orders o LEFT JOIN shops s ON o.shop_id = s.shop_id WHERE status > 0 GROUP BY shop_id ORDER BY ranking desc LIMIT 10";

		$stmt = $this->_db->query($sql);

		$ary = $stmt->fetchAll();

		$top_list = array();

		$count = 1;
		foreach ($ary as $item) {
			$item['rank'] = $count;
			$count++;
			$top_list[] = $item;
		}
		
		$summaryInfo['shops'] = $top_list;

		return $summaryInfo;


	}
}
?>