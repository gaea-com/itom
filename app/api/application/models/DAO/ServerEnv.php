<?php

namespace DAO;

use ParseCommandModel;
use PDO;
use Yaf\Application;

class ServerEnvModel extends \MysqlBase
{
    // 根据server_id和image获得所有环境变量实例值
    public function findOneByServerImage(
        $server_id,
        $imageName,
        $num = null
    ) {
        //$image_name = $this->getImageName($imageName);
        $sql
                   = 'SELECT * FROM `server_env` WHERE server_id=:server_id AND image_name=:image_name ';
        $sql       .= empty($num) ? '' : ' AND container_num=:container_num ';
        $sql       .= ' ORDER BY id DESC ';
        $sth       = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $paramsArr = [
            ':server_id'  => $server_id,
            ':image_name' => $imageName,
        ];

        if (! empty($num)) {
            $paramsArr[':container_num'] = $num;
        }
        $sth->execute($paramsArr);
        $red = $sth->fetch(PDO::FETCH_ASSOC);
        return $red;
    }

    // 根据server_id和image获得所有环境变量实例值

    public function getImageName($image_name)
    {
        $arr = explode(':', $image_name);
        if (count($arr) >= 2) {
            array_pop($arr);
            $imageName = implode(':', $arr);
        } else {
            $imageName = $image_name;
        }

        return $imageName;
    }

    // 根据id获得所有环境变量实例值

    public function findOneByID($id)
    {
        $sql = 'SELECT * FROM `server_env` WHERE id=:id ';
        $sth = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $sth->execute([':id' => $id]);
        $red = $sth->fetch(PDO::FETCH_ASSOC);

        $model = new EnvTemplateModel();

        $data = [];
        if (! empty($red)) {
            $envData                    = $model->findOneInstance(
                $red['env_id']
            );
            $data                       = $envData;
            $data['container_name']     = $red['container_name'];
            $data['container_describe'] = $red['container_describe'];
        }

        return $data;
    }
    // 根据server_id获得环境变量实例值，供启动docker使用

