<?php

namespace Phpdie\Quicklogin;

use Phpdie\Quicklogin\AbstractOauth;
use Phpdie\Quicklogin\Curl;

class Alipay extends AbstractOauth
{
    private $postCharset = 'UTF-8';
    private $fileCharset = "UTF-8";
    public function __construct($appID, $appSecret, $redirectUri)
    {
        parent::__construct($appID, $appSecret, $redirectUri, 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm');
    }

    public function requestLoginUri()
    {
        $param['app_id'] = $this->appID;
        $param['redirect_uri'] = $this->redirectUri;
        $param['charset'] = $this->postCharset;
        $param['scope'] = 'auth_base'; //获取用户信息场景暂支持 auth_user 和 auth_base 两个值
        $uri = $this->loginUri . '?' . http_build_query($param);
        header('Location:' . $uri);
    }

    public function getAccessToken($code, $refresh_token = '')
    {
        $param = $this->buildRequestParam('alipay.system.oauth.token', $code);
        $param = array_map('urlencode', $param);
        $result = Curl::post('https://openapi.alipay.com/gateway.do', $param, false);
        $result = json_decode($result, true);
        if (!empty($result['alipay_system_oauth_token_response']['access_token'])) {
            return $result['alipay_system_oauth_token_response']['access_token'];
        }
    }

    public function  getUserInfo($auth_token)
    {
        $param = $this->buildRequestParam('alipay.user.info.share', $auth_token);
        $param = array_map('urlencode', $param);
        $result = Curl::post('https://openapi.alipay.com/gateway.do', $param);
        return $result ? json_decode($result, true) : [];
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
        $param['sign'] = $this->generateSign($param, $signType = "RSA2");
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
                // 转换成目标字符集
                $v = $this->characet($v, $this->postCharset);
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

    private function characet($data, $targetCharset)
    {
        if (!empty($data)) {
            $fileType = $this->fileCharset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
            }
        }
        return $data;
    }

    private function generateSign($params, $signType = "RSA")
    {
        $params = array_filter($params);
        $params['sign_type'] = $signType;
        return $this->sign($this->getSignContent($params), $signType);
    }

    protected function sign($data, $signType = "RSA")
    {
        $priKey = $this->appSecret;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }
}
