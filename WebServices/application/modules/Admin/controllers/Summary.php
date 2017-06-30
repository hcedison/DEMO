<?php
class SummaryController extends BaseApiController{
	private $_summary_model = null;

	function init(){
		parent::init();
		$this->_summary_model = new SummaryModel();
	}

	function allAction(){
		$this->echo_result($this->_summary_model->getAllStatics());
	}

	function rechargeSummaryAction(){
		$this->echo_result($this->_summary_model->getRechargeSummary());
	}

	function registerSummaryAction(){
		$this->echo_result($this->_summary_model->getRegisterSummary());
	}

	function rechargeLogAction(){
		$recharge_time = intval($_REQUEST['recharge_time']);
		$this->echo_result($this->_summary_model->getRechargeLog($recharge_time));
	}

	function registerLogAction(){
		$this->echo_result($this->_summary_model->getRegisterLog());
	}
}
?>