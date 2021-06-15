<?php

namespace Phpdie\Quicklogin;

use Phpdie\Quicklogin\AbstractOauth;
use Phpdie\Quicklogin\Curl;

class Github extends AbstractOauth
{
    public function __construct($appID, $appSecret, $redirectUri)
    {
        parent::__construct($appID, $appSecret, $redirectUri, 'https://github.com/login/oauth/authorize');
    }

    public function requestLoginUri()
    {
        $param['client_id'] = $this->appID;
        $param['redirect_uri'] = $this->redirectUri;
        $param['scope'] = 'user';
        $param['state'] = rand(1, 999);
        $uri = $this->loginUri . '?' . http_build_query($param);
        header('Location:' . $uri);
    }

    public function getAccessToken($code, $refresh_token = '')
    {
        $param['client_id'] = $this->appID;
        $param['client_secret'] = $this->appSecret;
        if ($refresh_token) {
            $param['refresh_token'] = $refresh_token;
            $param['grant_type'] = 'refresh_token';
        } else {
            $param['code'] = $code;
        }
        $result = Curl::post('https://github.com/login/oauth/access_token', $param);
        $result = parse_str($result, $temp);
        $result = $result ? $result : $temp;
        if (!empty($result['access_token'])) {
            return $result['access_token'];
        }
    }

    public function  getUserInfo($token)
    {
        $header['Authorization'] ='token '.$token;
        $header['Accept'] = 'application/json';
        $header['User-Agent'] = 'Windows';
        $result = Curl::get('https://api.github.com/user', [], false, $header);
        $result = parse_str($result, $temp);
        $result = $result ? $result : $temp;
        return $result ? $result : [];
    }
}
