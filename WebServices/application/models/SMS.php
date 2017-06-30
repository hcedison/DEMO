<?php
class SMSModel extends MosaicModel{
	const ACCOUNT_SID = "8a216da857087494015709191ddc0155";
	const AUTH_TOKEN = "852d585dd146494a8e5f9c0137e353f1";
	const APP_ID = "8a216da85741a1b901574d51782d08e1";
	const BASE_URL = "https://app.cloopen.com:8883/2013-12-26/Accounts/";

	function sendAuthCode($phone,$code){
		$time = date("YmdHis",time());
		$sig = strtoupper(md5(self::ACCOUNT_SID . self::AUTH_TOKEN . $time));
		$authorization = base64_encode(self::ACCOUNT_SID . ':' . $time);

		$url = self::BASE_URL . self::ACCOUNT_SID . '/SMS/TemplateSMS?sig=' . $sig;

		$post_data = array (
		"appId" => self::APP_ID,
	    "to" => $phone,
	    "templateId" => "119711",
	    "datas" => array($code,"30"),
		);

		$data_string = json_encode($post_data);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_URL, $url);		
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: '.strlen($data_string),'Authorization'.':'.$authorization));
		curl_setopt($ch, CURLOPT_HEADER, 1 );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		$output = curl_exec($ch);
		curl_close($ch);
		
		return $output;
	}

	function sendTempPassword($phone,$temp_password){
		$time = date("YmdHis",time());
		$sig = strtoupper(md5(self::ACCOUNT_SID . self::AUTH_TOKEN . $time));
		$authorization = base64_encode(self::ACCOUNT_SID . ':' . $time);

		$url = self::BASE_URL . self::ACCOUNT_SID . '/SMS/TemplateSMS?sig=' . $sig;

		$post_data = array (
		"appId" => self::APP_ID,
	    "to" => $phone,
	    "templateId" => "57466",
	    "datas" => array($temp_password,"30"),
		);

		$data_string = json_encode($post_data);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_URL, $url);		
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: '.strlen($data_string),'Authorization'.':'.$authorization));
		curl_setopt($ch, CURLOPT_HEADER, 1 );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		$output = curl_exec($ch);
		curl_close($ch);
		
		return $output;
	}


}
?>