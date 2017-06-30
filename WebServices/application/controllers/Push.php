<?php
class PushController extends BaseApiController{
	private $_push_model = null;

	function init(){
		parent::init();
		$this->_push_model = new PushModel();
	}

	function pushMessageAction(){
		$myuid = intval($_REQUEST['myuid']);
		$uid = intval($_REQUEST['uid']);
		$content = $_REQUEST['content'];

		$len = strlen($content);
		
		if ($myuid==0 or $uid==0 or $len==0){
			$this->echo_error(10004);
		}

		$this->_push_model->pushMessage($myuid,$uid,$content);

		$this->echo_result(null);
	}
}
?>