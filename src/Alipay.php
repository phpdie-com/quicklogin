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
        $param['scope'] = 'auth_user,auth_base'; //获取用户信息场景暂支持 auth_user 和 auth_base 两个值
        $uri = $this->loginUri . '?' . http_build_query($param);
        header('Location:' . $uri);
    }

    public function getAccessToken($code, $refresh_token = '')
    {
        $param = $this->buildRequestParam('alipay.system.oauth.token', $code);
        $result = Curl::post('https://openapi.alipay.com/gateway.do', $param);
        $result = json_decode($result, true);
        if (!empty($result['alipay_system_oauth_token_response']['access_token'])) {
            return $result['alipay_system_oauth_token_response']['access_token'];
        }
    }

    public function  getUserInfo($accessToken)
    {
        $param = $this->buildRequestParam('alipay.user.info.share', '', $accessToken);
        $result = Curl::post('https://openapi.alipay.com/gateway.do', $param);
        $result = json_decode($result, true);
        if (!empty($result['alipay_user_info_share_response'])) {
            return $result['alipay_user_info_share_response'];
        }
    }

    private function buildRequestParam($method, $code, $auth_token = '', $refresh_token = '')
    {
        if ($code) {
            $param['code'] = $code;
            $param['grant_type'] = 'authorization_code';
        } else if ($auth_token) {
            $param['auth_token'] = $auth_token;
            $param['biz_content'] = '{"aa":"bb"}'; //说明上讲这个是必填项,随意搞个json
        } else if ($refresh_token) {
            $param['refresh_token'] = $refresh_token;
            $param['grant_type'] = 'refresh_token';
        }
        $param['app_id'] = $this->appID;
        $param['method'] = $method;
        $param['charset'] = 'utf-8';
        $param['sign_type'] = 'RSA2';
        $param['timestamp'] = date('Y-m-d H:i:s');
        $param['version'] = '1.0';
        $param['sign'] = $this->sign($param);

        $param = $this->formatData($param);
        return $param;
    }

    private function formatData($param)
    {
        //return mb_convert_encoding($param, 'UTF-8');
        foreach ($param  as $key => &$value) {
            $value = mb_convert_encoding($value, 'UTF-8');
        }
        return $param;
    }

    private function getSignContent($params)
    {
        ksort($params);
        unset($params['sign']);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if ("@" != substr($v, 0, 1)) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset($k, $v);
        return $stringToBeSigned;
    }

    private function sign($param)
    {
        $data = $this->getSignContent($param);
        $priKey = $this->appSecret;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        $sign = base64_encode($sign);
        return $sign;
    }
}
