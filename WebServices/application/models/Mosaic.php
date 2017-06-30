<?php
class MosaicModel{
	const MAX_NICKNAME_LENGTH = 7;
	protected $_db = null;
  protected $_memcached = null;
  const MOSAIC_AVATAR_SMALL_SIXE = '?imageView2/1/w/200/h/200';
  const QINIU_URL_PREFIX = 'http://photo.imcharm.com/';
  const MOSAIC_IMAGE_MIDDLE_SIZE = '?imageView2/1/w/640/h/640';
  const MOSAIC_IMAGE_SMALL_SIZE = '?imageView2/1/w/200/h/200';

  const DATA_TYPE_DICTIONARY = 0;
  const DATA_TYPE_LIST = 1;
  const DATA_TYPE_SINGLE = 2;

  const PHOTO_TYPE_USER = 0;
  const PHOTO_TYPE_AD = 1;
  const PHOTO_TYPE_SHARED_ORDER = 2;

  const TASK_TYPE_CREATE = 0;
  const TASK_TYPE_START = 1;
  const TASK_TYPE_STOP = 2;
  const TASK_TYPE_INCREASE = 3;

  const HOT_HEAT_THRESHOLD = 10;

	function __construct(){
		$config = new Yaf_Config_Ini(APPLICATION_COINFIG_FILE);
    Yaf_Registry::set('config',$config);
		// $config = Yaf_Registry::get('config')->get('product')->toArray();
		$rds_config = $config->product->rds->toArray();

		$host = $rds_config['host'];
		$username = $rds_config['username'];
		$password = $rds_config['password'];

    if (!($_SERVER['SERVER_ADDR']=='121.43.101.35' || $_SERVER['SERVER_ADDR']=='10.117.46.136')){
        $host = 'rdsvnuuuauvvuaepublic.mysql.rds.aliyuncs.com';
    }



		$db_name = $rds_config['db_name'];
		$dn = "mysql:host={$host};dbname={$db_name}";

    if ($_SERVER['SERVER_ADDR']=='::1'){
      $host = '127.0.0.1';
      $username = 'stan';
      $password = 'fhqifkhk';
      $dn = "mysql:host={$host};port=8889;dbname={$db_name}";
    }

		$this->_db = new PDO($dn,$username,$password);
    $this->_db = $this->_db;

		$this->_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 
		$this->_db->exec("set names 'utf8mb4';");

    $this->_memcached = Yaf_Registry::get('memcached');
	}

	function getClientSecret($client_id=''){
		$sql = "select client_secret from `auth_apps` where client_id=?";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($client_id));

		$info = $stmt->fetchColumn();

