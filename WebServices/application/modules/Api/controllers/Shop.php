<?php
class ShopController extends BaseApiController{
	private $_shop_model;

	function init(){
		parent::init();
		$this->_shop_model = new ShopModel();
	}

	function detailAction() {
		$shop_id = intval($_REQUEST['shop_id']);

		$shopInfo = $this->_shop_model->getShopInfo($shop_id);

		if (empty($shopInfo)) {
			$this->echo_error(70001);
		}

		$this->echo_result($shopInfo);
	}

	function discountAction() {
		$shop_id = intval($_REQUEST['shop_id']);
		$price = floatval($_REQUEST['price']);

		$discountInfo = $this->_shop_model->discountedPrice($shop_id, $price);

		$this->echo_result($discountInfo);
	}


}
?>