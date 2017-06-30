<?php
class BaseController extends BaseApiController{
	function tagsAction(){
		$tags = $this->_mosaic_model->getTags();

		$this->echo_result($tags);
	}

	function avatarsAction(){
		$avatars = $this->_mosaic_model->getAvatars();

		$this->echo_result($avatars);
	}

	function appConfigAction(){
		$app_version = $_REQUEST['app_version'];
		$source = $_REQUEST['user_source'];

		$app_config = $this->_mosaic_model->getAppConfig($app_version,$source);

		if (!isset($_REQUEST['access_token'])){
			$device_id = $_REQUEST['device_id'];

			$monitor_model = new MonitorModel();
			$monitor_model->activeDevice($device_id);
		}
		

		$this->echo_result($app_config);
	}

	function templetsAction(){
		$templets = $this->_mosaic_model->getDescriptionTemplets();

		$this->echo_result($templets);
	}

	function registerAuthCodeAction(){
		$phone = $_REQUEST['phone'];

		$memcached_user = new MemcachedUserModel();
		$sms_model = new SMSModel();//sendAuthCode

		//	判断滥用短信
		$count = $memcached_user->getSMSCount($this->device_id);
		if ($count>=3)
			$this->echo_error(10033);

		$last_time = intval($memcached_user->getLastSMSTime($phone));

		if (time()-$last_time <= 60)
			$this->echo_error(10034);

		//	判断手机号是否已注册
		$user_model = new UserModel();
		$is_exist = $user_model->isPhoneUserExist($phone);
		if ($is_exist)
			$this->echo_error(10010);

		$auth_code = $memcached_user->getRegisterAuthCode($phone);
		if (empty($auth_code)){
			$auth_code = '' . rand(1000,9999);
			$memcached_user->setRegisterAuthCode($phone,$auth_code);
		}

		$sms_model->sendAuthCode($phone,$auth_code);

		$this->echo_result();
	}

	function checkRegisterAuthCodeAction(){
		$phone = $_REQUEST['phone'];
		$auth_code = $_REQUEST['auth_code'];

		if (empty($phone) || empty($auth_code))
			$this->echo_error(10004);

		$memcached_user = new MemcachedUserModel();
		$code = $memcached_user->getRegisterAuthCode($phone);

		if ($code==$auth_code)
			$this->echo_result();
		else
			$this->echo_error(10035);
	}

	function sendTempPasswordAction(){
		$phone = $_REQUEST['phone'];

		$memcached_user = new MemcachedUserModel();
		$sms_model = new SMSModel();//sendAuthCode

		//	判断滥用短信
		$count = $memcached_user->getSMSCount($this->device_id);
		if ($count>=3)
			$this->echo_error(10033);

		$last_time = intval($memcached_user->getLastSMSTime($phone));

		if (time()-$last_time <= 60)
			$this->echo_error(10034);

		//	判断手机号是否已注册
		$user_model = new UserModel();
		$is_exist = $user_model->isPhoneUserExist($phone);
		if (!$is_exist)
			$this->echo_error(10003);

		$temp_password = $memcached_user->getTempPassword($phone);
		if (empty($temp_password)){
			$temp_password = '' . rand(100000,999999);
			$memcached_user->setTempPassword($phone,$temp_password);
		}

		$sms_model->sendTempPassword($phone,$temp_password);
		$memcached_user->updateLastSMSTime($phone);

		$this->echo_result();
	}
}
?>