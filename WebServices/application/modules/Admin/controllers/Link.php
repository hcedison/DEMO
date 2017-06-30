<?php
class LinkController extends Yaf_Controller_Abstract{
	private $_link_model = null;

	public function init(){
		$this->_link_model = new LinkModel();
	}

	function sinaAction(){
		$link_id = intval($_REQUEST['link_id']);

		$this->_link_model->recordLink($link_id,'sina');

		$url = $this->_link_model->getDownloadLink();

		header("location:"+$url);

		$this->redirect($url);

		exit();
	}
}
?>