    public function varReplace($data = null, $project_id, $server_id, $type)
    {
        if (empty($data)) {
            return [];
        }

        $model      = new ParseCommandModel;
        $returnData = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $status = $this->varReplace(
                    $value,
                    $project_id,
                    $server_id,
                    $type
                );
                if (! empty($status['status']) && $status['status'] == 400) {
                    return ['status' => 400, 'error' => $status['error']];
                }
                $returnData[$key] = $status;
            } else {
                $status = $model->parseStr(
                    $value,
                    [
                        'project_id'  => $project_id,
                        'instance_id' => $server_id,
                        'type'        => $type,
                    ]
                );
                if ($status['code'] == 400) {
                    return ['status' => 400, 'error' => $status['error']];
                } else {
                    if ($status['code'] == 300) {
                        $returnData[$key] = $value;
                    } else {
                        $returnData[$key] = $status['data'];
                    }
                }
            }
        }

        return $returnData;
    }

    /**
     * 获取待启动的容器的env参数 检查
     *
     * @param  $server_id
     * @param  $type
     * @param  $ip
     * @param  $composeInfo
     * @return array
     */
    public function findOneByServerInsNew($server_id, $type, $ip, $composeInfo)
    {
        $return = ['status' => false];
        $serModel   = new ProjectServerModel;
        $serData    = $serModel->findAllByParams(
            [
                'server_id' => $server_id,
                'type'      => $type,
                'status'    => ProjectServerModel::STATUS_SUCCESS,
            ]
        );
        $project_id = empty($serData) ? 1 : $serData[0]['project_id'];
        $compose_id = empty($serData) ? false : $serData[0]['compose_id'];

        // 验证容器编排模板变化的实例
        $dockerComposeChangeArr = $serModel->getAllComposeChangeServer();
        if (in_array($type.'_'.$server_id, $dockerComposeChangeArr)) {
            $return['error'] = $ip.':该实例下容器的环境变量或编排模板发生变化，请先同步实例镜像列表，然后重新配置1';
            return $return;
        }

        // 已经启动的docker
        $proModel       = new ProjectDockerModel;
        $openDockerData = $proModel->findAllOpen(null, $server_id, $type);
        $openDockerArr  = [];
        if (! empty($openDockerData)) {
            foreach ($openDockerData as $val) {
                $openDockerArr[] = $this->getImageName($val['image_name']).'_'
                    .$val['name'];
            }
        }

        if (empty($compose_id)) {
            $return['error'] = $ip.':server状态错误';
            return $return;
        }

        $imageTimesArr = $composeInfo['image_times'];
        $imageNameArr  = $composeInfo['image_name'];

        $envModel = new ServerEnvModel();
        $redDataArr = $envModel->findAllBySid($server_id);
        $redDataArrNew = [];
        foreach ($redDataArr as $v) {
            $md5= md5($v['image_name']);
            $redDataArrNew[$md5] = $v;
        }
        //整理排序
        $serverEnvArr = [];
        foreach ($imageTimesArr as $key => $value) {
            $keyMd5 = md5($value['image_name']);
            if (array_key_exists($keyMd5, $redDataArrNew)) {
                $redDataArrNew[$keyMd5]['sleep_time'] = $value['sleep_time'];
                $serverEnvArr[$key] = $redDataArrNew[$keyMd5];
                continue;
            }
            $return['error'] = '编排模板中的镜像：'.$value['image_name'].'在'.$ip.'的环境变量中未设置，请检查，后续操作已禁止';
            return $return;
        }
        $dataArr = [];
        if (!empty($serverEnvArr)) {
            if (count($imageNameArr) <> count($serverEnvArr)) {
                $return['error'] = $ip.':该实例下容器的环境变量或模板发生变化，请先同步实例镜像列表，然后重新配置2';
                return $return;
            }
            $model             = new DockerImageModel();
            $serverImgEnvModel = new ServerImageEnvModel();
            //需要加入以下逻辑，真正要启动的镜像是compose中的镜像，但是如果修改了compose，
            //那么docker_image表（表示容器中已经拉下来的image）中就会记录以前的，那么不能使用id来区分，
            //需要通过ip（gaea_server 中的public_ip）和镜像名称来区分
            //所以：第一步：获取dockercompose中的景象名称和实例ip
            //第二步：在docker_image表中做过滤，获得镜像的id，然后执行后续
            foreach ($serverEnvArr as $red) {
                $res = $model->findOneByIpName($ip, $red['image_name']);
                echo 22222;
                if ($res['status']) {
                    $red['image_name'] = $res['data']['name_version'];
                } else {
                    $return['error'] = '未获取到镜像名称';
                    return $return;
                }
                if (! in_array(
                    $red['image_name'].'_'.$red['container_name'],
                    $openDockerArr
                )
                ) {
                    $data = $serverImgEnvModel->findOne(
                        $server_id,
                        $red['image_name']
                    );
                    echo 3333;
                    $data['image_name']         = $red['image_name'];
                    $data['container_name']     = $red['container_name'];
                    $data['container_describe'] = $red['container_describe'];
                    $data['sleep_time']         = $red['sleep_time'];
                    $data = $this->varReplace(
                        $data,
                        $project_id,
                        $red['server_id'],
                        'gaea'
                    );

                    if (! empty($data['status']) && $data['status'] === 400) {
                        $return['error'] = $data['error'];

                        return $return;
                    }
                    $dataArr[] = $data;
                }
            }
        } else {
            $return['error'] = $ip.'该实例还没有设置环境变量';
            return $return;
        }
        $return['status'] = true;
        $return['data']   = $dataArr;
        return $return;
    }


    public function findAll($paramsArr = null)
    {
        $sql = 'SELECT * FROM `server_env` ';
        $arr = [];
        if (! empty($paramsArr)) {
            $sql    .= ' WHERE ';
            $strArr = [];
            foreach ($paramsArr as $key => $value) {
                if (is_array($value)) {
                    $strArr[] = $key.' in (\''.implode('\',\'', $value).'\') ';
                } else {
                    if (in_array($key, ['image_name', 'container_name'])) {
                        $strArr[] = $key.' like "%'.$value.'%" ';
                    } else {
                        $strArr[]      = $key.'=:'.$key.' ';
                        $arr[':'.$key] = $value;
                    }
                }
            }
            $sql .= ' '.implode(' and ', $strArr);
        }

        $sth = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $sth->execute($arr);
        $red = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $red;
    }

    // 根据实例ID销毁实例环境变量实例

    public function insertOne($data)
    {
        $return = ['status' => false];
        //先删除后插入
        $sql
             = 'DELETE FROM `server_env` WHERE server_id=:server_id AND image_name=:image_name';
        $sth = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $sth->execute(
            [
                ':server_id'  => $data[':server_id'],
                ':image_name' => $data[':image_name'],
            ]
        );


        $sql
                   = 'INSERT INTO `server_env` (image_name,server_id,container_name,container_describe,container_num) VALUES (:image_name,:server_id,:container_name,:container_describe,:container_num)';
        $sth       = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $serStatus = $sth->execute($data);

        if (! $serStatus) {
            $error           = $sth->errorInfo();
            $return['error'] = $error;

            return $return;
        }

        return ['status' => true];
    }

    // 根据image销毁所有环境变量实例

    public function destroyEnvInsByServer($server_id, $type)
    {
        $return = ['status' => true];

        $sql
             = 'SELECT * FROM `server_env` WHERE server_id=:server_id AND type=:type ';
        $sth = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $sth->execute([':server_id' => $server_id, ':type' => $type]);
        $red = $sth->fetchAll(PDO::FETCH_ASSOC);

        if (! empty($red)) {
            foreach ($red as $value) {
                $model  = new EnvTemplateModel();
                $status = $model->deleteOneInstance($value['env_id']);
                if (! $status['status']) {
                    $return['status']                     = false;
                    $return['errorArr'][$value['env_id']] = $status['error'];
                }

                VarQuoteModel::envDelQuote($value['id']);
            }
        } else {
            $return['status'] = true;
            $return['error']  = '未发现此条件下的环境变量实例';
        }

        if ($return['status']) {
            $temSql
                    = 'DELETE FROM `server_env` WHERE server_id=:server_id AND type=:type ';
            $sth    = $this->db->prepare(
                $temSql,
                [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
            );
            $status = $sth->execute(
                [':server_id' => $server_id, ':type' => $type]
            );
            if (! $status) {
                $return['error'] = $sth->errorInfo();

                return $return;
            }
        }

        return $return;
    }

    // 根据server和tid获取环境变量设置

    public function findOneByServerImageTid($server_id, $type, $imageName, $tid)
    {
        $image_name                  = $this->getImageName($imageName);
        $sql
                                     = 'SELECT * FROM `server_env` WHERE server_id=:server_id AND type=:type AND image_name=:image_name AND container_tid=:container_tid ';
        $sql                         .= ' ORDER BY id DESC ';
        $sth                         = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $paramsArr                   = [
            ':server_id'  => $server_id,
            ':type'       => $type,
            ':image_name' => $image_name,
        ];
        $paramsArr[':container_tid'] = $tid;
        $sth->execute($paramsArr);
        $red = $sth->fetch(PDO::FETCH_ASSOC);

        $model   = new EnvTemplateModel();
        $diffArr = $model->envServerChech();

        $tem_diff = false;

        if (! empty($red)) {
            if (! empty($diffArr[$red['image_name']])
                && in_array(
                    $red['env_id'],
                    $diffArr[$red['image_name']]
                )
            ) {
                $temData = $model->findOneByImage($red['image_name']);
                $insData = $model->findOneInstance($red['env_id']);
                if (! empty($temData['params_env'])) {
                    foreach ($temData['params_env'] as $key => $value) {
                        if (array_key_exists($key, $insData['params_env'])) {
                            $temData['params_env'][$key]
                                = $insData['params_env'][$key];
                        }
                    }
                }

                if (! empty($temData['params_data'])) {
                    foreach ($temData['params_data'] as $key => $value) {
                        if (array_key_exists($key, $insData['params_data'])) {
                            $temData['params_data'][$key]
                                = $insData['params_data'][$key];
                        }
                    }
                }

                $data     = $temData;
                $tem_diff = true;
            } else {
                $data = $model->findOneInstance($red['env_id']);
            }
            $data['container_name']     = $red['container_name'];
            $data['container_describe'] = $red['container_describe'];
        } else {
            $data                       = $model->findOneByImage($image_name);
            $data['container_name']     = '';
            $data['container_describe'] = '';
        }

        $data['image_name'] = $imageName;

        return ['data' => $data, 'tem_diff' => $tem_diff];
    }

    // 根据sid获取所有环境变量设置，供新格式模板、环境变量脚本使用
    public function findAllBySid($sid)
    {
        $sql
             = 'SELECT * FROM `server_env` WHERE server_id=:server_id ';
        $sth = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $sth->execute([':server_id' => $sid]);
        $redAll = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $redAll;
    }

    // 编辑环境变量设置，供新格式模板、环境变量脚本使用
    public function update($id, $num, $tid)
    {
        $return = ['status' => 400, 'error' => ''];
        $sql    = 'SELECT * FROM `server_env` WHERE id=:id';
        $sth    = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $sth->execute([':id' => $id]);
        $red = $sth->fetch(PDO::FETCH_ASSOC);

        if (! empty($red)) {
            $sql
                    = 'UPDATE `server_env` SET `container_num`=:container_num,`container_tid`=:container_tid WHERE id=:id';
            $sth    = $this->db->prepare(
                $sql,
                [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
            );
            $status = $sth->execute(
                [
                    ':id'            => $id,
                    ':container_num' => $num,
                    ':container_tid' => $tid,
                ]
            );
            if (! $status) {
                $return['error'] = $sth->errorInfo();

                return $return;
            }
        }
        $return['status'] = 200;

        return $return;
    }
    //同时删除server_image_env中的记录
    public function deleteByServerImage($serverId, $imageArr)
    {
        $return = [];
        $imageNameIn = implode(',', $imageArr);
        $sql = "DELETE FROM server_env WHERE server_id=:server_id AND find_in_set(image_name, '".$imageNameIn."')";
        $sth = $this->db->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute([':server_id' => $serverId]);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = '删除server_env表数据失败：'.$error[2];
        } else {
            $sql = "DELETE FROM server_image_env WHERE server_id=:server_id AND find_in_set(image_name, '".$imageNameIn."')";
            $sth = $this->db->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
            $return['status'] = $sth->execute([':server_id' => $serverId]);
            if (!$return['status']) {
                $error           = $sth->errorInfo();
                $return['error'] = '删除server_env表数据成功，删除server_image_env表数据失败：'.$error[2];
            }
        }
        return $return;
    }
}
