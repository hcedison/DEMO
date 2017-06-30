<?php
class TrackController extends BaseApiController{
	private $_track_model;

	function init(){
		parent::init();
		$this->_track_model = new TrackModel();
	}

	function bindAction(){
		$phone = $_REQUEST['phone'];
		$name = $_REQUEST['name'];
		$device_id = $_REQUEST['device_id'];
		$latitude = doubleval($_REQUEST['latitude']);
		$longitude = doubleval($_REQUEST['longitude']);

		$this->_track_model->bindDevice($phone, $name, $device_id, $latitude, $longitude);

		$this->echo_result();
	}

	function updateLocationAction(){
		$device_id = $_REQUEST['device_id'];
		$latitude = doubleval($_REQUEST['latitude']);
		$longitude = doubleval($_REQUEST['longitude']);

		$this->_track_model->updateLocation($device_id, $latitude, $longitude);

		$this->echo_result();
	}

	function locationsAction(){
		$page = intval($_REQUEST['page']);
		$name = $_REQUEST['name'];
		$phone = $_REQUEST['phone'];
		$is_recent = intval($_REQUEST['is_recent']) > 0;

		$ary = $this->_track_model->getLocations($page, $phone, $name, $is_recent);

		$this->echo_result($ary);
	}
}
?>