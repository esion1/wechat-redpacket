<?php
namespace zhangyonghui\wechat\redpacket;

use yii\base\Event;


class ResultEvent extends Event
{
	public $result;
	
	public $force = false;
}