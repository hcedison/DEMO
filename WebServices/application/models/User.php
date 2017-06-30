<?php

class UserModel extends MosaicModel{
	const MEMCACHED_USER_PROFILE_KEY = "user.profile.[:uid]";

	private $uid;
	private $_memcached_user = null;
	// public $uid,$nickname,$gender,$email,$sina_uid,$qq_open_id,$facebook,$phone,$password;
	public $birthday,$year,$height,$weight;
	// public $balance,$vip_expiration_time;
	// public $latitude,$longitude;

	const PROFILE_ITEMS = "uid,nickname,avatar,unix_timestamp(birthday) as birthday,if(birthday is null,0,year(curdate()) - year(birthday)) as `age`,`year`,weixin,weixin is not null as has_weixin,province,city,gender,latitude,longitude,unix_timestamp(vip_expiration_time) as vip_expiration_time,phone,sina_id,qq_id,email,facebook_id,balance,unix_timestamp(register_time) as register_time,unix_timestamp(last_visit_time) as last_visit_time,height,weight,geohash,current_timestamp < vip_expiration_time as is_vip,tags,gender_changed,is_super_vip,origin_province,origin_city,job,blood_type,salary,love_attitude,sex_attitude,education,marriage_status,marriage_duration,zodiac_sign,charm_part,has_kids,unlimited_search,group_id";
	const PROFILE_ITEMS_U = "u.avatar,u.uid,u.nickname,u.weixin,u.unlimited_search,u.weixin is not null as has_weixin,u.province,u.city,u.introduction,u.year,unix_timestamp(u.birthday) as birthday,if(u.birthday is null,0,year(curdate()) - year(u.`birthday`)) as `age`,u.gender,u.latitude,u.longitude,unix_timestamp(u.vip_expiration_time) as vip_expiration_time,u.phone,u.sina_id,u.qq_id,u.email,u.facebook_id,unix_timestamp(u.last_visit_time) as last_visit_time,u.height,u.weight,current_timestamp < vip_expiration_time as is_vip,tags,gender_changed,is_super_vip,origin_province,origin_city,u.blood_type,job,salary,love_attitude,sex_attitude,education,marriage_status,marriage_duration,zodiac_sign,charm_part,has_kids,group_id";

	function __construct($uid){
		parent::__construct();
		$this->uid = intval($uid);
		$this->_memcached_user = new MemcachedUserModel();
	}	

	function isPhoneUserExist($phone){
		$stmt = $this->_db->prepare("select count(*) from users where phone= ?");
		$stmt->execute(array($phone));

		$count = $stmt->fetchColumn();

		return $count>0;
	}

	function getPhoneUser($phone){
		$stmt = $this->_db->prepare("select uid,is_registered from users where phone = ?");
		$stmt->execute(array($phone));

		$ary = $stmt->fetchAll();

		return count($ary) > 0 ? $ary[0] : null;
	}

	function isDeviceUserExist($device_id){
		$stmt = $this->_db->prepare("select count(*) from users where device_id=?");
		$stmt->execute(array($phone));

		$count = $stmt->fetchColumn();

		return $count>0;
	}

	function createUser($phone,$password,$salt,$channel){
		$hashed_password = hash_hmac('sha1', $password, $salt);

		$sql = "update users set password = ?, is_registered = 1 where phone = ?";

		$stmt = $this->_db->prepare($sql);
		$result = $stmt->execute(array($hashed_password,$phone));
		if ($result){
			$sql = "SELECT uid FROM users where phone = ?";
			$stmt = $this->_db->prepare($sql);
			$stmt->execute(array($phone));


			$uid = intval($stmt->fetchColumn());

			return $uid;
		}else
			return 0;

	}

	function getUserData($uid = 0){
		$sql = "select * from users where uid = ?";

		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($uid));

		$user_data = (array)$stmt->fetchObject();
		// $user_data = $this->appendAvatar($user_data);

