<?php

/**
 * Created by PhpStorm.
 * User: Ren
 * Date: 2017/2/22 0022
 * Time: 上午 11:50
 */
class WXLogin{
    private $appId;             //appId
    private $secret;            //secret
    private $code;              //code

    private $redirectUri;      //回调URL
    private $getCodeUrl;         //获取code的链接
    private $getAccessTokenUrl;  //获取access_token的链接
    private $getUserInfoUrl;        //获取用户信息的链接

    private $accessToken;      //access_token
    private $openId;            //openId
    private $wxUserInfo;        //微信用户信息数组


    public function __construct($redirect_uri = '', $appid = 'wx196c2f6fac038737', $secret = 'xxxx'){
        if (!isset($_SESSION)) session_start();
        if (!isset($_SESSION['wx'])){
            $this->appId = $appid;
            $this->secret = $secret;
            $this->redirectUri = $redirect_uri;
            $this->getCodeUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->appId.'&redirect_uri='.$this->redirectUri.'&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect';
            $this->action();
        }
    }

    public function __set($key, $value){
        $this->$key = $value;
    }
    public function __get($key){
        return $this->$key;
    }

    public function action(){
        //请求code
        if (!isset($_GET['code'])){
            $this->requestCode();
        }
        //如果返回code，则接收code,并通过code获取access_token与openid
        if (isset($_GET['code'])) {
            $this->getCode();
            if (!is_null($this->code)) $this->getAccessToken();
            if (!is_null($this->accessToken) && !is_null($this->openId)) $this->getUserInfo();
            if (!is_null($this->wxUserInfo)) $this->saveUserInfo();
        }
    }

    //发送获取code的链接
    private function requestCode(){
        header('Location:'.$this->getCodeUrl);
    }
    //获取code
    private function getCode(){
        if (isset($_GET['code'])){
            $this->code = $_GET['code'];
        }
    }

    //获取取access_token与openid
    /*
        {
            "access_token":"ACCESS_TOKEN",
            "expires_in":7200,
            "refresh_token":"REFRESH_TOKEN",
            "openid":"OPENID",
            "scope":"SCOPE"
         }
    */

    private function getAccessToken(){
        //组装获取access_token数据的url
        $this->getAccessTokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->appId.'&secret='.$this->secret.'&code='.$this->code.'&grant_type=authorization_code';
        //curl读取json数据
        $res = $this->curl($this->getAccessTokenUrl);
        //解析json数据为数组
        $response = json_decode($res, true);
        //获取access_token
        $this->accessToken = $response['access_token'];
        //获取openid
        $this->openId = $response['openid'];
    }
    //拉取用户信息
    /*
    {
        "openid":" OPENID",
        "nickname": NICKNAME,
        "sex":"1",
        "province":"PROVINCE"
        "city":"CITY",
        "country":"COUNTRY",
        "headimgurl":    "http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46",
        "privilege":[
                        "PRIVILEGE1"
                        "PRIVILEGE2"
                    ],
        "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
        }
    */

    private function getUserInfo(){
        //组装拉取用户信息的URL
        $this->getUserInfoUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$this->accessToken.'&openid='.$this->openId.'&lang=zh_CN';
        //curl读取json数据
        $res = $this->curl($this->getUserInfoUrl);
        //解析json数据，存入用户信息数组
        $this->wxUserInfo = json_decode($res, true);
    }

    //将拉取的用户信息存入session
    private function saveUserInfo(){
        //存入session

        $_SESSION['wx'] = $this->wxUserInfo;
    }

    //curl
    private function curl($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

}