<?php
class ShopController extends BaseApiController{
	private $_shop_model;

	function init(){
		parent::init();
		$this->_shop_model = new ShopModel();
	}

	function listAction() {
		$page = intval($_REQUEST['page']);
		$name = $_REQUEST['name'];
		$contact_name = $_REQUEST['contact_name'];
		$phone = $_REQUEST['phone'];

		$shop_list = $this->_shop_model->getShopList($name, $contact_name, $phone, $page);

		$this->echo_result($shop_list);
	}

	function ordersAction() {
		$shop_id = intval($_REQUEST['shop_id']);
		$uid = intval($_REQUEST['uid']);
		$page = intval($_REQUEST['page']);

		$orderList = $this->_shop_model->getOrderList($shop_id, $uid, $page);

		$this->echo_result($orderList);
	}

	function detailAction() {
		$shop_id = intval($_REQUEST['shop_id']);

		$shopInfo = $this->_shop_model->getShopInfo($shop_id);

		if (empty($shopInfo)) {
			$this->echo_error(70001);
		}

		$this->echo_result($shopInfo);
	}

	function discountsAction() {
		$shop_id = intval($_REQUEST['shop_id']);
		$page = intval($_REQUEST['page']);


		$discountList = $this->_shop_model->getDiscountList($shop_id, $page);

		$this->echo_result($discountList);
	}

	function removeDiscountAction(){
		$discount_id = intval($_REQUEST['discount_id']);

		$this->_shop_model->removeDiscount($discount_id);

		$this->echo_result();
	}

	function addDiscountAction(){
		$shop_id = intval($_REQUEST['shop_id']);
		$discount_type = intval($_REQUEST['discount_type']);
		$factor1 = floatval($_REQUEST['factor1']);
		$factor2 = floatval($_REQUEST['factor2']);

		$this->_shop_model->addDiscount($shop_id, $discount_type, $factor1, $factor2);

		$this->echo_result();
	}

	
}
?>