		if ($info==null)
			return null;
		else
			return $info;
	}

  function getAuthLog($access_token){
    $sql = "select * from auth_logs where access_token=?";
    $stmt = $this->_db->prepare($sql);
    
    $result = $stmt->execute(array($access_token));
    if ($result){
      $auth_info = $stmt->fetchObject();
      if ($auth_info!=null)
        return (array)$auth_info;
      else
        return null;
    }else
      return null;
  }

  function isDeviceBlocked($device_id){
    $sql = "select `device_status` from `device` where device_id=?";
    $stmt = $this->_db->prepare($sql);
    $stmt->execute(array($device_id));
    $status = intval($stmt->fetchColumn());

    return $status!=0;
  }

	function utf8_strlen($str){
		if(empty($str)){
        	return 0;
    	}
    	if(function_exists('mb_strlen')){
        	return mb_strlen($str,'utf-8');
    	}
    	else {
        	preg_match_all("/./u", $str, $ar);
        	return count($ar[0]);
    	}
	}

	function utf8_substr($str,$start=0) {
    	if(empty($str)){
        	return false;
    	}
    	if (function_exists('mb_substr')){
        	if(func_num_args() >= 3) {
      	      $end = func_get_arg(2);
     	       return mb_substr($str,$start,$end,'utf-8');
     	   }
    	    else {
    	        mb_internal_encoding("UTF-8");
    	       return mb_substr($str,$start);
     	   }       
 	
   	 	}
   	 	else {
      		$null = "";
      	 	preg_match_all("/./u", $str, $ar);
      	  	if(func_num_args() >= 3) {
      	      $end = func_get_arg(2);
      	      return join($null, array_slice($ar[0],$start,$end));
      	 	}
        	else {
            	return join($null, array_slice($ar[0],$start));
        	}
    	}
    }

	public function randomDate($begintime, $endtime="") {
		srand();
    	$begin = strtotime($begintime);  
    	$end = $endtime == "" ? mktime() : strtotime($endtime);  
    	$timestamp = rand($begin, $end);  
    	return $timestamp;  
	}



  function updateLastVisitTime($uid){
    $memcached_user = new MemcachedUserModel();

    $lastvisit = $memcached_user->getLastVisitTime($uid);

    if (intval(time())-$lastvisit > 300){
      $memcached_user->updateLastVisitTime($uid);

      $sql = "update `users` set last_visit_time = current_timestamp where uid = $uid";
      $this->_db->exec($sql);
    }   
  }

  function updateLocation($uid,$latitude,$longitude,$province = 0,$city = 0){
    $latitude = doubleval($latitude);
    $longitude = doubleval($longitude);
    $uid = intval($uid);

    if ($latitude==0 || $longitude==0 || $uid==0)
      return;

    $memcached_user = new MemcachedUserModel();

    $location_info = $memcached_user->getLocation($uid);

    $time = intval($location_info['time']);

    if (intval(time())-$time>300){
      $memcached_user->updateLocation($uid,$latitude,$longitude);

      $latitude_r = doubleval($location_info['latitude']);
      $longitude_r = doubleval($location_info['longitude']);

      // $province_r = intval($location_info['province']);
      

      if ($latitude!=$latitude_r || $longitude!=$longitude_r){
        $geohasher = new Geohash();
        $geohash = $geohasher->encode($latitude,$longitude);
        $geohash = substr($geohash, 0,4);

        if ($province==0)
          $sql = "update `users` set latitude = $latitude,longitude = $longitude,geohash = '{$geohash}' where uid=$uid";
        else
          $sql = "update `users` set latitude = $latitude,longitude = $longitude,province = $province,city = $city,geohash = '{$geohash}' where uid=$uid";
        $this->_db->exec($sql);
      }
    }
  }

  function getProvinceCity($latitude,$longitude){
    $_CACHE = Mosaic_CityInfo::city_info();

    $data = array('location' => $latitude. ",".$longitude,'output' => 'json');
    $data_string = http_build_query($data);
    $url =  "http://api.map.baidu.com/geocoder" ."?".$data_string;

    $result = file_get_contents($url, false); 
    $getresult = json_decode($result);

    if(isset($getresult->status) && $getresult->status == "OK"){
      $proc1 = mb_substr($getresult->result->addressComponent->province, 0, 2, "utf-8");
      $proc2 = mb_substr($getresult->result->addressComponent->province, 0, 3, "utf-8");
      foreach ($_CACHE['city']['provinces'] as $k => $v) {
        if($proc1 == $v || $proc2 == $v) {
          $province = $k;
          $city1 = mb_substr($getresult->result->addressComponent->city, 0, 2, "utf-8");
          $city2 = mb_substr($getresult->result->addressComponent->city, 0, 3, "utf-8");
          $city3 = mb_substr($getresult->result->addressComponent->city, 0, 4, "utf-8");
          foreach ($_CACHE['city']['cities'][$province] as $kk => $vv) {
            if($city1 == $vv || $city2 == $vv || $city3 == $vv) {
              $city = $kk;
              break;
            }
          }
          break;
        }
      }
    }

    if ($province!=0)
      return array('province'=>$province,'city'=>$city);
    else
      return null;
  }

  function push_notification($uid,$content,$push_type = 'notice'){
    //  0 评论，1 回复,2 赞
    //  determine silent or alert
    $silent = true;
    
    $memcached_user = new MemcachedUserModel();

    $last_push_time = intval($memcached_user->getLastPushTime($uid));
    $now_time = intval(time());

    if ($push_type == 'notice')
      $silent = false;
    else if ($now_time-$last_push_time>60*60){
        $silent = false;
        $memcached_user->updatePushTime($uid);
    }

    //  get badge number
    $sql = "select count(*) from `action_log` where `to`=$uid";
    $stmt = $this->_db->query($sql);
    $badge = intval($stmt->fetchColumn());
    //  determine ios or android
    $sql = "select device_type,push_token from `device` where `uid`=$uid order by create_time desc";
    $stmt = $this->_db->query($sql);

    $deviceInfo = (array)$stmt->fetchObject();
    $device_type = intval($deviceInfo['device_type']);
    $push_token = $deviceInfo['push_token'];

    $quiet = $silent ? 1 : 0;

    $sql = "insert into `push`.`apn_list` (uid,device_token,content,badge,quiet,push_type) values (?,?,?,?,?,?)";
    $stmt = $this->_db->prepare($sql);

    $result = $stmt->execute(array($uid,$push_token,$content,$badge,$quiet,$push_type));

    return true;
  }

  function insertAuthLog($uid,$device_id,$client_id,$access_token){
    $uid = intval($uid);
    if ($uid==0)
      $uid = $this->_requestor_uid;
    if ($uid==0)
      return;

    $sql = "delete from auth_logs where uid=?";
    $stmt = $this->_db->prepare($sql);
    $del_result = $stmt->execute(array($uid));

    $sql = "delete from auth_logs where device_id=$device_id";
    $this->_db->exec($sql);

    if ($del_result){
      $sql = "insert into auth_logs (access_token,uid,client_id,device_id) values(?,?,?,?)";
      $stmt = $this->_db->prepare($sql);
      $insert_result = $stmt->execute(array($access_token,$uid,$client_id,$device_id));

      return true;
    }else
      return false;
  }

  function getTags(){
    $sql = "select * from `tags`";
    $stmt = $this->_db->query($sql);
    $ary = $stmt->fetchAll();

    $mut = array();
    for ($i=0;$i<count($ary);$i++){
      $mut[] = $ary[$i]['tag'];
    }

    return $mut;
  }

  function getAvatars(){
    $sql = "select * from `avatars`";
    $stmt = $this->_db->query($sql);
    $ary = $stmt->fetchAll();

    return $ary;
  }

  function getDescriptionTemplets(){
    $sql = "select content,gender from `templet`";
    $stmt = $this->_db->query($sql);
    $ary = $stmt->fetchAll();

    for ($i=0;$i<count($ary);$i++){
      $templets[] = $ary[$i]['content'];
    }

    return $templets;
  }

  private function is_bigger($app_version,$reviewed_version){
      $ary0 = explode('.', $app_version);
      $ary1 = explode('.', $reviewed_version);

      $is_bigger = false;

      for ($i=0;$i<count($ary0);$i++){
          $n0 = intval($ary0[$i]);
          $n1 = intval($ary1[$i]);
          if ($n0 > $n1){
              $is_bigger = true;
              break;
          }
      }

      return $is_bigger;
  }

  function getAppConfig($app_version,$source = null){
    $app_config = array();
    $app_config['apple_reviewed'] = 0;
    $app_config['demo_disabled'] = 0;


    $reviewed_version = "1.1";

    $is_bigger_apple = $this->is_bigger($app_version,$reviewed_version);


    if ($is_bigger_apple)
      $app_config["apple_reviewed"] = 0;
    else
      $app_config["apple_reviewed"] = 1;


    if (empty($source))
      $app_config['reviewed'] = $app_config['apple_reviewed'];
    else if ($source=="同步推")
      $app_config['reviewed'] = $app_config['tbt_reviewed'];
    else if ($source=="快用")
      $app_config['reviewed'] = $app_config['kuaiyong_reviewed'];
    else
      $app_config['reviewed'] = 1;

    if (intval($app_configp['reviewed'])==0 && empty($source))
      $app_config["show_ads"] = 1;

    $user_model = new UserModel();
    $app_config['contact_list'] = $user_model->getContactList();

    return $app_config;
  }

  function convert_to_json($str){
    $str = base64_decode($str);
    $str = str_replace("\n", "", $str);
    $str = str_replace(" = "," : ",$str);
    $str = str_replace(";",",",$str);
    $str = str_replace(",}","}",$str);

    return "".$str;
  }

  function get_filter_from_array($ary){
      if (empty($ary))
        return null;

      $filter_string = "(";

      foreach ($ary as $filter) {
          if (strlen($filter_string)!=1)
              $filter_string .= ",";

          $filter_string .= $filter;
      }

      $filter_string .= ")";

      return $filter_string;
  }

    function appendAvatars($user_list){
      $ary = array();

      foreach ($user_list as $profile) {
        $avatar = $profile['avatar'];
        if (!empty($avatar) && !(strpos($avatar, 'http://') === 0)){
          $small_url = self::QINIU_URL_PREFIX . $avatar . self::MOSAIC_AVATAR_SMALL_SIXE;
          $middle_url = self::QINIU_URL_PREFIX . $avatar . self::MOSAIC_IMAGE_MIDDLE_SIZE;
          $origin_url = self::QINIU_URL_PREFIX . $avatar;
 
          $avatar = self::QINIU_URL_PREFIX . $avatar.self::MOSAIC_AVATAR_SMALL_SIXE;


          $profile['avatar'] = $avatar;
          $profile['avatars'] = array('small_url'=>$small_url,'middle_url'=>$middle_url,'origin_url'=>$origin_url);
        }else if (strpos($avatar, 'http://') === 0 && !strpos($avatar, '?imageView')){
          $small_url = $avatar . self::MOSAIC_AVATAR_SMALL_SIXE;
          $middle_url = $avatar . self::MOSAIC_IMAGE_MIDDLE_SIZE;
          $origin_url = $avatar;

          $avatar = $avatar.self::MOSAIC_AVATAR_SMALL_SIXE;
          $profile['avatar'] = $avatar;
          $profile['avatars'] = array('small_url'=>$small_url,'middle_url'=>$middle_url,'origin_url'=>$origin_url);
        }
        $ary[] = $profile;
      }

      return $ary;
  }

  function appendAvatar($profile){
    $avatar = $profile['avatar'];
    if (!empty($avatar) && !(strpos($avatar, 'http://') === 0)){
      $small_url = self::QINIU_URL_PREFIX . $avatar . self::MOSAIC_AVATAR_SMALL_SIXE;
      $middle_url = self::QINIU_URL_PREFIX . $avatar . self::MOSAIC_IMAGE_MIDDLE_SIZE;
      $origin_url = self::QINIU_URL_PREFIX . $avatar;

      $avatar = self::QINIU_URL_PREFIX .$avatar.self::MOSAIC_AVATAR_SMALL_SIXE;
      $profile['avatar'] = $avatar;
      $profile['avatars'] = array('small_url'=>$small_url,'middle_url'=>$middle_url,'origin_url'=>$origin_url);

    }else if (strpos($avatar, 'http://') === 0 && !strpos($avatar, '?imageView')){
      $small_url = $avatar . self::MOSAIC_AVATAR_SMALL_SIXE;
      $middle_url = $avatar . self::MOSAIC_IMAGE_MIDDLE_SIZE;
      $origin_url = $avatar;

      $avatar = $avatar.self::MOSAIC_AVATAR_SMALL_SIXE;
      $profile['avatar'] = $avatar;
      $profile['avatars'] = array('small_url'=>$small_url,'middle_url'=>$middle_url,'origin_url'=>$origin_url);
    }

    return $profile;
  }

  function appendPhotoList($data_list,$photo_type){
    //  0－用户照片 1－投放素材   2－晒单照片
    if (empty($data_list))
      return null;

    $related_key = 'uid';
    switch ($photo_type) {
      case 1:
        $related_key = 'ad_id';
        break;
      case 2:
        $related_key = 'order_id';
        break;
      default:
        # code...
        break;
    }

    $ary = array();

    $data_ids = array();

    foreach ($data_list as $data) {
      $data_ids[] = intval($data[$related_key]);
    }

    $data_ids_string = implode(',', $data_ids);

    if (0 == $photo_type)
      $sql = "select * from photos where photo_type = $photo_type and uid in ({$data_ids_string}) order by photo_id asc";
    else
      $sql = "select * from photos where photo_type = $photo_type and related_id in ({$data_ids_string}) order by photo_id asc";

    $stmt = $this->_db->query($sql);
    $photo_list = $stmt->fetchAll();

    $allPhotoInfo = array();

    foreach ($photo_list as $photo) {
      $related_id = intval($photo['related_id']);
      $photo_uri = $photo['photo_uri'];
      $photo['small_url'] = self::QINIU_URL_PREFIX . $photo_uri . self::MOSAIC_IMAGE_SMALL_SIZE;
      $photo['middle_url'] = self::QINIU_URL_PREFIX . $photo_uri . self::MOSAIC_IMAGE_MIDDLE_SIZE;
      $photo['origin_url'] = self::QINIU_URL_PREFIX . $photo_uri . self::MOSAIC_IMAGE_MIDDLE_SIZE;

      if (isset($allPhotoInfo[$related_id])){
        $temp_list = $allPhotoInfo[$related_id];
      }else{
        $temp_list = array();
      }
      $temp_list[] = $photo;

      $allPhotoInfo[$related_id] = $temp_list;
    }

    $ary = array();
    foreach ($data_list as $data) {
      $data['photo_list'] = $allPhotoInfo[$data[$related_key]];
      $ary[] = $data;
    }

    return $ary;
  }

  function recordAction($uid, $endpoint, $module_name, $parameters){
      $sql = "INSERT INTO action_logs (uid, end_point, module_name, parameters) VALUES (?,?,?,?)";

      $stmt = $this->_db->prepare($sql);

      $stmt->execute(array($uid, $endpoint, $module_name, $parameters));
  }

  function translatePhotoURIList($photo_uris){
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
      $photo_info['origin_url'] = self::QINIU_URL_PREFIX . $photo_uri . self::MOSAIC_IMAGE_MIDDLE_SIZE;

      $url_list[] = $photo_info;
    }

    return $url_list;
  }

  function memcached_remove($key, $value, $expiration_time = 0){
    $ary = $this->_memcached($key);
    if (in_array($value, $ary)){
      $index = array_search($value, $ary);
      array_splice($ary, $index, 1);
    }

    $this->_memcached->set($key,$ary,$expiration_time);
  }

  function location_from_id($province = 0,$city = 0){
      $provinces = array('未填','北京','上海','重庆','安徽','福建','甘肃','广东','广西','贵州','海南','河北','黑龙江','河南','香港','湖北','湖南','江苏','江西','吉林','辽宁','澳门','内蒙古','宁夏','青海','山东','山西','陕西','四川','台湾','天津','新疆','西藏','云南','浙江','海外');
      $cities = array('未填','东城','西城','崇文','宣武','朝阳','丰台','石景山','海淀','门头沟','房山','通州','顺义','昌平','大兴','平谷','怀柔','密云','延庆','崇明','黄浦','卢湾','徐汇','长宁','静安','普陀','闸北','虹口','杨浦','闵行','宝山','嘉定','浦东','金山','松江','青浦','南汇','奉贤','朱家角','万州','涪陵','渝中','大渡口','江北','沙坪坝','九龙坡','南岸','北碚','万盛','双挢','渝北','巴南','黔江','长寿','綦江','潼南','铜梁','大足','荣昌','壁山','梁平','城口','丰都','垫江','武隆','忠县','开县','云阳','奉节','巫山','巫溪','石柱','秀山','酉阳','彭水','江津','合川','永川','南川','合肥','安庆','蚌埠','亳州','巢湖','滁州','阜阳','贵池','淮北','淮化','淮南','黄山','九华山','六安','马鞍山','宿州','铜陵','屯溪','芜湖','宣城','福州','福安','龙岩','南平','宁德','莆田','泉州','三明','邵武','石狮','晋江','永安','武夷山','厦门','漳州','兰州','白银','定西','敦煌','甘南','金昌','酒泉','临夏','平凉','天水','武都','武威','西峰','嘉峪关','张掖','广州','潮阳','潮州','澄海','东莞','佛山','河源','惠州','江门','揭阳','开平','茂名','梅州','清远','汕头','汕尾','韶关','深圳','顺德','阳江','英德','云浮','增城','湛江','肇庆','中山','珠海','南宁','百色','北海','桂林','防城港','河池','贺州','柳州','来宾','钦州','梧州','贵港','玉林','贵阳','安顺','毕节','都匀','凯里','六盘水','铜仁','兴义','玉屏','遵义','海口','三亚','五指山','琼海','儋州','文昌','万宁','东方','定安','屯昌','澄迈','临高','万宁','白沙黎族','昌江黎族','乐东黎族','陵水黎族','保亭黎族','琼中黎族','西沙群岛','南沙群岛','中沙群岛','石家庄','保定','北戴河','沧州','承德','丰润','邯郸','衡水','廊坊','南戴河','秦皇岛','唐山','新城','邢台','张家口','哈尔滨','北安','大庆','大兴安岭','鹤岗','黑河','佳木斯','鸡西','牡丹江','齐齐哈尔','七台河','双鸭山','绥化','伊春','郑州','安阳','鹤壁','潢川','焦作','济源','开封','漯河','洛阳','南阳','平顶山','濮阳','三门峡','商丘','新乡','信阳','许昌','周口','驻马店','香港','九龙','新界','武汉','恩施','鄂州','黄冈','黄石','荆门','荆州','潜江','十堰','随州','武穴','仙桃','咸宁','襄阳','襄樊','孝感','宜昌','长沙','常德','郴州','衡阳','怀化','吉首','娄底','邵阳','湘潭','益阳','岳阳','永州','张家界','株洲','南京','常熟','常州','海门','淮安','江都','江阴','昆山','连云港','南通','启东','沭阳','宿迁','苏州','太仓','泰州','同里','无锡','徐州','盐城','扬州','宜兴','仪征','张家港','镇江','周庄','南昌','抚州','赣州','吉安','景德镇','井冈山','九江','庐山','萍乡','上饶','新余','宜春','鹰潭','长春','白城','白山','珲春','辽源','梅河','吉林','四平','松原','通化','延吉','沈阳','鞍山','本溪','朝阳','大连','丹东','抚顺','阜新','葫芦岛','锦州','辽阳','盘锦','铁岭','营口','澳门','呼和浩特','阿拉善盟','包头','赤峰','东胜','海拉尔','集宁','临河','通辽','乌海','乌兰浩特','锡林浩特','银川','固原','中卫','石嘴山','吴忠','西宁','德令哈','格尔木','共和','海东','海晏','玛沁','同仁','玉树','济南','滨州','兖州','德州','东营','菏泽','济宁','莱芜','聊城','临沂','蓬莱','青岛','曲阜','日照','泰安','潍坊','威海','烟台','枣庄','淄博','太原','长治','大同','候马','晋城','离石','临汾','宁武','朔州','忻州','阳泉','榆次','运城','西安','安康','宝鸡','汉中','渭南','商州','绥德','铜川','咸阳','延安','榆林','成都','巴中','达州','德阳','都江堰','峨眉山','涪陵','广安','广元','九寨沟','康定','乐山','泸州','马尔康','绵阳','眉山','南充','内江','攀枝花','遂宁','汶川','西昌','雅安','宜宾','自贡','资阳','台北','基隆','台南','台中','高雄','屏东','南投','云林','新竹','彰化','苗栗','嘉义','花莲','桃园','宜兰','台东','金门','马祖','澎湖','其它','天津','和平','东丽','河东','西青','河西','津南','南开','北辰','河北','武清','红挢','塘沽','汉沽','大港','宁河','静海','宝坻','蓟县','乌鲁木齐','阿克苏','阿勒泰','阿图什','博乐','昌吉','东山','哈密','和田','喀什','克拉玛依','库车','库尔勒','奎屯','石河子','塔城','吐鲁番','伊宁','拉萨','阿里','昌都','林芝','那曲','日喀则','山南','昆明','大理','保山','楚雄','大理','东川','个旧','景洪','开远','临沧','丽江','六库','潞西','曲靖','思茅','文山','西双版纳','玉溪','中甸','昭通','杭州','安吉','慈溪','定海','奉化','海盐','黄岩','湖州','嘉兴','金华','临安','临海','丽水','宁波','瓯海','平湖','千岛湖','衢州','江山','瑞安','绍兴','嵊州','台州','温岭','温州','余姚','舟山','美国','英国','法国','瑞士','澳洲','新西兰','加拿大','奥地利','韩国','日本','德国','意大利','西班牙','俄罗斯','泰国','印度','荷兰','新加坡','欧洲','北美','南美','亚洲','非洲','大洋洲');

      if ($province == 0)
        return null;
      else{
        if ($city > 0){
          return $provinces[$province] . $cities[$city];
        }else{
          return $provinces[$province];
        }
      }
  }
  
}
?>