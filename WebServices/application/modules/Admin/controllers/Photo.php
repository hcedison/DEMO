<?php
class PhotoController extends BaseApiController{
	private $_photo_model = null;

	function init(){
		parent::init();
		$this->_photo_model = new PhotoModel();
	}

	function getPhotoListAction(){
		$dateline = intval($_REQUEST['dateline']);
		$gender = intval($_REQUEST['gender']);

		$ary = $this->_photo_model->getPhotoList($dateline,$gender);

		$this->echo_result($ary);
	}

	function reviewPhotoAction(){
		$photo_id = $_REQUEST['photo_id'];
		$this->_photo_model->reviewPhoto($photo_id);

		$this->echo_result(null);
	}

	function deletePhotoAction(){
		$photo_id = $_REQUEST['photo_id'];
		$this->_photo_model->hidePhoto($photo_id);

		$this->echo_result(null);
	}

	function replacePhotoAction(){
		$content_id = $_REQUEST['content_id'];
		$url = $_REQUEST['url'];

		$this->_photo_model->replacePhoto($content_id,$url);

		$this->echo_result(null);
	}

	function publicAction(){
		$dateline = intval($_REQUEST['dateline']);
		$gender = intval($_REQUEST['gender']);

		$ary = $this->_photo_model->getPublicPhotoList($dateline,$gender);

		$this->echo_result($ary);
	}
}
?>