		return $user_data;
	}

	function getProfile($uid = 0){
		if (0==$uid)
			$uid = $this->uid;

		$profile = null;//$this->loadProfileCache($uid);
		if (empty($profile)){
			$items = "u.uid,unlimited_search,unix_timestamp(birthday) as birthday,if(birthday is null,0,year(curdate()) - year(birthday)) as `age`,blood_type,marriage_status,province,city,origin_province,origin_city,job,salary,education,introduction,gender,latitude,longitude,phone,balance,unix_timestamp(register_time) as register_time,unix_timestamp(last_visit_time) as last_visit_time,height,weight,geohash,group_id";

			$sql = "SELECT * FROM users u WHERE u.uid=?";

			$stmt = $this->_db->prepare($sql);
			$stmt->execute(array($uid));

			$profile = (array)$stmt->fetchObject();

			// $profile = $this->appendAvatar($profile);

			// $this->saveProfileCache($uid,$profile);
		}

		// if (intval($profile['is_super_vip']) > 0)
		// 	$profile['is_vip'] = 1;

		return $profile;
	}

	function updateUserProfile($profile){
		$sql = "update users set ";
		$keys = array();
		$values = array();


		if (isset($profile["latitude"]) && isset($profile["longitude"])){
			$latitude = doubleval($profile["latitude"]);
			$longitude = doubleval($profile["longitude"]);

			$geohasher = new Geohash();
			$geohash = $geohasher->encode($latitude,$longitude);
			$geohash = substr($geohash, 0,4);
			if ($geohash!=null)
				$profile["geohash"] = $geohash;
		}

		if (isset($profile['gender'])){
			$profile['gender_changed'] = 1;
		}

		



		foreach ($profile as $key=>$value){
			if ($key!="uid"){
				$keys[] = $key;
				$values[] = $value;
			}
		}

		for ($i=0;$i<count($keys);$i++){
			if ($i!=0)
				$sql .= ",";

			$key = $keys[$i];
			if ($key=="birthday")
				$sql .= $key."=from_unixtime(?)";
			else
				$sql .= $key."=?";
		}

		$sql .= " where uid=".$this->uid;

		$stmt = $this->_db->prepare($sql);

		$this->clearProfileCache($this->uid);

		$result = $stmt->execute($values);

		if ($result>0)
			return $stmt->rowCount()>0;
		else
			return false;

	}

	function removeActionLog($uid,$type,$content_id){
		if ($uid>0){
			if (0==intval($content_id))
				$sql = "delete from action_log where `to`=$uid and `type`=$type";
			else
				$sql = "delete from action_log where `to`=$uid and `content_id`=$content_id";
			$this->_db->exec($sql);
		}
	}

