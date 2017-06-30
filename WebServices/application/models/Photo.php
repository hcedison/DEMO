<?php
class PhotoModel extends MosaicModel{
	// const QINIU_URL_PREFIX = 'http://7xnq7k.com1.z0.glb.clouddn.com/';
	
	function uploadPhoto($uid,$photo_uri,$is_private = 0){
		$sql = "insert into photos (uid,photo_uri) values (?,?)";

		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($uid,$photo_uri));

		$photo_id = intval($this->_db->lastInsertId());

		if (0 == $is_private){
			$user_model = new UserModel();
			$profile = $user_model->getProfile($uid);
			if (empty($profile['avatar_id'])){
				$this->setAvatar($uid,array('photo_uri'=>$photo_uri,'photo_id'=>$photo_id));
			}
		}
		

		return $photo_id;
	}

	
	function getPhotoURL($photo_uris){
		if (empty($photo_uris))
			return null;

		$uri_list = null;
		if (is_array($photo_uris)){
			$uri_list = $photo_uris;
		}else{
			$uri_list = explode(',', $photo_uris);
		}

		$url_list = array();

		foreach ($uri_list as $photo_uri) {
			$photo_info = array();
			$photo_info['small_url'] = self::QINIU_URL_PREFIX . $photo_uri . self::MOSAIC_IMAGE_SMALL_SIZE;
			$photo_info['middle_url'] = self::QINIU_URL_PREFIX . $photo_uri . self::MOSAIC_IMAGE_MIDDLE_SIZE;
			$photo_info['origin_url'] = self::QINIU_URL_PREFIX . $photo_uri;

			$url_list[] = $photo_info;
		}

		return $url_list;
	}

	//	get photo list for review
	function getPublicList($uid = 0,$is_self = false){
		if ($is_self)
			$sql = "SELECT uid,photo_id,photo_uri,is_avatar,unix_timestamp(create_time) as create_time from photos where uid = $uid and photo_type = 0 and reviewed >= 0 order by is_avatar desc,photo_id asc";
		else
			$sql = "SELECT uid,photo_id,photo_uri,is_avatar,unix_timestamp(create_time) as create_time from photos where uid = $uid and photo_type = 0 and reviewed > 0  order by is_avatar desc,photo_id asc";
		$stmt = $this->_db->query($sql);
		$ary = $stmt->fetchAll();
		$photo_list = array();
		foreach ($ary as $photo_info) {
			$photo_uri = $photo_info['photo_uri'];

			$photo_info['small_url'] = self::QINIU_URL_PREFIX . $photo_uri . self::MOSAIC_IMAGE_SMALL_SIZE;
			$photo_info['middle_url'] = self::QINIU_URL_PREFIX . $photo_uri . self::MOSAIC_IMAGE_MIDDLE_SIZE;
			$photo_info['origin_url'] = self::QINIU_URL_PREFIX . $photo_uri;

			$photo_list[] = $photo_info;
		}

		return $photo_list;
	}

	function getPrivateList($uid){
		$sql = "SELECT uid,photo_id,photo_uri,is_private,unix_timestamp(create_time) as create_time from photos where uid = $uid and reviewed >= 0 and is_private = 1 order by create_time asc";
		$stmt = $this->_db->query($sql);
		$ary = $stmt->fetchAll();
		$photo_list = array();
		foreach ($ary as $photo_info) {
			$photo_uri = $photo_info['photo_uri'];

			$photo_info['small_url'] = self::QINIU_URL_PREFIX . $photo_uri . self::MOSAIC_IMAGE_SMALL_SIZE;
			$photo_info['middle_url'] = self::QINIU_URL_PREFIX . $photo_uri . self::MOSAIC_IMAGE_MIDDLE_SIZE;
			$photo_info['origin_url'] = self::QINIU_URL_PREFIX . $photo_uri;

			$photo_list[] = $photo_info;
		}
		
		return $photo_list;
	}

	function setAvatar($uid,$photoInfo,$photo_id = 0){
		if (empty($photoInfo) && $photo_id > 0)
			$photoInfo = $this->getPhotoInfo($photo_id);

		$photo_id = intval($photoInfo['photo_id']);
		$photo_uri = $photoInfo['photo_uri'];

		$sql = "update photos set is_avatar = 1 where uid = $uid and photo_id = $photo_id";
		$this->_db->exec($sql);

		$sql = "update photos set is_avatar = 0 where uid = $uid and photo_id != $photo_id";
		$this->_db->exec($sql);

		$sql = "update `users` set avatar_id = ? where uid = ?";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($photo_id,$uid));

		$user_model = new UserModel();
		$user_model->clearProfileCache($uid);
	}

	function getPhotoInfo($photo_id){
		$sql = "select uid,photo_id,is_avatar,is_private,photo_uri,unix_timestamp(create_time) as create_time from photos where photo_id = $photo_id";
		$stmt = $this->_db->query($sql);

		$ary = $stmt->fetchAll();

		return count($ary) > 0 ? $ary[0] : nulll;
	}

	function hidePhoto($photo_id){
		if (strlen($photo_id)==0)
			return;

		$ary = explode(',', $photo_id);
		$photo_ids = "";
		for ($i=0;$i<count($ary);$i++){
			if ($i!=0)
				$photo_ids .= ",";
			$photo_ids .= $ary[$i];
		}

		$sql = "update `photos` set reviewed = -1 where `photo_id` in ($photo_ids)";

		$this->_db->exec($sql);

		$sql = "select uid from `photos` where `photo_id` in ({$photo_ids}) group by uid";
		$stmt = $this->_db->query($sql);
		$ary = $stmt->fetchAll();

		//	给照片被拒绝的用户发送通知
		for ($i=0;$i<count($ary);$i++){
			$dict = $ary[$i];
			$uid = intval($dict['uid']);

			$this->push_notification($uid,"您有照片不符合规范,已被删除,请上传规范的照片");
		}
	}

	function reviewPhoto($photo_id){
		if (strlen($photo_id)==0)
			return;
		$ary = explode(',', $photo_id);
		$photo_ids = "";
		for ($i=0;$i<count($ary);$i++){
			if ($i!=0)
				$photo_ids .= ",";
			$photo_ids .= $ary[$i];
		}

		$sql = "update `content` set reviewed = 1 where `content_id` in ($photo_ids)";

		$this->_db->exec($sql);
	}

	function deletePhoto($photo_id){
		if (strlen($photo_id)==0)
			return;

		$ary = explode(',', $photo_id);
		$photo_ids = "";
		for ($i=0;$i<count($ary);$i++){
			if ($i!=0)
				$photo_ids .= ",";
			$photo_ids .= $ary[$i];
		}

		$sql = "update `photos` set reviewed = -1 where `photo_id` in ($photo_ids)";

		$this->_db->exec($sql);
	}

	function checkExist($content_id){
		$content_id = intval($content_id);

		$sql = "select count(*) from content where content_id=$content_id";
		$stmt = $this->_db->query($sql);

		$count = intval($stmt->fetchColumn());

		return $count>0;
	}

	function replacePhoto($content_id,$url){
		$content_id = intval($content_id);

		$sql = "update content set content_uri=? where content_id=?";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($url,$content_id));
	}	

	function appendSmallURL($content_list){
		if (empty($content_list))
			return null;
		$ary = array();

		foreach ($content_list as $content) {
			# code...
			if (isset($content['content_uri'])){
				$content_uri = $content['content_uri'];
				$small_url = $content_uri . self::MOSAIC_IMAGE_SMALL_SIZE;
				$content['small_url'] = $small_url;
			}

			$ary[] = $content;
		}

		return $ary;
	}

	function appendPhotoURL($content_list){
		# code...
		if (empty($content_list))
			return null;
		$ary = array();

		foreach ($content_list as $content) {
			# code...
			if (isset($content['photo_uri'])){
				$photo_uri = $content['photo_uri'];
				$small_url = self::QINIU_URL_PREFIX . $photo_uri . self::MOSAIC_IMAGE_SMALL_SIZE;
				$middle_url = self::QINIU_URL_PREFIX . $photo_uri . self::MOSAIC_IMAGE_MIDDLE_SIZE;
				$origin_url = self::QINIU_URL_PREFIX . $photo_uri;

				$content['photo']['small_url'] = $small_url;
				$content['photo']['middle_url'] = $middle_url;
				$content['photo']['origin_url'] = $origin_url;
			}
			$ary[] = $content;
		}

		return $ary;
	}

	function insertFakePhoto(){
		$offset = 0;
		do{
			$sql = "select uid,origin_uid from users where is_internal = 1 and origin_uid != 0 order by register_time asc limit {$offset},100";
			$stmt = $this->_db->query($sql);
			$ary = $stmt->fetchAll();

			foreach ($ary as $user_info) {
				
				$uid = $user_info['uid'];
				$origin_uid = $user_info['origin_uid'];

				$sql = "insert into content(content_uri,uid,create_time,reviewed,is_private,is_inserted) select content_uri,{$uid} as uid,from_unixtime(unix_timestamp(create_time)+3600*24*334+360) as create_time,-10 as reviewed,1 as is_private,1 as is_inserted from content_bk where uid = {$origin_uid}";

				$this->_db->exec($sql);
			}

			$count = count($ary);

			$offset += 100;
		}while($count > 0);

		echo "done";
	}

	function updatePhotoToPublic(){
		$offset = 0;
		do{
			$sql = "select uid from users where is_internal = 1 and origin_uid != 0 order by register_time asc limit {$offset},100";
			$stmt = $this->_db->query($sql);
			$ary = $stmt->fetchAll();

			foreach ($ary as $user_info) {
				$uid = $user_info['uid'];
				$sql = "update content set is_private = 0 and reviewed = 1 where uid = {$uid} limit 1";
				

				$this->_db->exec($sql);
			}

			$count = count($ary);

			$offset += 100;
		}while($count > 0);

		echo "done";
	}


}
?>