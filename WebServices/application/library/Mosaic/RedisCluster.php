<?php
class Mosaic_RedisCluster{

	private $_isUseCluster = false;

	private $_sn = 0;

	private $_linkHandle = array(
		'master' => null,
		'slave' => array(),
	);
	
	public function __construct($_isUseCluster = false){
		$this->_isUseCluster = $_isUseCluster;
	}
	
	function getRedis($isMaster=true,$slaveOne=true){

		if($isMaster){
            return $this->_linkHandle['master'];

        }else{
        	#print_r($this->_getSlaveRedis());
            return $slaveOne ? $this->_getSlaveRedis() : $this->_linkHandle['slave'];
        }
	}

	function master(){
		return $this->_linkHandle['master'];
	}

	function slave(){
		return $this->_getSlaveRedis();
	}

	public function connect($config = array(),$isMaster = true){
		$init_config_keys = array('host','port','auth');
		foreach ($init_config_keys as $key) {
			if(!isset($config[$key])){
				throw new Exception("the Params of Redis Conf Is Wrong", 1);
			}
		}

		$host = $config['host'];

		if (!($_SERVER['SERVER_ADDR']=='218.244.130.177' || $_SERVER['SERVER_ADDR']=='10.162.50.101')){
			$host = '121.43.110.109';
		}


		if($isMaster){
			$this->_linkHandle['master'] = new Redis();
			$this->_linkHandle['master']->pconnect($host,$config['port']);
			if(!empty($config['auth'])){
				$this->_linkHandle['master']->auth($config['auth']);
			}
		}else{
			$this->_linkHandle['slave'][$this->_sn] = new Redis();
			$this->_linkHandle['slave'][$this->_sn]->pconnect($host,$config['port']);
			if(!empty($config['auth'])){
				$this->_linkHandle['slave'][$this->_sn]->auth($config['auth']);
			}
			++$this->_sn;
		}
	}

	# 关闭选择 0:master,1:slave,2:all
	public function close($flag = 2){
		switch($flag){
            // 关闭 Master
            case 0:
                $this->getRedis()->close();
            break;
            // 关闭 Slave
            case 1:
                for($i=0; $i<$this->_sn; ++$i){
                    $this->_linkHandle['slave'][$i]->close();
                }
            break;
            // 关闭所有
            case 1:
                $this->getRedis()->close();
                for($i=0; $i<$this->_sn; ++$i){
                    $this->_linkHandle['slave'][$i]->close();
                }
            break;
        }
        return true;
	}

	private function _getSlaveRedis(){
		// 就一台 Slave 机直接返回
        if($this->_sn <= 1){
            return $this->_linkHandle['slave'][0];
        }
        // 随机 Hash 得到 Slave 的句柄
        $hash = mt_rand(0,$this->_sn - 1);
        return $this->_linkHandle['slave'][$hash];
	}


}