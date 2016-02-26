<?php
namespace zhangyonghui\wechat\redpacket;

use DOMDocument;
use DOMElement;
use DOMText;
use yii\base\Event;
use yii\base\Component;
use yii\helpers\ArrayHelper;

/**
 * 微信 SDK API 操作类
 *
 * 微信商户平台文档：{@link https://pay.weixin.qq.com/wiki/doc/api/cash_coupon.php?chapter=13_5#
 *
 * @package wechat
 * @author  zhangyonghui
 * @version 1.0
 * @updated 2016/1/28
 */
abstract class RedPacketSDK extends Component {

	private $params;
	
	const EVENT_RESPONSE_RESULT = "event_response_result";

	/**
	 * @return the $params
	 */
	public function getParams() {
		$this->params["sign"]=$this->sign();
		return $this->arrayToXml();
	}

	/**
	 * @param field_type $params
	 */
	public function setParams($params) {
		$this->params = $params;
	}

	/**
	 * 数组转成xml字符串
	 *
	 * @return string
	 */
	protected function arrayToXml()
	{
		$xml = '<xml>';
		foreach($this->params as $key => $value) {
			$xml .= "<{$key}>";
			$xml .= "<![CDATA[{$value}]]>";
			$xml .= "</{$key}>\n";
		}
		$xml .= '</xml>';
		return $xml;
	}
	
	/**
	 * 签名
	 * 规则：
	 * 先按照参数名字典排序
	 * 用&符号拼接成字符串
	 * 最后拼接上API秘钥，str&key=密钥
	 * md5运算，全部转换为大写
	 *
	 * @return string
	 */
	protected function sign($type = "MD5")
	{
		ksort($this->params);
		$signStr = $this->arrayToString();
		$signStr .= '&key='.$this->key;
		if($type == 'MD5') {
			return $this->signMd5($signStr);
		}
		throw new \InvalidArgumentException('Unsupported sign method');
	}
	
	/**
	 * 数组转成字符串
	 * @return string
	 */
	protected function arrayToString()
	{
		$params = $this->filter($this->params);
		$str = '';
		foreach($params as $key => $value) {
			$str .= "{$key}={$value}&";
		}
		return substr($str, 0, strlen($str)-1);
	}
	
	/**
	 * 过滤待签名数据，sign和空值不参加签名
	 * @return array
	 */
	protected function filter($params)
	{
		$tmpParams = [];
		foreach ($params as $key => $value) {
			if( $key != 'sign' && ! empty($value) ) {
				$tmpParams[$key] = $value;
			}
		}
		return $tmpParams;
	}
	
	/**
	 * MD5签名
	 *
	 * @param string $str 待签名字符串
	 * @return string 生成的签名，最终数据转换成大写
	 */
	protected function signMd5($str)
	{
		$sign = md5($str);
			
		return strtoupper($sign);
	}
	
	/**
	 * 商户订单号mch_id+yyyymmdd+10位数字
	 * @param $length 默认 10位
	 * @param string $chars
	 * @return string
	 */
	public function get_mch_billno($str = ""){
		$rand_num = $this->get_num();
		return $str.date("Ymd").$rand_num;
	}
	
	public function get_num($length = 10, $chars = '0123456789'){
		$rand_num = '';
		$max = strlen($chars) - 1;
		for($i = 0; $i < $length; $i++) {
			$rand_num.= $chars[mt_rand(0, $max)];
		}
		return $rand_num;
	}
	
	/**
	 * 32位随机字符串
	 */
	public function get_nonce_str(){
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()+-';
		$random = $chars[mt_rand(0,73)].$chars[mt_rand(0,73)].$chars[mt_rand(0,73)].$chars[mt_rand(0,73)].$chars[mt_rand(0,73)];//Random 5 times
		$content = uniqid().$random;  // 类似 5443e09c27bf4aB4uT
		return md5($content);
	}
	
	/**
	 * 创建微信格式的XML
	 * @param array $data
	 * @param null $charset
	 * @return string
	 */
	public function xml(array $data, $charset = null)
	{
		$dom = new DOMDocument('1.0', $charset === null ? Yii::$app->charset : $charset);
		$root = new DOMElement('xml');
		$dom->appendChild($root);
		$this->buildXml($root, $data);
		$xml = $dom->saveXML();
		return trim(substr($xml, strpos($xml, '?>') + 2));
	}
	
