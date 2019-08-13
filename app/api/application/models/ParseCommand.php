<?php
/**
 * 解析命令中变量
 */

class ParseCommandModel
{
    //解析传入得字符串变量
    public static function parseStr(string $str, array $params = [])
    {
        $check = '/{\$.*?}/';
        preg_match_all($check, $str, $res);
        if ($res[0]) {
            $replace  = '/(^{\$)(.*?)(})/i';
            $o        = '$2';
            $patterns = $replacements = [];
            foreach ($res[0] as $key => $value) {
                $varName = preg_replace($replace, $o, $value);
                //查找变量值
                $varInfo = DAO\VarInfoModel::findOneByVarName($varName, $params['project_id']);
                if (empty($varInfo)) {
                    $varInfo = DAO\VarInfoModel::findOneByVarName($varName, null);
                    if (empty($varInfo)) {
                        return ['status' => false, 'error' => "未查询到{$varName}变量", 'code' => 400];
                    }
                    //throw new \Exception("无此变量：{$varName}", 1);
                }
                $patterns[$key] = '/{\$' . $varName . '}/'; //替换变量
                if ($varInfo['type'] == 200) {
                    $replacements[$key] = $varInfo['var_value'];
                } elseif ($varInfo['type'] == 100) {
                    $method             = json_decode($varInfo['var_value'], true);
                    $modelName          = 'DAO' . '\\' . $method['model'];
                    $model              = new $modelName;
                    try {
                        $varValue           = call_user_func_array([$model, $method['action']], [$params]);
                        $replacements[$key] = $varValue;
                    } catch (Exception $e) {
                        return ['status' => false, 'error' => $e->getMessage(), 'code' => 400];
                    }
                }
            }
            $command = preg_replace($patterns, $replacements, $str);
            return ['status' => true, 'data' => $command, 'code' => 200];
        }
        return ['status' => false, 'error' => '未查询到变量', 'code' => 300];
    }

    public static function pareseTxt()
    {
    }
}
