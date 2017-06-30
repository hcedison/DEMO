<?php
class AccountController extends BaseApiController{
	private $_account_model = null;

	function init(){
		parent::init();
		$this->_account_model = new AccountModel($this->_requestor_uid);
	}

	function listAction(){
		//	根据日期获取当日运营账号
		$page = intval($_REQUEST['page']);
		$is_admin = intval($_REQUEST['is_admin']);


		$ary = $this->_account_model->getAccountList($page, $this->_requestor_uid, $is_admin);

		$this->echo_result($ary);
	}

	function updateAction(){
		$uid = intval($_REQUEST['uid']);

		$this->_account_model->updateAccount($uid, $_REQUEST);

		$this->echo_result();
	}

	function createAction(){
		$name = $_REQUEST['name'];
		$idcard_no = $_REQUEST['idcard_no'];
		$phone = $_REQUEST['phone'];
		$group_id = intval($_REQUEST['group_id']);

		if (empty($name) || empty($phone)){
			$this->echo_error(10004);
		}

		$result = $this->_account_model->prepareAccount($name, $idcard_no, $phone, $group_id);
		if ($result)
			$this->echo_result();
		else
			$this->echo_error(60003);
	}

	function prepareAction(){
		$name = $_REQUEST['name'];
		$contact_name = $_REQUEST['contact_name'];
		$phone = $_REQUEST['phone'];

		if (empty($name) || empty($phone)){
			$this->echo_error(10004);
		}

		$result = $this->_account_model->prepareShopAccount($name, $contact_name, $phone);
		if ($result)
			$this->echo_result();
		else
			$this->echo_error(60003);
	}

	function deleteAction(){
		$uid = intval($_REQUEST['uid']);
		$start_date = intval($_REQUEST['start_date']);
		$end_date = intval($_REQUEST['end_date']);

		$count = $this->_account_model->deleteAccount($uid, $start_date, $end_date);

		$this->echo_result(array('deleted' => $count));
	}

	function searchAction(){
		$name = $_REQUEST['name'];
		$idcard_no = $_REQUEST['idcard_no'];
		$phone = $_REQUEST['phone'];
		$is_admin = intval($_REQUEST['is_admin']);
		$page = intval($_REQUEST['page']);

		// if (empty($name) && empty($idcard_no) && empty($phone)){
		// 	$this->echo_error(10004);
		// }

		$ary = $this->_account_model->searchAccount($name,$idcard_no,$phone,$is_admin,$page);

		$this->echo_result($ary);
	}

	function rechargeAction() {
		$uid = intval($_REQUEST['uid']);
		$balance = intval($_REQUEST['balance']);

		$this->_account_model->rechargeAccount($uid, $balance);

		$this->echo_result();
	}
	function banAccountAction(){
		$uid = intval($_REQUEST['uid']);

		$is_exist = $this->_account_model->isAccountExist($uid);

		if ($is_exist){
			$status = $this->_account_model->isAccountBanned($uid);
			if ($status<0){
				$this->_account_model->banAccount($uid);
			}

			$this->echo_result(null);
		}else{
			$code = 10003;
			$msg = $this->_error_codes->system_errors[$code];
			$this->echo_message($msg,$code);
		}
	}

	function submitAction(){
		$score = $this->_account_model->submitProfile($_REQUEST);

		$this->echo_result(array('score'=>$score));
	}

	function scoresAction() {
		$page = intval($_REQUEST['page']);
		$name = $_REQUEST['name'];
		$idcard_no = $_REQUEST['idcard_no'];
		$phone = $_REQUEST['phone'];

		$ary = $this->_account_model->getScoresList($page, $name, $idcard_no, $phone);

		$this->echo_result($ary);
	}

	function importAction(){
		$name = $_REQUEST['name'];
		$idcard_no = $_REQUEST['idcard_no'];
		$phone = $_REQUEST['phone'];

		$names = explode(',', $name);
		$idcard_nos = explode(',', $idcard_no);
		$phones = explode(',', $phone);

		if (count($names) == count($idcard_nos) && count($idcard_nos) == count($phones)){
			$this->_account_model->importUser($names, $idcard_nos, $phones);

			$this->echo_result();
		}else{
			$this->echo_error(60004);
		}
	}
}
?>