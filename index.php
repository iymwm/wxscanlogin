<?php
include_once("./vendor/autoload.php");
use zky\WxScanLogin;


$appid="";
$appsecret="";
$WxScanLogin=new Wxscanlogin($appid,$appsecret);
if (!isset($_GET["code"])){
    $redirect_uri='http://'.$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"];
    $url=$WxScanLogin->qrconnect($redirect_uri,'snsapi_login',rand(111,999));
    header("Location: {$url}");
}
$oauth2_info = $WxScanLogin->oauth2_access_token($_GET["code"]);
$userinfo = $WxScanLogin->oauth2_get_user_info($oauth2_info['access_token'], $oauth2_info['openid']);
var_dump($userinfo);

?>