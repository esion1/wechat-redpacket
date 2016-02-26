<?php
namespace zhangyonghui\wechat\redpacket;

use yii\base\InvalidConfigException;
use common\libs\wechat\ResultEvent;
use yii\base\Event;

class MpRedPacket extends RedPacketSDK
{
	public $mch_id;
	
	public $key;
	
	public $cai_path;
	
	const WECHAT_BASE_URL = "https://api.mch.weixin.qq.com/mmpaymkttransfers";
	
	const EVENT_SEND_REDPACKET_CHECK = "sendRedpacketCheck";
	
	public function init()
	{
		if ($this->mch_id === null) {
			throw new InvalidConfigException('The "mch_id" property must be set.');
		} elseif ($this->key === null) {
			throw new InvalidConfigException('The "key" property must be set.');
		} elseif ($this->cai_path === null) {
			throw new InvalidConfigException('The "cai_path" property must be set.');
		}
	}
	
	//现金红包接口
	const SEND_REDPACKET = "/sendredpack";

	public function sendRedpacket($params){
		$result = $this->httpPost(self::SEND_REDPACKET,$this->getXml($params));
		return $result;
	}
	
	//红包查询接口
	const FIND_REDPACKET = "/gethbinfo";
	
	public function findRedpacket($params){
		$result = $this->httpPost(self::FIND_REDPACKET,$this->getXml($params));
		return $result;
	}
	
	//裂变红包接口
	const SEND_GROUP_REDPACKET  = "/sendgroupredpack";
	
	public function sendGroupRedpacket($params){
		$result = $this->httpPost(self::SEND_GROUP_REDPACKET,$this->getXml($params));
		return $result;
	}
	
	//裂变红包查询接口
	const FIND_GROUP_REDPACKET = "/gethbinfo";
	
	public function findGroupRedpacket($params){
		$result = $this->httpPost(self::FIND_GROUP_REDPACKET,$this->getXml($params));
		return $result;
	}
	
	/**
	 * 获取xml格式
	 * @param $params
	 * @return Ambigous <\common\libs\wechat\the, string>
	 */
	public function getXml($params){
		$this->setParams($params);
		return $this->getParams();
	}

	/**
	 * 回调微信地址
	 * 
	 */
	public function parseHttpRequest(callable $callable, $url, $postOptions = null, $otherOptions = null, $force = true)
	{
		$otherOptions=[
			CURLOPT_SSLCERTTYPE=>'PEM',
			CURLOPT_SSLCERT=>$this->cai_path.'/apiclient_cert.pem',
			CURLOPT_SSLKEYTYPE=>'PEM',
			CURLOPT_SSLKEY=>$this->cai_path.'/apiclient_key.pem',
		];
		
		$result = call_user_func_array($callable, [$url, $postOptions, $otherOptions]);
		if(empty($result) && $force===true){
			$event = new ResultEvent();
			$event->result = $result;
			$this->trigger(self::EVENT_SEND_REDPACKET_CHECK,$event); 
			$force = $event->force;
			$this->parseHttpRequest($callable, $url, $postOptions, $otherOptions, $force);
		}
		return $result;
	}
	
	public function parseRequestXml($xml = null)
	{
		if (!empty($xml)) {
			$return = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		}
		return $return;
	}
	
	/**
	 * 微信基本链接
	 */
	protected function httpBuildQuery($url, array $options)
	{
		if (stripos($url, 'http://') === false && stripos($url, 'https://') === false) {
			$url = self::WECHAT_BASE_URL . $url;
		}
		return parent::httpBuildQuery($url, $options);
	}
}