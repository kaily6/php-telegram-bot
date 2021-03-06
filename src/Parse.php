<?php

namespace Longman\TelegramBot;


class Parse
{
    private static $app_id = '';
    private static $rest_key = '';

    public static function send($action, array $data = null)
    {
        $ch = curl_init();
        $curlConfig = array(
            CURLOPT_URL => 'https://api.parse.com/1/' . $action,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-Parse-Application-Id : '.self::$app_id,
                'X-Parse-REST-API-Key : '. self::$rest_key,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        );
        curl_setopt_array($ch, $curlConfig);

        $result = curl_exec($ch);
        curl_close($ch);

        return !empty($result) ? json_decode($result, true) : false;
    }

    public static function sendWithToken($action, array $data = null, $token, $is_put = false)
    {
        $ch = curl_init();
        $curlConfig = array(
            CURLOPT_URL => 'https://api.parse.com/1/' . $action,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-Parse-Application-Id : '.self::$app_id,
                'X-Parse-REST-API-Key : '. self::$rest_key,
                'X-Parse-Session-Token :'.$token,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        );
        if ($is_put) {
            $curlConfig[CURLOPT_CUSTOMREQUEST] ="PUT";
        }
        curl_setopt_array($ch, $curlConfig);

        $result = curl_exec($ch);
        curl_close($ch);

        return !empty($result) ? json_decode($result, true) : false;
    }

    public static function sendGet($action, array $data = null, $token)
    {
        $ch = curl_init();
        $curlConfig = array(
            CURLOPT_URL => 'https://api.parse.com/1/' . $action,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-Parse-Application-Id : '.self::$app_id,
                'X-Parse-REST-API-Key : '. self::$rest_key,
                'X-Parse-Session-Token :'.$token,
                'Content-Type: application/json',
            ]
        );
        curl_setopt_array($ch, $curlConfig);

        $result = curl_exec($ch);
        curl_close($ch);

        return !empty($result) ? json_decode($result, true) : false;
    }

}
