<?php
class PayController extends BaseApiController{
	private $_pay_model = null;

	function init(){
		parent::init();
		$this->_pay_model = new PayModel($this->_requestor_uid);
	}

	function orderAction(){
		$price = floatval($_REQUEST['price']);
		$shop_id = intval($_REQUEST['shop_id']);
		$pay_type = $_REQUEST['pay_type'];

		if (empty($pay_type) || $pay_type == "balance"){
			$user_model = new UserModel($this->_requestor_uid);
			$profile = $user_model->getProfile();
			$balance = floatval($profile['balance']);

			if ($balance >= $price){
				$this->_pay_model->purchaseOrder($shop_id, $price);

				$this->echo_result(array('balance'=>$balance - $price));
			} else {
				$this->echo_error(10031);
			}
		}else{
			$order_info = $this->_pay_model->generateOrder($shop_id, $price, $pay_type);

			$this->echo_result($order_info);
		}
	}

}
?>