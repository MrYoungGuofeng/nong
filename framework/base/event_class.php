<?php

/**
 * 事件处理类
 * 
 * @author Young
 * @class Event
 */
class Event
{
	public $sender;

	public $handled = false;

	public function __construct($sender)
	{
		$this->sender = $sender;
	}
}
