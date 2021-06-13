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
        $uri = $this->loginUri . '?' . http_build_query($param);
        header('Location:' . $uri);
    }

    public function getAccessToken($code, $refresh_token = '')
    {
        $param = $this->buildRequestParam('alipay.system.oauth.token', $code);
        $result = Curl::post('https://openapi.alipay.com/gateway.do?' . http_build_query($param));

        var_dump($result);


        $result = json_decode($result, true);
        if (!empty($result['alipay_system_oauth_token_response']['access_token'])) {
            return $result['alipay_system_oauth_token_response']['access_token'];
        }
    }

    public function  getUserInfo($auth_token)
    {
        $param = $this->buildRequestParam('alipay.user.info.share', $auth_token);
        $result = Curl::post('https://openapi.alipay.com/gateway.do', $param);
        return $result ? json_decode($result, true) : [];
    }

    private function signData($param)
    {
        unset($param['sign']);
        ksort($param);
        $private_key = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->appSecret, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        $res = openssl_get_privatekey($private_key);
        if ($res) {
            openssl_sign(http_build_query($param), $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            exit("私钥格式有误");
        }
        return base64_encode($sign);
    }

    private function buildRequestParam($method, $code, $refresh_token = '')
    {
        if ($refresh_token) {
            $param['refresh_token'] = $refresh_token;
            $param['grant_type'] = 'refresh_token';
        } else {
            $param['code'] = $code;
            $param['grant_type'] = 'authorization_code';
        }
        $param['app_id'] = $this->appID;
        $param['method'] = $method;
        $param['charset'] = 'utf-8';
        $param['sign_type'] = 'RSA2';
        $param['timestamp'] = date('Y-m-d H:i:s');
        $param['version'] = '1.0';

        var_dump('待签字符串', http_build_query($param));

        $param['sign'] = $this->signData($param);

        var_dump('签名后的sign', $param['sign']);

        return $param;
    }
}
