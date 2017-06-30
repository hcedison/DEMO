<?php
class UserController extends BaseApiController{
	private $_user_model = null;
	private $_memcached_user_model = null;
	
	function init(){
		parent::init();
		$this->_user_model = new UserModel($this->_requestor_uid);
		$this->_memcached_user_model = new MemcachedUserModel();
	}

	function registerAction(){
		$phone = $_REQUEST['phone'];
		$auth_code = $_REQUEST['auth_code'];
		$password = $_REQUEST['password'];
		$salt = 'mosaic';//'' . rand(100000,999999);
		$channel = $_REQUEST['channel'];

		//	缺少必要参数
		if (empty($phone) || empty($auth_code) || empty($password))
			$this->echo_error(10004);

		//	用户已存在
		$phone_user = $this->_user_model->getPhoneUser($phone);
		if (empty($phone_user)){
			$this->echo_error(10047);
		}else{
			if (intval($phone_user['is_registered']) > 0)
				$this->echo_error(10010);
		}

		//	验证码错误
		$is_correct = $this->_memcached_user_model->isAuthCodeCorrect($phone,$auth_code);
		if (!$is_correct)
			$this->echo_error(10043);

		//	密码长度不正确
		if (strlen($password) < 6 || strlen($password) > 16)
			$this->echo_error(10026);

		$profile = $this->_user_model->registerAccount($phone,$password,$salt,$channel);

		$access_token = $this->gen_access_token($profile['uid']);


		if ($access_token==null)
			$this->echo_error(10011);
		$profile['access_token'] = $access_token;
		$profile['password'] = $password;

		$result = $this->insert_auth_log($profile['uid']);

		$monitor_model = new MonitorModel();
		$monitor_model->remarkDevice($profile['uid'],$_REQUEST['device_id']);
		// $this->_memcached_user_model->invalidRegisterAuthCode($phone);

		$this->echo_result($profile);
	}

	function loginAction(){
		$phone = $_REQUEST['phone'];
		$channel = $_REQUEST['channel'];

		$profile = null;
		$password_hash = strtolower($_REQUEST['password']);

		if ($password_hash==null)
			$this->echo_error(10009);

		if (!$this->_user_model->isPhoneUserExist($phone)){
			$this->echo_error(10003);
		}
			
		$profile = $this->_user_model->loginAccount($phone,$password_hash);

		if ($profile==null)
			$this->echo_error(10012);

		$uid = intval($profile['uid']);

		$access_token = $this->gen_access_token($uid);

		if ($access_token==null)
			$this->echo_error(10011);
		$profile['access_token'] = $access_token;

		$result = $this->insert_auth_log(intval($profile['uid']));

		$this->echo_result($profile);
}

	function profileAction(){
		$uid = $_REQUEST['uid'];
		$profile = $this->_user_model->getDetailProfile($uid);

		$this->echo_result($profile);
	}

	function updateProfileAction(){
		$my_profile = $this->_user_model->getProfile();

		if (1==intval($my_profile['gender_changed']) && 0!=intval($_REQUEST['gender']))
			$this->echo_error(10035);

		$profile = $this->_user_model->updateProfile($_REQUEST);

		$this->echo_result($profile);
	}

	function recordDeviceAction(){
		$device_id = $_REQUEST['device_id'];
		$device_type = $_REQUEST['device_type'];
		$push_token = $_REQUEST['push_token'];
		$source = $_REQUEST['user_source'];
		$this->_user_model->recordDevice($device_id,$device_type,$push_token,$source);

		$this->echo_result(null);
	}

	function promptsAction(){
		$prompts = $this->_user_model->getPrompts();

		$this->echo_result($prompts);
	}

	function noticeListAction(){
		$page = $_REQUEST['page'];

		$notice_list = $this->_user_model->getNoticeList($page);

		$this->echo_result($notice_list);
	}

	function updateLocationAction(){
		$latitude = $_REQUEST['latitude'];
		$longitude = $_REQUEST['longitude'];

		if (doubleval($latitude)!=0 && doubleval($longitude)!=0){
			$memcached_user = new MemcachedUserModel();

			
			$location_info = $this->_user_model->getProvinceCity($latitude,$longitude);

        	$province = intval($location_info["province"]);
        	$city = intval($location_info["city"]);

        	$memcached_user->updateLocation($this->_requestor_uid,$latitude,$longitude,$province,$city);


        	if ($province > 0){
          		$this->_user_model->updateLocation($this->_requestor_uid,$latitude,$longitude,$province,$city);
        	}

			$this->echo_result(null);
		}
		else
			$this->echo_error(10019);
	}

