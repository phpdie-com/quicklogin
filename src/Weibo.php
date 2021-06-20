<?php

namespace Phpdie\Quicklogin;

use Phpdie\Quicklogin\AbstractOauth;

class Weibo extends AbstractOauth
{
    public function __construct($appID, $appSecret, $redirectUri)
    {
        parent::__construct($appID, $appSecret, $redirectUri, 'https://api.weibo.com/oauth2/authorize');
    }

    public function requestLoginUri()
    {
        //https: //api.weibo.com/oauth2/authorize?client_id=YOUR_CLIENT_ID&response_type=code&redirect_uri=YOUR_REGISTERED_REDIRECT_URI
        $param['client_id'] = $this->appID;
        $param['redirect_uri'] = $this->redirectUri;
        $param['response_type'] = 'code';
        ksort($param);
        $uri = $this->loginUri . '?' . http_build_query($param);
        header('Location:' . $uri);
    }

    public function getAccessToken($authorization_code, $refresh_token = '')
    {
        //https://api.weibo.com/oauth2/access_token?client_id=YOUR_CLIENT_ID&client_secret=YOUR_CLIENT_SECRET&grant_type=authorization_code&redirect_uri=YOUR_REGISTERED_REDIRECT_URI&code=CODE
        $param['client_id'] = $this->appID;
        $param['client_secret'] = $this->appSecret;
        $param['redirect_uri'] = $this->redirectUri;
        $param['grant_type'] = 'authorization_code';
        $param['code'] = $authorization_code;
        ksort($param);
        $result = Curl::post('https://api.weibo.com/oauth2/access_token', $param);
        $result = json_decode($result, true);
        if (!empty($result['access_token'])) {
            return $result['access_token'];
        }
    }

    public function  getUserInfo($access_token, $openid = '')
    {
        $param['access_token'] = $access_token;
        $param['uid'] = $openid ? $openid : $this->getOpenId($access_token);
        $result = Curl::get('https://api.weibo.com/2/users/show.json', $param);
        return $result ? json_decode($result, true) : [];
    }

    public function getOpenId($access_token)
    {
        $param['access_token'] = $access_token;
        $result = Curl::get('https://api.weibo.com/2/account/get_uid.json', $param);
        $result = json_decode($result, true);
        if (!empty($result['uid'])) {
            return $result['uid'];
        }
    }

    public function getAccessTokenArr($authorization_code)
    {
        $param['client_id'] = $this->appID;
        $param['client_secret'] = $this->appSecret;
        $param['redirect_uri'] = $this->redirectUri;
        $param['grant_type'] = 'authorization_code';
        $param['code'] = $authorization_code;
        ksort($param);
        $result = Curl::post('https://api.weibo.com/oauth2/access_token', $param);
        return $result ? json_decode($result, true) : [];
    }
}
