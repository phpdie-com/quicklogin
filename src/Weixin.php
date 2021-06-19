<?php

namespace Phpdie\Quicklogin;

use Phpdie\Quicklogin\AbstractOauth;

class Weixin extends AbstractOauth
{
    public function __construct($appID, $appSecret, $redirectUri)
    {
        parent::__construct($appID, $appSecret, $redirectUri, 'https://open.weixin.qq.com/connect/oauth2/authorize');
    }

    public function requestLoginUri()
    {
        $param['appid'] = $this->appID;
        $param['redirect_uri'] = $this->redirectUri;
        $param['response_type'] = 'code';
        $param['scope'] = 'snsapi_base';
        ksort($param);
        $uri = $this->loginUri . '?' . http_build_query($param) . '#wechat_redirect';
        $header[] = ['User-Agent:micromessage'];
        echo Curl::get($uri, [], false, $header);
        // header('Location:' . $uri);
    }

    public function getAccessToken($authorization_code, $refresh_token = '')
    {
        $param['appid'] = $this->appID;
        $param['secret'] = $this->appSecret;
        if ($refresh_token) {
            $param['grant_type'] = 'refresh_token';
            $param['refresh_token'] = $refresh_token;
        } else {
            $param['grant_type'] = 'authorization_code';
            $param['code'] = $authorization_code;
        }
        ksort($param);
        $result = Curl::post('https://api.weixin.qq.com/sns/oauth2/access_token', $param);
        $result = json_decode($result, true);
        if (!empty($result['access_token'])) {
            return $result['access_token'];
        }
    }

    public function  getUserInfo($access_token, $openid = '')
    {
        $param['access_token'] = $access_token;
        $param['lang'] = 'zh_CN';
        $param['openid'] = $openid;
        $result = Curl::get('https://api.weixin.qq.com/sns/userinfo', $param);
        return $result ? json_decode($result, true) : [];
    }

    /**
     * 获取access_token,正常响应返回结构如下
    {
    "access_token":"ACCESS_TOKEN",
    "expires_in":7200,
    "refresh_token":"REFRESH_TOKEN",
    "openid":"OPENID",
    "scope":"SCOPE" 
    }
     */
    public function getAccessTokenArr($authorization_code, $refresh_token = '')
    {
        $param['appid'] = $this->appID;
        $param['secret'] = $this->appSecret;
        if ($refresh_token) {
            $param['grant_type'] = 'refresh_token';
            $param['refresh_token'] = $refresh_token;
        } else {
            $param['grant_type'] = 'authorization_code';
            $param['code'] = $authorization_code;
        }
        ksort($param);
        $result = Curl::post('https://api.weixin.qq.com/sns/oauth2/access_token', $param);
        return $result ? json_decode($result, true) : [];
    }
}
