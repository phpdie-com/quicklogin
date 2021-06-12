<?php

namespace Phpdie\Quicklogin;

abstract class AbstractOauth
{
    protected $appID;
    protected $appSecret;
    protected $loginUri;    //授权登录的uri
    protected $redirectUri; //授权成功后重定向uri

    protected function __construct($appID, $appSecret, $redirectUri, $loginUri)
    {
        $this->setAppID($appID);
        $this->setAppSecret($appSecret);
        $this->setRedirectUri($redirectUri);
        $this->setLoginUri($loginUri);
    }

    public function setAppID($appID)
    {
        $this->appID = $appID;
    }

    public function setAppSecret($appSecret)
    {
        $this->appSecret = $appSecret;
    }

    public function setLoginUri($loginUri)
    {
        $this->loginUri = $loginUri;
    }

    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }

    //请求第三方授权的登录页面
    abstract function requestLoginUri();

    // //获取accesstoken
    abstract function getAccessToken($code, $refresh_token = '');

    // //获取用户基本信息
    abstract function getUserInfo($access_token);
}
