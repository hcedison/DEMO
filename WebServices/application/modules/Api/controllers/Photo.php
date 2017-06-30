<?php
class PhotoController extends BaseApiController{
	private $_photo_model = null;

	function init(){
		parent::init();
		$this->_photo_model = new PhotoModel();
	}

	function privateListAction(){
		$timestamp = intval($_REQUEST['timestamp']);
		$uid = intval($_REQUEST['uid']);

		if (empty($uid))
			$uid = $this->_requestor_uid;

		$photo_list = $this->_photo_model->getPrivateList($uid,$timestamp);


		$this->echo_result($photo_list);
	}

	function uploadAction(){
		$uid = $this->_requestor_uid;
		$photo_uri = $_REQUEST['photo_uri'];
		$is_private = intval($_REQUEST['is_private']);

		$photo_id = $this->_photo_model->uploadPhoto($uid,$photo_uri,$is_private);

		$this->echo_result(array('photo_id'=>$photo_id));
	}	

	function deleteAction(){
		$photo_id = intval($_REQUEST['photo_id']);

		$photoInfo = $this->_photo_model->getPhotoInfo($photo_id);

		if (empty($photoInfo))
			$this->echo_error(10038);

		if (intval($photoInfo['uid']) != $this->_requestor_uid)
			$this->echo_error(10039);

		if (intval($photoInfo['is_avatar']) > 0)
			$this->echo_error(10040);

		$this->_photo_model->deletePhoto($photo_id);

		$this->echo_result();
	}
}
?>