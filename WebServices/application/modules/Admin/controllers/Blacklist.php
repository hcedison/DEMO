<?php
class BlacklistController extends BaseApiController{
	private $_blacklist_model = null;

	function init(){
		parent::init();
		$this->_blacklist_model = new BlacklistModel($this->_requestor_uid);
	}

	function importAction(){
		$name = $_REQUEST['name'];
		$idcard_no = $_REQUEST['idcard_no'];
		$phone = $_REQUEST['phone'];
		$times = $_REQUEST['times'];

		$names = explode(',', $name);
		$idcard_nos = explode(',', $idcard_no);
		$phones = explode(',', $phone);
		$timeses = explode(',', $times);
		$sources = explode(',', $source);

		if (count($names) == count($idcard_nos) && count($idcard_nos) == count($phones) && count($phones) == count($timeses)){
			$this->_blacklist_model->importBlacklist($names, $idcard_nos, $phones, $timeses, $sources);

			$this->echo_result();
		}else{
			$this->echo_error(60004);
		}
	}

	function listAction(){
		$page = intval($_REQUEST['page']);

		$ary = $this->_blacklist_model->getBlacklist($page);

		$this->echo_result($ary);
	}

	function searchAction(){
		$name = $_REQUEST['name'];
		$idcard_no = $_REQUEST['idcard_no'];
		$phone = $_REQUEST['phone'];
		$page = intval($_REQUEST['page']);

		// if (empty($name) && empty($idcard_no) && empty($phone)){
		// 	$this->echo_error(10004);
		// }

		$ary = $this->_blacklist_model->searchBlacklist($name,$idcard_no,$phone,$page);

		$this->echo_result($ary);
	}

	function deleteAction(){
		$start_date = intval($_REQUEST['start_date']);
		$end_date = intval($_REQUEST['end_date']);
		$blacklist_id = intval($_REQUEST['blacklist_id']);

		$count = $this->_blacklist_model->deleteBlacklist($blacklist_id, $start_date, $end_date);

		$this->echo_result(array('deleted' => $count));
	}
}
?>