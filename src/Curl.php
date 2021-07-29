<?php

namespace Phpdie\Quicklogin;

class Curl
{
    private static $curl;

    private static function init()
    {
        self::$curl = curl_init();
    }

    public static function get($url, $data = array(), $ssl = false, $headers = array())
    {
        self::init();
        if (count($data) && strpos($url, '?') === false) {
            $url .= '?' . http_build_query($data);
        }

        if ($ssl) {
            curl_setopt(self::$curl, CURLOPT_SSL_VERIFYPEER, true);
        } else {
            curl_setopt(self::$curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        if ($headers) {
            curl_setopt(self::$curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt(self::$curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(self::$curl, CURLOPT_FOLLOWLOCATION, true); //解决301重定向获取不到
        curl_setopt(self::$curl, CURLOPT_URL, $url);
        curl_setopt(self::$curl, CURLOPT_REFERER, $url);
        $response = curl_exec(self::$curl);
        curl_close(self::$curl);
        return $response;
    }

    public static function post($url, $data = array(), $ssl = false, $headers = array())
    {
        self::init();
        if ($ssl) {
            curl_setopt(self::$curl, CURLOPT_SSL_VERIFYPEER, true);
        } else {
            curl_setopt(self::$curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        if ($headers) {
            curl_setopt(self::$curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt(self::$curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(self::$curl, CURLOPT_POST, true);
        curl_setopt(self::$curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt(self::$curl, CURLOPT_FOLLOWLOCATION, true); //解决301重定向获取不到
        curl_setopt(self::$curl, CURLOPT_URL, $url);
        $response = curl_exec(self::$curl);
        curl_close(self::$curl);
        return $response;
    }
}
