<?php
namespace zky;
class WxScanLogin
{
    protected $appid;
    protected $appsecret;
    //构造函数，获取Access Token
    public function __construct($appid = NULL, $appsecret = NULL)
    {
        if($appid && $appsecret){
            $this->appid = $appid;
            $this->appsecret = $appsecret;
        }

        //扫码登录不需要该Access Token, 语义理解需要
        //1. 本地写入
        $res = file_get_contents('access_token.json');
        $result = json_decode($res, true);
        $this->expires_time = $result["expires_time"];
        $this->access_token = $result["access_token"];

        if (time() > ($this->expires_time + 3600)){
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
            $res = $this->httpRequest($url);
            $result = json_decode($res, true);
            $this->access_token = $result["access_token"];
            $this->expires_time = time();
            //file_put_contents('access_token.json', '{"access_token": "'.$this->access_token.'", "expires_time": '.$this->expires_time.'}');
        }
    }

    //生成扫码登录的URL
    public function qrconnect($redirect_url, $scope, $state = NULL)
    {
        $url = "https://open.weixin.qq.com/connect/qrconnect?appid=".$this->appid."&redirect_uri=".urlencode($redirect_url)."&response_type=code&scope=".$scope."&state=".$state."#wechat_redirect";
        return $url;
    }

    //生成OAuth2的Access Token
    public function oauth2_access_token($code)
    {
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->appsecret."&code=".$code."&grant_type=authorization_code";
        $res = $this->HttpsRequest($url);

        return $res;
    }

    //获取用户基本信息（OAuth2 授权的 Access Token 获取 未关注用户，Access Token为临时获取）
    public function oauth2_get_user_info($access_token, $openid)
    {
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $res = $this->httpRequest($url);
        return json_decode($res, true);
    }
    public function httpRequest($url,$post='',$method='GET',$limit=0,$returnHeader=FALSE,$cookie='',$bysocket=FALSE,$ip='',$timeout=15,$block=TRUE) {
        $return = '';
        $matches = parse_url($url);
        !isset($matches['host']) && $matches['host'] = '';
        !isset($matches['path']) && $matches['path'] = '';
        !isset($matches['query']) && $matches['query'] = '';
        !isset($matches['port']) && $matches['port'] = '';
        $host = $matches['host'];
        $path = $matches['path'] ? $matches['path'].($matches['query'] ? '?'.$matches['query'] : '') : '/';
        $port = !empty($matches['port']) ? $matches['port'] : 80;
        if(strtolower($method) == 'post') {
            $post = (is_array($post) and !empty($post)) ? http_build_query($post) : $post;
            $out = "POST $path HTTP/1.0\r\n";
            $out .= "Accept: */*\r\n";
            //$out .= "Referer: $boardurl\r\n";
            $out .= "Accept-Language: zh-cn\r\n";
            $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= 'Content-Length: '.strlen($post)."\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cache-Control: no-cache\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
            $out .= $post;
        } else {
            $out = "GET $path HTTP/1.0\r\n";
            $out .= "Accept: */*\r\n";
            //$out .= "Referer: $boardurl\r\n";
            $out .= "Accept-Language: zh-cn\r\n";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
        }
        $fp = fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
        if(!$fp) return ''; else {
            $header = $content = '';
            stream_set_blocking($fp, $block);
            stream_set_timeout($fp, $timeout);
            fwrite($fp, $out);
            $status = stream_get_meta_data($fp);
            if(!$status['timed_out']) {//未超时
                while (!feof($fp)) {
                    $header .= $h = fgets($fp);
                    if($h && ($h == "\r\n" ||  $h == "\n")) break;
                }

                $stop = false;
                while(!feof($fp) && !$stop) {
                    $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                    $content .= $data;
                    if($limit) {
                        $limit -= strlen($data);
                        $stop = $limit <= 0;
                    }
                }
            }
         fclose($fp);
            return $returnHeader ? array($header,$content) : $content;
        }
     }
     public function HttpsRequest($url){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//关闭ssl证书的有效性检测
        curl_setopt($ch , CURLOPT_URL, $url);
        curl_setopt($ch , CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        if( curl_errno($ch) ){
            var_dump( curl_error($ch) );
            die();
        }
        curl_close( $ch );
        $arr = json_decode($res, true);
        return $arr;
     }
}
