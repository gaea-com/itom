<?php
/**
 * 工具类
 */
namespace Tools;

class FuncModel
{
    //检查密码格式 6-16位密码，必须包含数字和字母，特殊字符只能包含-_
    public static function isPassword($passwd)
    {
        return preg_match('/(?!^[0-9]+$)(?!^[A-z]+$)(?!^[^A-z0-9]+$)^.{6,16}$/', $passwd) ? true : false;
    }
    //随机生成短信验证码
    public static function getSmsCode($num = 4)
    {
        $code = '';
        $codes = '1234567890abcdefghijklmnopqrstuvwxyz';
        for ($i = 0; $i < $num; $i++) {
            $code .= substr($codes, mt_rand(1, strlen($codes) - 1), 1);
        }
        return $code;
    }

    //上传文件
    public static function uploadFile($name, $is_rename = null)
    {
        $return = ['status' => false, 'error' => ''];
        $file = $_FILES[$name] ?? null;
        if (!$file) {
            $return['error'] = '无文件被上传';
            return $return;
        }
        if ($file['error'] > 0) {
            switch ($file['error']['error']) {
            case 1:
                $return['error'] = '上传文件过大';
                break;
            case 2:
                $return['error'] = '上传文件过大';
                break;
            case 3:
                $return['error'] = '文件上传丢失';
                break;
            case 4:
                $return['error'] = '无文件被上传';
                break;
            case 6:
                $return['error'] = '没有临时文件夹';
                break;
            case 7:
                $return['error'] = '上传文件存储失败';
                break;
            }
            return $return;
        }
        if (is_uploaded_file($file['tmp_name'])) {
            $uploaded_file = $file['tmp_name'];
            $base_path = $_SERVER['DOCUMENT_ROOT'] . "/upload";
            // $base_path="/data/itom/upload/";
            if (!file_exists($base_path)) {
                mkdir($base_path);
            }
            $file_true_name = $file['name'];
            $ext = pathinfo($file_true_name, PATHINFO_EXTENSION);
            if ($is_rename) {
                $move_to_file = $base_path . "/" . $file['name'];
            } else {
                $move_to_file = $base_path . '/' . md5(time() . rand(1, 1000) . $file_true_name) . '.' . $ext;
            }
            if (move_uploaded_file($uploaded_file, $move_to_file)) {
                $return['status'] = true;
                $return['file_url'] = $move_to_file;
                $return['file_name'] = $file_true_name;
            } else {
                $return['error'] = "上传失败";
            }
        } else {
            $return['error'] = "上传失败";
        }
        clearstatcache();
        return $return;
    }

    /**
     * curl访问.
     *
     * @param  string $url      url
     * @param  array  $postData post数据
     * @param  int    $timeout  超时时间
     * @return false | array
     */
    public static function ycurl($url, $postData = null, $timeout = 90, $type = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($postData) {
            $postData = http_build_query($postData);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        if ($type) {
            $userAgentArr = [
                'IOS' => 'Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A403 Safari/8536.25',
                'android' => 'Mozilla/5.0 (Linux; U; Android 4.0.3; ja-jp; Sony Tablet S Build/TISU0R0110) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Safari/534.30',
                'web' => 'Mozilla/5.0 (Android; Linux armv7l; rv:9.0) Gecko/20111216 Firefox/9.0 Fennec/9.0',
            ];
            $userAgent = $userAgentArr[$type];
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        }

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $file_contents = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($file_contents) {
            $data = json_decode($file_contents, true);
            if (is_array($data)) {
                $data['http'] = $httpCode;
            } else {
                $data = [
                    'http' => $httpCode,
                    'data' => $file_contents,
                ];
            }

            return $data;
        //正则获取括号中的内容
            // if(preg_match('/(?:\()(.*)(?:\))/i' , $file_contents , $match) !== 0)
            // {
            //     return json_decode($match[1] , true);
            // }
        } else {
            return ['status' => $httpCode];
        }
        return false;
    }

    /**
     * curl访问.
     *
     * @param  string $url     url
     * @param  string $type    请求类型
     * @param  array  $params  请求数据
     * @param  int    $timeout 超时时间
     * @param  int    $headers header参数
     * @return false | array
     */
    public static function curl($URL, $type, $params = null, $headers = null, $timeout = 300)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/json'));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        switch ($type) {
        case "GET":curl_setopt($ch, CURLOPT_HTTPGET, true);
            break;
        case "POST":curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            break;
        case "PUT":curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            break;
        case "DELETE":curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            break;
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $file_contents = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // 获得响应结果里的：头大小
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        // 根据头大小去获取头信息内容
        $header = substr($file_contents, 0, $headerSize);
        $file_contents = substr($file_contents, $headerSize);
        curl_close($ch);

        if ($file_contents) {
            $data = json_decode($file_contents, true);
            if (is_array($data)) {
                $data['http'] = $httpCode;
                $data['header'] = $header;
            } else {
                $data = [
                    'http' => $httpCode,
                    'data' => $file_contents,
                    'header' => $header,
                ];
            }

            return $data;
        } else {
            return ['status' => $httpCode];
        }
        return false;
    }

    /**
     * 获取header头信息
     *
     * @return array|false|null
     */
    public static function getHeaders()
    {
        $headers = null;
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } elseif (function_exists('http_get_request_headers')) {
            $headers = http_get_request_headers();
        } else {
            foreach ($_SERVER as $name => $value) {
                if (strncmp($name, 'HTTP_', 5) === 0) {
                    $name           = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$name] = $value;
                }
            }
            return $headers;
        }
        foreach ($headers as $name => $value) {
            $result[$name] = $value;
        }
        return $result;
    }
}