##	API相关函数
	function isUserExist($uid = 0){
		$uid = intval($uid);
		if (0==$uid)
			return false;
		else
			$sql = "select count(*) from users where uid=?";

		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($uid));

		return intval($stmt->fetchColumn())>0;
	}
	/*
	*	注册用户
	*/
	function registerAccount($phone,$password,$salt,$channel = null){
		if ($channel==null || strlen($channel)==0)
			$channel = "App Store";

		$uid = $this->createUser($phone,$password,$salt,$channel);
		$this->uid = intval($uid);

		$profile = $this->getProfile();
		
		return $profile;
	}

	function loginAccount($phone,$password){
		$sql = "select uid from `users` where phone = ?";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($phone));
		$uid = intval($stmt->fetchColumn());

		$password_hash = $password;

		$user_data = $this->getUserData($uid);

		$password = $user_data["password"];

		if (strtolower($password)==strtolower($password_hash)){
			//	登录成功
			$this->uid = intval($uid);
			$profile = $this->getProfile();

			return $profile;
		}else{
			//	密码错误
			return null;
		}
	}



	function searchUser($keyword,$page,$latitude = 0,$longitude = 0){
		$myuid = $this->uid;

		$location_data = $this->get_location_data($latitude,$longitude);
		$latitude = $location_data['latitude'];
		$longitude = $location_data['longitude'];

		$page = intval($page);
		if ($page<=0)
			$page = 1;
		$offset = 20*($page-1);

		$len = $this->utf8_strlen($keyword);

		$profile_items = self::PROFILE_ITEMS.",math_dist($latitude,$longitude,latitude,longitude) as distance";

		if ($len==strlen("".intval($keyword)) && intval($keyword)>0)
			$sql = "select $profile_items from users where uid =? and register_time < current_timestamp and status >=0 and group_id = 0 order by distance asc limit $offset,20";
		else{
			$sql = "select $profile_items from users where nickname like ? and register_time < current_timestamp and status >=0 and group_id = 0 order by distance asc limit $offset,20";
			$keyword = '%'.$keyword.'%';
		}

		$stmt = $this->_db->prepare($sql);

		$stmt->execute(array($keyword));

		$ary = $stmt->fetchAll();
		$ary = $this->appendAvatars($ary);
		$ary = $this->appendHeat($ary,self::DATA_TYPE_LIST);

		return $ary;
	}

	private function get_location_data($latitude = 0,$longitude = 0){
		$memcached_user = new MemcachedUserModel();
		$myuid = intval($this->uid);
		$latitude = doubleval($latitude);
		$longitude = doubleval($longitude);

		if (0==$latitude && 0==$longitude){
			$location = $memcached_user->getLocation($myuid);
			if (!empty($location)){
				$latitude = doubleval($location["latitude"]);
				$longitude = doubleval($location["longitude"]);
				$geohash = $location["geohash"];
			}else{
				$sql = "select latitude,longitude,geohash from `users` where uid=$myuid";
				$stmt = $this->_db->query($sql);
				$latitude = doubleval($stmt->fetchColumn());
				$longitude = doubleval($stmt->fetchColumn());
				$geohash = $stmt->fetchColumn();

				if (0!=$latitude && $longitude!=0){
					$memcached_user = new MemcachedUserModel();
					$memcached_user->updateLocation($myuid,$latitude,$longitude);
				}
			}
			
		}else{
			$memcached_user = new MemcachedUserModel();
			$memcached_user->updateLocation($myuid,$latitude,$longitude);
		}

		return array('latitude'=>$latitude,'longitude'=>$longitude);
	}

	function getUserList($gender,$province,$city,$min_age,$max_age,$page){
		$myuid = $this->uid;

		$gender = intval($gender);
		$location_data = $this->get_location_data($latitude,$longitude);
		$latitude = $location_data['latitude'];
		$longitude = $location_data['longitude'];

		$province_filter = $province > 0 ? " and u.province = $province " : " ";
		$city_filter = " ";

		$profile_items = self::PROFILE_ITEMS.",math_dist($latitude,$longitude,latitude,longitude) as distance";

		$page = intval($page);
		if ($page<=0)
			$page = 1;
		$offset = 20*($page-1);

		if ($gender==0)
			$gender_filter = "";
		else
			$gender_filter = "and gender=$gender";

		

		$geohasher = new Geohash();
		$geohash = $geohasher->encode($latitude,$longitude);

		$short_hash = substr($geohash,0,4);

		$black_filter = '';
		$blocked_uids = $this->getBlockedUIDs($myuid);
		if (!empty($blocked_uids)){
			$uids_filter = $this->get_filter_from_array($blocked_uids);
			$black_filter = ' and uid not in '.$uids_filter;
		}

		//$sql = "select $profile_items from users where geohash=? $gender_filter order by distance asc limit $offset,20";
		//	先不用geohash,保证用户周围有人 and !isnull(latitude) and !isnull(longitude)
		$sql = "select $profile_items from users where 1 and uid!=$myuid  and register_time < current_timestamp and status >=0 $gender_filter $black_filter and invisible = 0 order by $province_order latitude is not null desc,distance asc,last_visit_time desc limit $offset,20";

		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array());
		// $stmt = $this->_db->query($sql);

		$ary = $stmt->fetchAll();
		$ary = $this->appendAvatars($ary);

		return $ary;
	}


	function getDetailProfile($uid){
		$myuid = intval($this->uid);
		$uid = intval($uid);

		if ($uid==0)
			$uid = $myuid;

		$user = new UserModel($uid);
		$profile = $user->getProfile();
		$my_profile = $user->getProfile($myuid);

		$is_vip = intval($profile['is_vip']);
		$my_is_vip = intval($my_profile['is_vip']);
		$gender = intval($profile['gender']);
		$my_gender = intval($my_profile['gender']);

		//	个性签名
		if (empty($profile['introduction']) && $uid != $myuid)
			$profile['introduction'] = "还未填写";

		$weixin = $profile['weixin'];

		if ($uid!=$myuid && !empty($weixin) && !$my_is_vip){
			$prefix = mb_substr($weixin, 0, 2, 'utf-8');# substr($weixin, 0,2);
			$suffix = mb_substr($weixin, -1, 1, 'utf-8'); #substr($weixin, -1);

			$weixin = $prefix . "***" . $suffix;

			$profile['weixin'] = $weixin;
		}
		
		$avatar = $profile['avatar'];
        if (!empty($avatar) && !(strpos($avatar, 'http://') === 0)){
          $avatar = self::QINIU_URL_PREFIX . $avatar.self::MOSAIC_AVATAR_SMALL_SIXE;
          $profile['avatar'] = $avatar;
        }else if (strpos($avatar, 'http://') === 0 && !strpos($avatar, '?imageView')){
          $avatar = $avatar.self::MOSAIC_AVATAR_SMALL_SIXE;
          $profile['avatar'] = $avatar;
        }

        //	照片列表
       $photo_model = new PhotoModel();
       $photo_list = $photo_model->getPublicList($uid,$uid == $myuid);
       $profile['photo_list'] = $photo_list;
       $profile = $this->appendContacts($profile,self::DATA_TYPE_SINGLE);

       //	检查资料是否完成
       if ($uid == $myuid){
	       	$necessary_keys_str = "zodiac_sign,province,origin_province,height,weight,marriage_status,education,job,salary,charm_part,gender";
	       	// my_type,long_distance,house_status,
			$neccessary_keys = explode(',', $necessary_keys_str);

			$profile_completed = true;
			$incomplete_reasons = array();

			foreach ($neccessary_keys as $key) {
				if (!isset($profile[$key]) || intval($profile[$key]) == 0){
					$profile_completed = false;
					$incomplete_reasons[] = 'profile';
					break;
				}
			}

			if (count($photo_list) < 3){
				$profile_completed = false;
				$incomplete_reasons[] = 'photo';
			}

			$contact_list = $profile['contacts'];
			if (count($contact_list) == 0){
				$profile_completed = false;
				$incomplete_reasons[] = 'contact';
			}

			$profile['profile_completed'] = $profile_completed ? '1' : '0';
			$profile['incomplete_reasons'] = $incomplete_reasons;
       }

       

		return $profile;
	}

	function updateProfile($new_profile){
		$keys = explode(",","birthday,nickname,introduction,education,year,gender,province,city,origin_province,origin_city,latitude,longitude,height,weight,tags,weixin,love_attitude,sex_attitude,marriage_status,zodiac_sign,job,salary,charm_part,has_kids,blood_type,no_disturb,invisible");

		if (isset($new_profile['age'])){
			$age = intval($_REQUEST['age']);
			$year = intval(date('Y',time())) - $age;
			$new_profile['year'] = $year;
		}

		foreach ($keys as $key){
			if (isset($new_profile[$key]))
				$profile[$key] = $new_profile[$key];
		}

		$this->updateUserProfile($profile);

		return $this->getProfile();
	}

	function getBalance(){
		$myuid = $this->uid;	
		//	检查余额
		$sql = "select balance from users where uid=$myuid";
		$stmt = $this->_db->query($sql);
		$balance = intval($stmt->fetchColumn());

		return $balance;
	}

	function isPasswordCorrect($password){
		$sql = "SELECT password FROM `users` where uid = " . $this->uid;
		$stmt = $this->_db->query($sql);

		$pwd = $stmt->fetchColumn();

		return strtolower($password) == strtolower($pwd);
	}

	function changePassword($password){
		$myuid = $this->uid;

		$sql = "update users set password=? where uid=?";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($password,$myuid));
		
		return true;	
	}

	function resetPassword($phone, $password){
		$sql = "update users set password=? where phone=?";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($password,$phone));
		
		return true;
	}

	function getPrompts(){
		$myuid = $this->uid;

		$sql = "select * from action_log where `to`=$myuid";
		$stmt = $this->_db->query($sql);
		$ary = $stmt->fetchAll();

		$n_message = 0;
		$n_visit = 0;
		$n_follow = 0;
		$n_like = 0;
		$n_comment = 0;
		$n_reply = 0;
		$n_apply = 0;
		$n_intimate = 0;


		foreach($ary as $notice){
			$type = intval($notice['type']);
			switch ($type) {
				case 0:$n_message++;break;
				case 1:$n_visit++;break;
				case 2:$n_follow++;break;
				case 3:$n_like++;break;
				case 4:$n_comment++;break;
				case 5:$n_reply++;break;
				case 6:$n_apply++;break;
				case 7:$n_intimate++;break;
				default:break;
			}
		}

		$prompts = array("message"=>$n_message,"visit"=>$n_visit,"follow"=>$n_follow,"like"=>$n_like,"comment"=>$n_comment,"reply"=>$n_reply,"apply"=>$n_apply,"intimate"=>$n_intimate);
	
		return $prompts;
	}

	function getNoticeList($page){
		$myuid = $this->uid;

		$page = intval($page);
		if ($page<=0)
			$page = 1;
		$offset = ($page-1)*20;

		$sql = "select u.nickname,u.avatar,u.uid,a.type,unix_timestamp(a.action_time) as action_time,a.content_id,c.content_uri from action_log a left join `users` u on a.from=u.uid left join `content` c on c.content_id=a.content_id where `to`=$myuid and `type`>=3 and `type`<=5 limit $offset,20";

		$stmt = $this->_db->query($sql);

		$ary = $stmt->fetchAll();
		$ary = $this->appendAvatars($ary);

		$this->removeActionLog($myuid,3);
		$this->removeActionLog($myuid,4);
		$this->removeActionLog($myuid,5);

		return $ary;
	}

	function recordDevice($device_id,$device_type,$push_token,$source){
		$myuid = $this->uid;
		$device_type = intval($device_type);	
		$device_channel = $source;

		if (strlen($device_channel)==0){
			$device_channel = $device_type==0?"App Store":"Google";
		}

		$sql = "select count(*) from `device` where uid = $myuid";
		$stmt = $this->_db->query($sql);
		$count = intval($stmt->fetchColumn());
		if ($count>0){
			$sql = "update `device` set modify_time=current_timestamp,push_token=?,device_id = ?,device_channel = ?,device_type = $device_type where uid = $myuid";
			$stmt = $this->_db->prepare($sql);
			$stmt->execute(array($push_token,$device_id,$device_channel));
		}else{
			$sql = "insert into `device` (`device_id`,`uid`,`push_token`,`device_type`,`device_channel`,`modify_time`) values(?,?,?,?,?,current_timestamp)";
			$stmt = $this->_db->prepare($sql);
			$stmt->execute(array($device_id,$myuid,$push_token,$device_type,$device_channel));
		}
	}
	
	function getMultiSimpleProfile($uids){
		//	用于获取列表页面的用户资料
		if (empty($uids))
			return null;

		$profiles = array();

		$profiles = null;//$this->getMultiProfileCaches($uids);
		$empty_uids = array();

		foreach ($uids as $uid) {
			if (!isset($profiles[$uid]))
				$empty_uids[] = $uid;
		}

		if (count($empty_uids) > 0){
			$uids_filter = $this->get_filter_from_array($empty_uids);

			$sql = "SELECT u.uid,u.nickname,u.province,u.city,p.photo_uri as `avatar`,u.gender,u.birthday,IF(u.birthday is null,0,year(curdate()) - year(u.`birthday`)) as `age`,u.height,u.weight,u.tags FROM users u LEFT JOIN `photos` p ON p.photo_id = u.avatar_id AND p.reviewed > 0 WHERE u.uid IN {$uids_filter}";
			$stmt = $this->_db->query($sql);
			$ary = $stmt->fetchAll();

			$ary = $this->appendAvatars($ary);

			foreach ($ary as $profile) {
				unset($profile['password']);
				$uid = $profile['uid'];
				$profiles[$uid] = $profile;
			}

			if (count($ary) > 0){
				$this->saveMultiProfileCache($ary,self::DATA_TYPE_LIST);
			}
		}

		return $profiles;
	}

	function getMultiProfileCaches($uids){
		$keys = array();
		foreach ($uids as $uid) {
			$keys[] = str_replace('[:uid]', $uid, self::MEMCACHED_USER_PROFILE_KEY);
		}

		$cached_profiles = null;//$this->_memcached->getMulti($keys);

		foreach ($uids as $uid) {
			$key = str_replace('[:uid]', $uid, self::MEMCACHED_USER_PROFILE_KEY);
			$cached_profiles[$uid] = $cached_profiles[$key];
			unset($cached_profiles[$key]);
		}

		return $cached_profiles;
	}


	function loadProfileCache($uid){
		// $key = str_replace('[:uid]', $uid, self::MEMCACHED_USER_PROFILE_KEY);

		// return $this->_memcached->get($key);

		return null;
	}

	function saveProfileCache($uid,$profile){
		// $key = str_replace('[:uid]', $uid, self::MEMCACHED_USER_PROFILE_KEY);

		// $this->_memcached->set($key,$profile,86400);
	}

	function saveMultiProfileCache($profiles,$data_type = self::DATA_TYPE_DICTIONARY){
		$new_profiles = array();

		if (self::DATA_TYPE_DICTIONARY == $data_type){
			foreach ($profiles as $uid => $profile) {
				$key = str_replace('[:uid]', $uid, self::MEMCACHED_USER_PROFILE_KEY);
				$new_profiles[$key] = $profile;
			}
		}else{
			foreach ($profiles as $profile) {
				$key = str_replace('[:uid]', $profile['uid'], self::MEMCACHED_USER_PROFILE_KEY);
				$new_profiles[$key] = $profile;
			}
		}
		

		$this->_memcached->setMulti($new_profiles,86400);
	}

	function clearProfileCache($uid){
		$key = str_replace('[:uid]', $uid, self::MEMCACHED_USER_PROFILE_KEY);

		$this->_memcached->delete($key);
	}


	function appendPMPrivacy($profiles,$uid = 0){
		if ($uid==0)
			$uid = $this->uid;

		if (0==$uid)
			return $profiles;

		$my_profile = $this->getProfile($uid);

		$my_is_vip = intval($my_profile['is_vip']) > 0;

		if ($my_is_vip){
			foreach ($profiles as $uid_key => $profile) {
				$profile['pm_privacy'] = 1;

				$profiles[$uid_key] = $profile;
			}

			return $profiles;
		}else{
			foreach ($profiles as $uid_key => $profile) {
				$profile['pm_privacy'] = 0;
				if (intval($my_profile['gender'])!=intval($profile['gender']) && intval($profile['is_vip']) > 0)
					$profile['pm_privacy'] = 1;
				else if ($this->isUnlocked($uid,intval($profile['uid'])))
					$profile['pm_privacy'] = 1;

				$profiles[$uid_key] = $profile;

			}

			return $profiles;
		}
	}

	function appendSimpleProfile($list,$uid = 0){
		if (empty($list))
			return null;

		$uids = array();
		foreach ($list as $info) {
			$uids[] = $info['uid'];
		}

		$profiles = $this->getMultiSimpleProfile($uids);
		$profiles = $this->appendContacts($profiles,self::DATA_TYPE_DICTIONARY);

		$new_list = array();
		foreach ($list as $info) {
			$uid = $info['uid'];
			$info['user'] = $profiles[$uid];
			$new_list[] = $info;
		}

		return $new_list;
	}

	function bindPhone($phone, $uid = 0){
		if (empty($uid))
			$uid = $this->uid;
		$sql = "update users set phone = ? where uid = ?";
		$stmt = $this->_db->prepare($sql);
		$result = $stmt->execute(array($phone,$uid));

		return $result;
	}

	function appendContacts($user_list,$data_type = self::DATA_TYPE_LIST){
		// get contacts list
		$contact_list = $this->getContactList();

		$uids = array();
		if (self::DATA_TYPE_DICTIONARY == $data_type){
			foreach ($user_list as $uid => $profile) {
				$uids[] = $uid;
			}
		}else if (self::DATA_TYPE_LIST == $data_type){
			$ary = array();
			foreach ($user_list as $profile) {
				$uids[] = $profile['uid'];
			}

			$uids_string = implode(',', $uids);
		}else if (self::DATA_TYPE_SINGLE == $data_type){
			$uids[] = $user_list['uid'];
		}

		if (empty($uids))
			return $user_list;

		$uids_string = implode(',', $uids);

		$sql = "select * from `contacts` where uid in ({$uids_string})";

		$stmt = $this->_db->query($sql);
		$ary = $stmt->fetchAll();

		$contact_info = array();

		foreach ($ary as $item) {
			$contacts = array();
			foreach ($contact_list as $cl_item) {
				$key = $cl_item['contact_key'];
				unset($cl_item['contact_id']);
				if (isset($item[$key])){
					$cl_item['content'] = $item[$key];
					$contacts[] = $cl_item;
				}
			}

			if (!empty($contacts)){
				$uid = $item['uid'];
				$contact_info[$uid] = $contacts;
			}
		}

		if (self::DATA_TYPE_DICTIONARY == $data_type){
			foreach ($user_list as $uid => $profile) {
				$profile['contacts'] = $contact_info[$uid];
				$user_list[$uid] = $profile;
			}

			return $user_list;
		}else if (self::DATA_TYPE_LIST == $data_type){
			$ary = array();
			foreach ($user_list as $user) {
				$uid = $user['uid'];
				if (isset($contact_info[$uid]))
					$user['contacts'] = $contact_info[$uid];

				$ary[] = $user;
			}

			return $ary;
		}else if (self::DATA_TYPE_SINGLE == $data_type){
			$user_list['contacts'] = $contact_info[$user_list['uid']];

			return $user_list;
		}
	}

	function getContactList(){
		$sql = "SELECT * from `contact_list` where rank >= 0 order by rank desc,contact_id asc";

		$stmt = $this->_db->querY($sql);

		$ary = $stmt->fetchAll();

		$contact_list = array();

		foreach ($ary as $info) {
			$icon_uri = $info['icon_uri'];
			if (!empty($icon_uri)){
				$icon_uri = self::QINIU_URL_PREFIX . $icon_uri;
				$info['icon_uri'] = $icon_uri;
			}
			
			$contact_list[] = $info;
		}

		return $contact_list;
	}

	function updateContacts($uid,$contacts){
		$contact_list = $this->getContactList();
		$valid_keys = array();

		foreach ($contact_list as $item) {
			$valid_keys[] = $item['contact_key'];
		}

		$keys = array();
		$values = array();

		foreach ($valid_keys as $key) {
			if (isset($contacts[$key])){
				$keys[] = $key;
				$values[] = $contacts[$key];
			}
		}

		$count = count($keys);
		if ($count > 0){
			$keys_str = implode(',', $keys);
			$question_masks = implode(',', array_fill(0, count($keys), '?'));

			$sql = "SELECT count(1) FROM `contacts` where uid = $uid";
			$stmt = $this->_db->query($sql);

			$c = intval($stmt->fetchColumn());

			if (0 == $c){
				$sql = "INSERT INTO `contacts` (uid,{$keys_str}) VALUES ($uid,{$question_masks})";
				$stmt = $this->_db->prepare($sql);
				$stmt->execute($values);
			}else{
				$sql = "UPDATE `contacts` SET ";

				$sql .= implode(' = ?,', $keys);
				$sql .= " = ? where uid = $uid";

				$stmt = $this->_db->prepare($sql);
				$stmt->execute($values);
			}
		}
	}

}

?>