<?php

namespace Phpdie\Quicklogin;

use Phpdie\Quicklogin\AbstractOauth;

class Qq extends AbstractOauth
{
    public function __construct($appID, $appSecret, $redirectUri)
    {
        parent::__construct($appID, $appSecret, $redirectUri, 'https://graph.qq.com/oauth2.0/authorize');
    }

    public function requestLoginUri()
    {
        $param['response_type'] = 'code';
        $param['client_id'] = $this->appID;
        $param['redirect_uri'] = $this->redirectUri;
        $param['state'] = rand(1, 999);
        $uri = $this->loginUri . '?' . http_build_query($param);
        header('Location:' . $uri);
    }

    public function getAccessToken($authorization_code, $refresh_token = '')
    {
        $param['client_id'] = $this->appID;
        $param['client_secret'] = $this->appSecret;
        if ($refresh_token) {
            $param['grant_type'] = 'refresh_token';
            $param['refresh_token'] = $refresh_token;
        } else {
            $param['grant_type'] = 'authorization_code';
            $param['code'] = $authorization_code;
            $param['redirect_uri'] = $this->redirectUri;
        }
        $result = Curl::get('https://graph.qq.com/oauth2.0/token', $param);

        $result = parse_str($result, $temp);
        $result = $result ? $result : $temp;
        if (!empty($result['access_token'])) {
            return $result['access_token'];
        }
    }

    public function  getUserInfo($access_token)
    {
        $param['access_token'] = $access_token;
        $param['oauth_consumer_key'] = $this->appID;
        $param['openid'] = $this->getOpenId($access_token);
        $result = Curl::get('https://graph.qq.com/user/get_user_info', $param);
        return $result ? json_decode($result, true) : [];
    }

    public function getOpenId($access_token)
    {
        $param['access_token'] = $access_token;
        $param['fmt'] = 'json';
        $result = Curl::get('https://graph.qq.com/oauth2.0/me', $param);
        $result = json_decode($result, true);
        if (!empty($result['openid'])) {
            return $result['openid'];
        }
    }
}
