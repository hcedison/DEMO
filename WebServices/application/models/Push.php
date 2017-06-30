<?php
class PushModel extends  MosaicModel{
	function pushMessage($myuid,$uid,$content){
		$myuid = intval($myuid);
		$uid = intval($uid);

		$len = strlen($content);
		if ($len>20)
			$content = substr($content,0,20)."...";

		
		
		$sql = "select nickname from `users` where uid=$myuid";
		$stmt = $this->_db->query($sql);
		$nickname = $stmt->fetchColumn();



		//	get pm_privacy
		$pm_privacy = 0;

		$sql = "select count(1) from users where (uid = $myuid or uid = $uid) and (vip_expiration_time > current_timestamp or is_super_vip > 0)";

		$stmt = $this->_db->query($sql);
		$count = intval($stmt->fetchColumn());
		if ($count>0)
			$pm_privacy = 1;

		if (0==$pm_privacy or $len==0)
			$content = $nickname."给你发来一条消息";
		else
			$content = $nickname.":".$content;

		$this->push_notification($uid,$content,true);
	}

	function insertNotice($type = 'visit',$uid,$to_uid){
		/*
		foreach($ary as $notice){
			$type = intval($notice['type']);
			switch ($type) {
				case 0:$n_message++;break;
				case 1:$n_visit++;break;
				case 2:$n_follow++;break;
				case 3:$n_like++;break;
				case 4:$n_comment++;break;
				case 5:$n_reply++;break;
				case 6:$n_apply++;break;
				case 7:$n_intimate++;break;
				default:break;
			}
		}

		*/
		$action_type = 0;
		switch ($type){
			case 'visit':
				$action_type = 1;
				$this->push_notification($to_uid,"刚刚有人看过你",'seeme');
				break;
			case 'follow':
				$action_type = 2;
				$this->push_notification($to_uid,"刚刚有人关注你",'follow');
			case 'apply':
				$action_type = 6;
			default:return;
		}

		$sql = "insert into action_log (`from`,`to`,`type`) values (?,?,?)";
		$stmt = $this->_db->prepare($sql);

		$stmt->execute(array($uid,$to_uid,$action_type));
	}

	function getNoticeSettings($uid){
		$user_memcached = new MemcachedUserModel();
		$info = $user_memcached->getNoticeSettings($uid);

		if (empty($info)){
			$sql = "select * from notice_settings where uid = $uid";
			$stmt = $this->_db->query($sql);
			$info = $stmt->fetchAll()[0];

			if (empty($info)){
				$info = array('uid'=>$uid,'pm'=>1,'seeme'=>1);
				$this->updateNoticeSettings($uid,$info);
			}
		}
		

		return $info;
	}

	function updateNoticeSettings($uid,$info){
		$pm = intval($info['pm']);
		$seeme = intval($info['seeme']);
		$sql = "insert into `notice_settings` (uid,pm,seeme) values($uid,$pm,$seeme) on duplicate key update pm = VALUES(pm),seeme = VALUES(seeme)";

		$user_memcached = new MemcachedUserModel();
		$user_memcached->updateNoticeSettings($uid,array('uid'=>$uid,'pm'=>$pm,'seeme'=>$seeme));

		$this->_db->exec($sql);
	}
}
?>