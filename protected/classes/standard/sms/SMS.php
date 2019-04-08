<?php


/**
 * 短信类
 * 
 * @author Young
 * @class SMS
 */
class SMS
{
    private static $obj;
	private static $sms;
    private function __construct(){}
    private function __clone(){}
    /**
     * 得到实例 
     * 
     * @access public
     * @return mixed
     */
    public static function getInstance()
    {
        if(!self::$obj instanceof self)
        {
            self::$obj = new self();
            self::$sms = new DXSMS();
        }
        return self::$sms;
    }

}