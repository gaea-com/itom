<?php

/**
 * Json Web Token 相关操作类
 */
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class JsonWebTokenModel
{
    const SECRET        = 'gaea8cabk131b22349t913a8ecf8gaea'; //jwt 密匙
    const EXP           = 86400; //jwt过期时间为24小时
    const JTI_REDIS_KEY = 'jwt_jti_'; //每个用户jwt白名单key前缀
    //验证Json Web Token
    public static function validateJWT()
    {
        //是否提交了Token
        $token = self::getJWT();
        if ($token) {
            try {
                //验证签名
                $signer = new Sha256();
                if (!$token->verify($signer, self::SECRET)) {
                    header('WWW-Authenticate: Bearer realm=api');
                    header('HTTP/1.0 401 Unauthorized');
                    exit;
                }
                $id  = $token->getClaim('jti');
                $exp = $token->getClaim('exp');
                $nbf = $token->getClaim('nbf');
                $uid = $token->getClaim('uid');
                $at  = time();
                //验证有效期
                if ($at < $nbf || $at > $exp) {
                    header('WWW-Authenticate: Bearer realm=api');
                    header('HTTP/1.0 401 Unauthorized');
                    exit;
                }
                //验证白名单 TODO
                $model = new Tools\RedisModel;
                $model->redis->select(1);
                $jti = $model->redis->get(self::JTI_REDIS_KEY . $uid);
                $model->redis->select(0);
                if (!$jti) {
                    header('WWW-Authenticate: Bearer realm=api');
                    header('HTTP/1.0 401 Unauthorized');
                    exit;
                }
                if ($id != $jti) {
                    header('WWW-Authenticate: Bearer realm=api');
                    header('HTTP/1.0 401 Unauthorized');
                    exit;
                }
                return $token;
            } catch (\Exception $e) {
                //记录日志 TODO
                //允许跨域
                header("Access-Control-Allow-Origin: *");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE");
                header("Access-Control-Max-Age: 3600");
                header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization, X-CSRF-TOKEN");
                header('WWW-Authenticate: Bearer realm=api');
                header('HTTP/1.0 401 Unauthorized');
                //echo json_encode(['status' => 400, 'error' => 100015, 'errorMsg' => $e->getMessage()]);
                exit;
            }
        }

        header('WWW-Authenticate: Bearer realm=api');
        header('HTTP/1.0 401 Unauthorized');
        exit;
    }

    //生成Json Web Token
    public static function getAccessToken($uid, $nickname, $type = false)
    {
        $exp = time() + self::EXP;
        $jti = md5($uid . $nickname . $exp);
        $jwt = [
            'header'  => ['typ' => 'JWT', 'alg' => 'HS256'],
            'payload' => [
                "exp"      => $exp, // 过期时间戳 1小时
                "uid"      => $uid, //user id
                "acc"      => $nickname, //用户邮件头
                "iss"      => 'GAEA.com', //固定 发行者
                "sub"      => 'itom', //固定 主题
                "nbf"      => microtime(), //允许运行的最早时间戳
                "iat"      => microtime(), //颁发jwt的时间
                "rol"      => $type,
                "jti"      => $jti, //此jwt的唯一识别码，用于白名单
            ],
            'secret'  => self::SECRET,
        ];
        $signer = new Sha256();
        $token  = (new Builder())->setIssuer($jwt['payload']['iss']) // Configures the issuer (iss claim)
            ->setAudience($jwt['payload']['iss']) // Configures the audience (aud claim)
            ->setId($jwt['payload']['jti'], true) // Configures the id (jti claim), replicating as a header item
            ->setIssuedAt($jwt['payload']['iat']) // Configures the time that the token was issue (iat claim)
            ->setNotBefore($jwt['payload']['nbf']) // Configures the time that the token can be used (nbf claim)
            ->setExpiration($jwt['payload']['exp']) // Configures the expiration time of the token (nbf claim)
            ->set('uid', $jwt['payload']['uid']) // Configures a new claim, called "uid"
            ->set('acc', $jwt['payload']['acc'])
            ->set('rol', $jwt['payload']['rol'])
            ->sign($signer, $jwt['secret']) // creates a signature using "testing" as key
            ->getToken(); // Retrieves the generated token
        $model = new Tools\RedisModel;
        $model->redis->select(1);
        $key = self::JTI_REDIS_KEY . $uid;
        $res = $model->redis->set($key, $jti, self::EXP);
        $model->redis->select(0);
        if ($res) {
            return (string) $token;
        } else {
            throw new \Exception("Redis save key fail", 99999);
        }
    }

    //清除JWT
    public static function delJwt($uid)
    {
        $redis = new Tools\RedisModel();
        $redis->redis->select(0);
        $delWs = $redis->redis->hdel('ws_user_fd', $uid);
        $redis->redis->select(1);
        $key = self::JTI_REDIS_KEY.$uid;
        $delJwt = $redis->redis->del($key);
        if ($delWs && $delJwt) {
            return true;
        }
        return false;
    }

    //获取JWT
    public static function getJWT()
    {
        $authHeader = Tools\FuncModel::getHeaders();
        if (isset($authHeader['Authorization']) && preg_match('/^Bearer\s+(.*?)$/', $authHeader['Authorization'], $matches)) {
            if (!empty($matches[1])) {
                try {
                    $token = (new Parser())->parse((string) $matches[1]);
                    if (is_object($token)) {
                        return $token;
                    }
                } catch (\Exception $e) {
                    //记录ip TODO
                    //echo $e->getMessage();
                }
            }
        }
        return null;
    }

}
