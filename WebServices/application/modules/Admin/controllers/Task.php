<?php
class TaskController extends BaseApiController{
	private $_task_model = null;
	private $_memcached_user_model = null;
	
	function init(){
		parent::init();
		$this->_task_model = new TaskModel();
		$this->_memcached_user_model = new MemcachedUserModel();
	}

	function listAction(){
		$timestamp = intval($_REQUEST['timestamp']);
		if (0 == $timestamp)
			$timestamp = time();

		$task_list = $this->_task_model->getTaskList($timestamp);

		$this->echo_result($task_list);
	}

	function profileAction(){
		$uid = intval($_REQUEST['uid']);
		$user_model = new UserModel($uid);

		$profile = $user_model->getDetailProfile($uid);

		$this->echo_result($profile);
	}

	function finishAction(){
		$task_id = intval($_REQUEST['task_id']);
		$provider_id = isset($_REQUEST['provider_id']) ? intval($_REQUEST['provider_id']) : -1;

		$this->_task_model->performTask($this->_requestor_uid,$task_id,$provider_id);

		$this->echo_result();
	}

	function updatesAction(){
		$order_list = $this->_task_model->getUpdateList();

		$this->echo_result($order_list);
	}

	function updateAction(){
		$ad_id = intval($_REQUEST['ad_id']);
		$impression = intval($_REQUEST['impression']);

		$ad_model = new AdModel();
		$ad_model->updateAdImpression($ad_id,$impression);

		$this->echo_result();
	}
}
?>