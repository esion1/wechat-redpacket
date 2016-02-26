# wechat-redpacket

微信红包查询和发放sdk

您可以使用composer来安装, 添加下列代码在您的``composer.json``文件中并执行``composer update``操作

```json
{
    "require": {
       "zhangyonghui/yii2-wechat-redpacket": "1.0.0"
    }
}
```


使用示例
--------
在使用前,请参考微信商户公众平台的[开发文档](https://pay.weixin.qq.com/wiki/doc/api/cash_coupon.php?chapter=13_5#)

Wechat定义方式
```php
//在config/web.php配置文件中定义component配置信息
'components' => [
  .....
  'wechat' => [
    'class' => 'callmez\wechat\sdk\Wechat',
    'appId' => '微信公众平台中的appid',
    'appSecret' => '微信公众平台中的secret',
    'token' => '微信服务器对接您的服务器验证token'
  ]
  ....
]

// 全局公众号sdk使用
$wechat = Yii::$app->wechat; 

//多商户号使用方式
$wechat = Yii::createObject([
    'class' => 'zhangyonghui\wechat\sdk\MpRedPacket',
    'key' => '商户平台中的秘钥',
    'mch_id' => '商户平台中的mch_id',
    'cai_path' => '商户平台中的证书安装路径'
]);

Wechat方法使用(部分示例)
```php

//查询红包
$data['nonce_str']=$wechat->get_nonce_str();
$data['mch_billno']=$wechat->get_mch_billno($mch_id);
$data['bill_type']="MCHT";
$data['appid']="";
$data['mch_id']="";
$wechat->findRedpacket($data);


//发送红包
$data['nonce_str']=$wechat->get_nonce_str();
$data['mch_billno']=$wechat->get_mch_billno($mch_id);
$data['send_name']="红包发送人";
$data['re_openid']="发送openid";
$data['total_amount']="红包金额";
$data['total_num']="红包数量";  //现金红包1  分裂红包最小3
$data['wishing']="红包祝福语";
//判断是否是分裂红包
if(empty($params["amt_type"]))
	$data['client_ip']= "127.0.0.1";
else 
	$data['amt_type']= "ALL_RAND";
$data['act_name']="红包活动名称";
$data['remark']="红包备注";
$wechat->sendRedpacket($data);

//自定义重试机制
$wechat->on(MpRedPacket::EVENT_SEND_REDPACKET_CHECK,[$this,'runRedpacket']);

//事件方法
private $netCount=3;

function runRedpacket($event){
   $this->netCount--;
   if($this->netCount==0)
    	$event->force = false;
   else
    	$event->force = true;
}

```

反馈或贡献代码
--------------
您可以在[这里](https://github.com/g457165949/wechat-redpacket.git)给我提出在使用中碰到的问题或Bug.
我会在第一时间回复您并修复.

您也可以 发送邮件457165949@qq.com给我并且说明您的问题.

如果你有更好代码实现,请fork项目并发起您的pull request.我会及时处理. 感谢!