	function changePasswordAction(){
		$old_password = $_REQUEST['old_password'];
		$new_password = $_REQUEST['new_password'];

		$correct = $this->_user_model->isPasswordCorrect($old_password);

		if (!$correct)
			$this->echo_error(10012);

		$result = $this->_user_model->changePassword($new_password);

		$this->echo_result();
	}

	function authCodeAction(){
		$phone = $_REQUEST['phone'];

		$memcached_user = new MemcachedUserModel();
		$sms_model = new SMSModel();//sendAuthCode

		//	判断滥用短信
		// $count = $memcached_user->getSMSCount($this->device_id);
		// if ($count>=3)
		// 	$this->echo_error(10033);

		$last_time = intval($memcached_user->getLastSMSTime($phone));

		if (time()-$last_time <= 60)
			$this->echo_error(10034);

			// 判断手机号是否已注册
		//	用户已存在
		$phone_user = $this->_user_model->getPhoneUser($phone);
		if (empty($phone_user)){
			$this->echo_error(10047);
		}else{
			if (intval($phone_user['is_registered']) > 0)
				$this->echo_error(10010);
		}

		$auth_code = $memcached_user->getAuthCode($phone);
		if (empty($auth_code)){
			$auth_code = '' . rand(1000,9999);
			$memcached_user->setAuthCode($phone,$auth_code);
		}

		$sms_model->sendAuthCode($phone,$auth_code);

		$this->echo_result();
	}

	function bindPhoneAction(){
		$phone = $_REQUEST['phone'];
		$auth_code = $_REQUEST['auth_code'];

		if (empty($phone) || empty($auth_code)){
			$this->echo_error(10004);
		}

		$memcached_user = new MemcachedUserModel();

		$saved_auth_code = $memcached_user->getAuthCode($phone);
		if ($saved_auth_code == $auth_code){
			$this->_user_model->bindPhone($phone);

			$this->echo_result(array('phone'=>$phone));
		}else{
			$this->echo_error(10043);
		}

	}

	function updateAvatarAction(){
		$photo_id = $_REQUEST['photo_id'];

		$photo_model = new PhotoModel();
		$photoInfo = $photo_model->getPhotoInfo($photo_id);

		if (empty($photoInfo))
			$this->echo_error(10023);

		if (intval($photoInfo['uid']) != $this->_requestor_uid)
			$this->echo_error(10036);

		if (intval($photoInfo['is_private']) > 0 || intval($photoInfo['reviewed']) < 0)
			$this->echo_error(10037);

		$photo_model->setAvatar($this->_requestor_uid,$photoInfo);

		$this->echo_result();
	}

	function noticeSettingsAction(){
		$push_model = new PushModel();

		$info = $push_model->getNoticeSettings($this->_requestor_uid);

		$this->echo_result($info);
	}

	function updateNoticeSettingsAction(){
		$push_model = new PushModel();

		$push_model->updateNoticeSettings($this->_requestor_uid,$_REQUEST);

		$this->echo_result();
	}

	function updateContactsAction(){
		$this->_user_model->updateContacts($this->_requestor_uid,$_REQUEST);

		$this->echo_result();
	}

	function forgotAction(){
		$phone = $_REQUEST['phone'];

		$memcached_user = new MemcachedUserModel();
		$sms_model = new SMSModel();//sendAuthCode

		//	判断滥用短信
		// $count = $memcached_user->getSMSCount($this->device_id);
		// if ($count>=3)
		// 	$this->echo_error(10033);

		$last_time = intval($memcached_user->getLastSMSTime($phone));

		if (time()-$last_time <= 60)
			$this->echo_error(10034);

			// 判断手机号是否已注册
		//	用户已存在
		$phone_user = $this->_user_model->getPhoneUser($phone);
		if (empty($phone_user)){
			$this->echo_error(10047);
		}else{
			if (intval($phone_user['is_registered']) <= 0)
				$this->echo_error(10048);
		}

		$auth_code = $memcached_user->getAuthCode($phone);
		if (empty($auth_code)){
			$auth_code = '' . rand(1000,9999);
			$memcached_user->setAuthCode($phone,$auth_code);
		}

		$sms_model->sendAuthCode($phone,$auth_code);

		$this->echo_result();
	}

	function resetPasswordAction(){
		$phone = $_REQUEST['phone'];
		$auth_code = $_REQUEST['auth_code'];
		$new_password = $_REQUEST['new_password'];

		//	验证码错误
		$is_correct = $this->_memcached_user_model->isAuthCodeCorrect($phone,$auth_code);
		if (!$is_correct)
			$this->echo_error(10043);

		$result = $this->_user_model->resetPassword($phone, $new_password);

		$this->echo_result();
	}


}
?>