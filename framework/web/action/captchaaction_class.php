<?php

/**
 * 验证码Action
 * 
 * @author Young
 * @class CaptchaAction
 */
class CaptchaAction extends BaseAction
{
	//Action运行入口
	public function run()
	{
		$captcha = new Captcha();
		$captcha->renderImage();
	}
}
?>