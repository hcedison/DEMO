<?php
class ContentController extends BaseApiController{
	private $_tryst_model = null;
	private $_content_model = null;

	function init(){
		parent::init();
		$this->_tryst_model = new TrystModel();
		$this->_content_model = new ContentModel();
	}

	function photosAction(){
		$timestamp = intval($_REQUEST['timestamp']);
		$gender = intval($_REQUEST['gender']);

		$ary = $this->_content_model->getPhotoList($timestamp,$gender);

		$this->echo_result($ary);
	}

	function approvePhotosAction(){
		$photo_ids = $_REQUEST['photo_ids'];
		$this->_content_model->approvePhotos($photo_ids);

		$this->echo_result();
	}

	function deletePhotosAction(){
		$photo_ids = $_REQUEST['photo_ids'];
		$this->_content_model->deletePhotos($photo_ids);

		$this->echo_result();
	}

	function trystsAction(){
		$gender = intval($_REQUEST['gender']);
		$timestamp = intval($_REQUEST['timestamp']);

		$tryst_list = $this->_tryst_model->getNotReviewedTrysts($gender,$timestamp);

		$this->echo_result($tryst_list);
	}

	function deleteTrystsAction(){
		$tryst_ids = $_REQUEST['tryst_ids'];
		$this->_content_model->deleteTrysts($tryst_ids);

		$this->echo_result();
	}

	function approveTrystsAction(){
		$tryst_ids = $_REQUEST['tryst_ids'];
		$this->_content_model->approveTrysts($tryst_ids);

		$this->echo_result();
	}
}
?>