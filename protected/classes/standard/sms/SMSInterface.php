<?php

 /**
  *短信接口
  */
interface SMSInterface
{
    public function sendCode($mobile,$code='123456');
    public function sendTemplate($mobile,$templateId=0,$templateParam=array());
}