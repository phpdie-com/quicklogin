<?php

namespace Phpdie\Quicklogin;

use Phpdie\Quicklogin\AbstractOauth;
use Phpdie\Quicklogin\Curl;

class Alipay extends AbstractOauth
{
    public function __construct($appID, $appSecret, $redirectUri)
    {
        parent::__construct($appID, $appSecret, $redirectUri, 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm');
    }

    public function requestLoginUri()
    {
        $param['app_id'] = $this->appID;
        $param['redirect_uri'] = $this->redirectUri;
        $param['scope'] = 'auth_user'; //获取用户信息场景暂支持 auth_user 和 auth_base 两个值
        $param['state'] = rand(1, 999); //不是必须的
        $uri = $this->loginUri . '?' . http_build_query($param);
        header('Location:' . $uri);
    }

    public function getAccessToken($code, $refresh_token = '')
    {
        if ($refresh_token) {
            $param['refresh_token'] = $refresh_token;
            $param['grant_type'] = 'refresh_token';
        } else {
            $param['code'] = $code;
            $param['grant_type'] = 'authorization_code';
        }
        $result = Curl::post('https://openapi.alipay.com/gateway.do', $param);
        $result = json_decode($result, true);
        if (!empty($result['alipay_system_oauth_token_response']['access_token'])) {
            return $result['alipay_system_oauth_token_response']['access_token'];
        }
    }

    public function  getUserInfo($token)
    {
        $param['auth_token'] = $token;
        $result = Curl::post('https://openapi.alipay.com/gateway.do', $param);
        return $result ? json_decode($result, true) : [];
    }
}