	/**
	 * @see yii\web\XmlResponseFormatter::buildXml()
	 */
	protected function buildXml($element, $data)
	{
		if (is_object($data)) {
			$child = new DOMElement(StringHelper::basename(get_class($data)));
			$element->appendChild($child);
			if ($data instanceof Arrayable) {
				$this->buildXml($child, $data->toArray());
			} else {
				$array = [];
				foreach ($data as $name => $value) {
					$array[$name] = $value;
				}
				$this->buildXml($child, $array);
			}
		} elseif (is_array($data)) {
			foreach ($data as $name => $value) {
				if (is_int($name) && is_object($value)) {
					$this->buildXml($element, $value);
				} elseif (is_array($value) || is_object($value)) {
					$child = new DOMElement(is_int($name) ? $this->itemTag : $name);
					$element->appendChild($child);
					$this->buildXml($child, $value);
				} else {
					$child = new DOMElement(is_int($name) ? $this->itemTag : $name);
					$element->appendChild($child);
					$child->appendChild(new DOMText((string) $value));
				}
			}
		} else {
			$element->appendChild(new DOMText((string) $data));
		}
	}
	
	
	abstract public function parseHttpRequest(callable $callable, $url, $postOptions = null);
	
	
	/**
	 * Http Get 请求
	 * @param $url
	 * @param array $options
	 * @return mixed
	 */
	public function httpGet($url, array $options = [])
	{
		return $this->parseHttpRequest(function($url) {
			return $this->http($url);
		}, $this->httpBuildQuery($url, $options));
	}
	
	/**
	 * Http Post 请求
	 * @param $url
	 * @param array $postOptions
	 * @param array $options
	 * @param array $otherOptions
	 * @return mixed
	 */
	public function httpPost($url,$postOptions, array $options = [] ,array $otherOptions = [])
	{
		
		return $this->parseHttpRequest(function($url, $postOptions, $otherOptions) {
			return $this->http($url, ArrayHelper::merge([
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => $postOptions
					], $otherOptions));
		}, $this->httpBuildQuery($url, $options), $postOptions , $otherOptions);
	}
	
	/**
	 *  * Http Raw数据 Post 请求
	 * @param $url
	 * @param $postOptions
	 * @param array $options
	 * @param array $otherOptions
	 * @return mixed
	 */
	public function httpRaw($url, $postOptions, array $options = [] , array $otherOptions = [])
	{
		return $this->parseHttpRequest(function($url, $postOptions,$otherOptions) {
			return $this->http($url, ArrayHelper::merge([
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => is_array($postOptions) ? json_encode($postOptions, JSON_UNESCAPED_UNICODE) : $postOptions
					], $otherOptions));
		}, $this->httpBuildQuery($url, $options), $postOptions , $otherOptions);
	}
	
	/**
	 * Api url 组装
	 * @param $url
	 * @param array $options
	 * @return string
	 */
	protected function httpBuildQuery($url, array $options)
	{
		if (!empty($options)) {
			$url .= (stripos($url, '?') === null ? '&' : '?') . http_build_query($options);
		}
		return $url;
	}
	
	abstract protected function parseRequestXml($result);
	
	/**
	 * Http基础库 使用该库请求微信服务器
	 * @param $url
	 * @param array $options
	 * @return bool|mixed
	 */
	protected function http($url, $options = [])
	{
		$options = [
		CURLOPT_URL => $url,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_CONNECTTIMEOUT => 30,
		CURLOPT_RETURNTRANSFER => true,
		] + (stripos($url, "https://") !== false ? [
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1 // 微信官方屏蔽了ssl2和ssl3, 启用更高级的ssl
				] : []) + $options;
	
		$curl = curl_init();
		curl_setopt_array($curl, $options);
		$content = curl_exec($curl);
		$status = curl_getinfo($curl);
		curl_close($curl);
		if (isset($status['http_code']) && $status['http_code'] == 200) {
			return $this->parseRequestXml($content);
		}
		return false;
	}

}
?>