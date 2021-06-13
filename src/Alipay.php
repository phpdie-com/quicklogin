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
        $param['scope'] = 'auth_base'; //获取用户信息场景暂支持 auth_user 和 auth_base 两个值
        $param['state'] = rand(1, 999); //不是必须的
        $uri = $this->loginUri . '?' . http_build_query($param);
        header('Location:' . $uri);
    }

    public function getAccessToken($code, $refresh_token = '')
    {
        $param = $this->buildRequestParam('alipay.system.oauth.token', '', '', $code);
        $result = Curl::post('https://openapi.alipay.com/gateway.do', $param);
        $result = json_decode($result, true);
        var_dump($result);
        if (!empty($result['alipay_system_oauth_token_response']['access_token'])) {
            return $result['alipay_system_oauth_token_response']['access_token'];
        }
    }

    public function  getUserInfo($auth_token)
    {
        $param = $this->buildRequestParam('alipay.user.info.share', '', '', $auth_token);
        $result = Curl::post('https://openapi.alipay.com/gateway.do', $param);
        
        
        echo 'getUserInfo' . PHP_EOL;
       if($result){
           var_dump(json_decode($result, true));
       }
        
        return $result ? json_decode($result, true) : [];
    }

    private function signData($param)
    {
        unset($param['sign']);
        $param = array_filter($param);
        ksort($param);
        array_map('urlencode', $param);
        $priKey = $this->appSecret;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        openssl_sign(http_build_query($param), $sign, $res, OPENSSL_ALGO_SHA256);
        return base64_encode($sign);
    }

    //$code, $refresh_token, $auth_token三者只需要传一种,其他2种留空即可
    private function buildRequestParam($method, $code, $refresh_token, $auth_token)
    {
        if ($code) {
            $param['code'] = $code;
            $param['grant_type'] = 'authorization_code';
        } else if ($refresh_token) {
            $param['refresh_token'] = $refresh_token;
            $param['grant_type'] = 'refresh_token';
        } else if ($auth_token) {
            $param['code'] = $auth_token;
            $param['grant_type'] = 'authorization_code';
        }
        $param['method'] = $method;
        $param['charset'] = 'utf-8';
        $param['timestamp'] = date('Y-m-d H:i:s');
        $param['version'] = '1.0';
        $param['sign_type'] = 'RSA2';
        $param['app_id'] = $this->appID;
        $param['sign'] = $this->signData($param);
        return $param;
    }
}
