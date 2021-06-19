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
        return '';
    }

    public function  getUserInfo($accessToken)
    {
        $param = $this->buildRequestParam('alipay.user.info.share', '', $accessToken);
        //这里用get请求不需要花太多精力处理乱码问题,用post返回的数据中文乱码
        //可以考虑用echo mb_convert_encoding($result, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');解决中文乱码的问题
        $result = Curl::get('https://openapi.alipay.com/gateway.do', $param);
        $result = json_decode($result, true);
        if (!empty($result['alipay_user_info_share_response'])) {
            return $result['alipay_user_info_share_response'];
        }
        return [];
    }

    private function buildRequestParam($method, $code, $auth_token = '', $refresh_token = '')
    {
        if ($code) {
            $param['code'] = $code;
            $param['grant_type'] = 'authorization_code';
        } else if ($auth_token) {
            $param['auth_token'] = $auth_token;
            $param['biz_content'] = '{}'; //说明上讲这个是必填项,随意搞个json
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
        $param['sign'] = $this->sign($param); //并没有urlencode也成功了
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
