<?php
class MemcachedUserModel extends MosaicModel{
	const XMPP_PASSWORD_KEY = "xmpp.password.[:uid]";
	const USER_RELATION_COUNT_KEY = "user.relation.count.[:uid]";
	const USER_INTIMATES_SET_KEY = "user.intimates.set.[:uid]";
	const USER_LOCATION_KEY = "user.location.[:uid]";
	const USER_LAST_VISIT_TIME_KEY = "user.lastvisit.time.[:uid]";
	const USER_AUTH_CODE_KEY = "auth.code.register.[:phone]";
	const USER_LAST_SMS_TIME_KEY = "auth.code.last.time.[:phone]";
	const DEVICE_DAILY_TOTAL_SMS_COUNT_KEY = "device.daily.total.sms.count.[:device_id]";
	const USER_TEMP_PASSWORD_KEY = "user.password.temp.[:phone]";
	const USER_LAST_PUSH_TIME = "user.last.push.time.[:uid]";
	const USER_BLOCKED_UIDS_KEY = "user.blocked.uids.list.[:uid]";
	const USER_HEAT_KEY = "user.heat.[:uid]";
	const USER_UNLOCKED_UIDS_KEY = "user.unlocked.uids.list.[:uid]";
	const USER_LAST_APPLY_INTIMATE_TIME_KEY = "user.last.apply.intimate.time.[:uid]";
	const USER_APPLY_INTIMATE_COUNT_KEY = "user.apply.intimate.count.[:uid].[:date]";
	const USER_NOTICE_SETTINGS_KEY = "user.notice.settings.[:uid]";
	const USER_LAST_SAY_HI_TIME_KEY = "user.last.say.hi.time.[:uid]";
	const USER_SAY_HI_COUNT_KEY = "user.say.hi.count.[:uid].[:date]";

	function getLastPushTime($uid){
		return 0;
		$key = str_replace('[:uid]', $uid, self::USER_LAST_PUSH_TIME);

		return $this->_memcached->get($key);
	}

	function updatePushTime($uid){
		return;
		$key = str_replace('[:uid]', $uid, self::USER_LAST_PUSH_TIME);

		$this->_memcached->set($key,time(),time()+86400*7);
	}

	function updateLocation($uid,$latitude,$longitude,$province = 0,$city = 0){
		return;
		if (0==intval($uid) || 0==doubleval($latitude) || 0==doubleval($longitude))
			return;

		$key = str_replace('[:uid]', $uid, self::USER_LOCATION_KEY);
		
		$geohasher = new Geohash();
    	$geohash = $geohasher->encode($latitude,$longitude);
    	$geohash = substr($geohash, 0,4);

    	$location_info = array();
    	$location_info['latitude'] = $latitude;
    	$location_info['longitude'] = $longitude;
    	$location_info['geohash'] = $geohash;

    	if ($province > 0){
    		$location_info['province'] = $province;
    		$location_info['city'] = $city;
    	}else{
    		$old_info = $this->_memcached->get($key);
    		$province = intval($old_info['province']);
    		$city = intval($old_info['city']);

    		if ($province>0){
    			$location_info['province'] = $province;
    			$location_info['city'] = $city;
    		}
    	}
    		
    	$this->_memcached->set($key,$location_info,time()+86400*3);
	}

	function getLocation($uid){
		if (0==intval($uid))
			return null;

		return null;

		$key = str_replace('[:uid]', $uid, self::USER_LOCATION_KEY);

		return $this->_memcached->get($key);
	}

	function updateLastVisitTime($uid){
		return;
		$key = str_replace('[:uid]', $uid, self::USER_LAST_VISIT_TIME_KEY);

		$this->_memcached->set($key,time(),time()+86400);
	}

	function getLastVisitTime($uid){
		return 0;
		$key = str_replace('[:uid]', $uid, self::USER_LAST_VISIT_TIME_KEY);

		return intval($this->_memcached->get($key));
	}

	function setAuthCode($phone,$code){
		// $key = str_replace('[:phone]', $phone, self::USER_AUTH_CODE_KEY);

		// $this->_memcached->set($key,$code,1800);
		$sql = "INSERT into auth_codes (phone,code) values (?,?)";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($phone,$code));
	}

	function getAuthCode($phone){
		// $key = str_replace('[:phone]', $phone, self::USER_AUTH_CODE_KEY);

		// return $this->_memcached->get($key);
		$sql = "SELECT `code` from auth_codes where phone = ? and sent_time >= from_unixtime(unix_timestamp(CURRENT_TIMESTAMP) - 1800) order by code_id desc limit 1";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($phone));

		$code = $stmt->fetchColumn();

		return $code;
	}

	function isAuthCodeCorrect($phone,$auth_code){
		$sql = "SELECT count(1) from auth_codes where phone = ? and `code` = ? and sent_time > from_unixtime(unix_timestamp(current_timestamp) - 1800)";
		$stmt = $this->_db->prepare($sql);

		$stmt->execute(array($phone,$auth_code));

		return intval($stmt->fetchColumn()) > 0;
	}

	function invalidAuthCode($phone){
		return;
		$key = str_replace('[:phone]', $phone, self::USER_AUTH_CODE_KEY);

		$this->_memcached->delete($key);
	}

	function updateLastSMSTime($phone){
		return;
		$key = str_replace('[:phone]', $phone, self::USER_LAST_SMS_TIME_KEY);

		$this->_memcached->set($key,time(),60);
	}

	function getLastSMSTime($phone){
		// $key = str_replace('[:phone]', $phone, self::USER_LAST_SMS_TIME_KEY);

		// return $this->_memcached->get($key);
		$sql = "SELECT unix_timestamp(sent_time) from auth_codes where phone = ? order by code_id desc limit 1";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($phone));

		return intval($stmt->fetchColumn());
	}

	function getSMSCount($device_id){
		$key = str_replace('[:device_id]', $device_id, DEVICE_DAILY_TOTAL_SMS_COUNT_KEY);

		return $this->_memcached->get($key);
	}

	function updateSMSCount($device_id){
		$key = str_replace('[:device_id]', $device_id, DEVICE_DAILY_TOTAL_SMS_COUNT_KEY);

		$this->_memcached->increment($key);
		$this->_memcached->touch($key,time()+86400);
	}

	function getTempPassword($phone){
		$key = str_replace('[:phone]', $phone, self::USER_TEMP_PASSWORD_KEY);

		return $this->_memcached->get($key);	
	}

	function setTempPassword($phone,$temp_password){
		$key = str_replace('[:phone]', $phone, self::USER_TEMP_PASSWORD_KEY);

		$this->_memcached->set($key,$temp_password,time()+1800);
	}

	function clearTempPassword($phone){
		$key = str_replace('[:phone]', $phone, self::USER_TEMP_PASSWORD_KEY);

		$this->_memcached->delete($key);
	}




	/**
	*	user notice settings
	*
	*
	*/
	function getNoticeSettings($uid){
		$key = str_replace('[:uid]', $uid, self::USER_NOTICE_SETTINGS_KEY);

		return $this->_memcached->get($key);
	}

	function updateNoticeSettings($uid,$settingsInfo){
		$key = str_replace('[:uid]', $uid, self::USER_NOTICE_SETTINGS_KEY);

		$this->_memcached->set($key,$settingsInfo,86400*14);
	}

	
}
?>