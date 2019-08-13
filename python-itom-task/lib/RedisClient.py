# -*- coding: utf-8 -*-
"""
__title__ = ''
__author__ = 'jack'
__mtime__ = '2017/8/22'

"""
import sys
import redis
import logging
import traceback
from conf import config
logger = logging.getLogger("redis")


class RedisConnection(object):
    """  redis 客户端设置  """

    pool = None
    def __init__(self):
        """  redis 单例模式初始化  """
        if self.pool is None:
            RedisConnection.create_pool()
        self.redis_client = redis.Redis(connection_pool=RedisConnection.pool)
        try:
            self.redis_client.ping()
        except redis.exceptions.ResponseError as e:
            logger.error(e)
            sys.exit(128)
        except redis.exceptions.ConnectionError as e:
            logger.error(e)
            sys.exit(128)
        # python中，所有类的实例中的成员变量，都是公用一个内存地址，因此，及时实例化多个RedisCache类，内存中存在的pool也只有一个

    @staticmethod
    def create_pool():
        RedisConnection.pool = redis.ConnectionPool(
            host=config.RedisConnectIP,
            port=config.RedisConnectPort,
            db=config.RedisDB,
            password=config.RedisPassword)

    def HashSet(self, name, key, value):
        # hash 类型  设置  名称  域 值
        # 0 插入完成
        # 1 更新完成
        # False 出问题啦。
        try:
            result_code = self.redis_client.hset(name, key, value)
            result = dict(
                changed=True,
                changed_code=result_code
            )

        except:
            logger.error("redis hash 类型(name:{name}|key:{key}|value:{value})插入失败。({error})".format(
                name=name,
                key=key,
                value=value,
                error=traceback.format_exc()
            ))
            result = dict(
                changed=False
            )

        return result

    def HashDel(self, name, *key):
        # hash 类型  删除指定名称的域
        # 0 删除对象不存在
        # 1 删除完成
        try:
            result_code = self.redis_client.hdel(name, *key)
            result = dict(
                changed=True,
                changed_code=result_code
            )
        except:
            logger.error("redis hash 类型(name:{name}|key:{key})删除失败。({error})".format(
                name=name,
                key=key,
                error=traceback.format_exc()
            ))
            result = dict(
                changed=False
            )
        return result

    def HashLen(self, name):
        # hash 类型  查询指定名称域数量
        try:
            result_len = self.redis_client.hlen(name)
            result = dict(
                changed=True,
                result_len=result_len
            )
        except:
            logger.error("redis hash 类型(name:{name})长度获取失败。({error})".format(
                name=name,
                error=traceback.format_exc()
            ))
            result = dict(
                changed=False
            )
        return result

    def HashGetAll(self, name):
        # hash 类型  获取指定名称所有域
        try:
            result_dict = self.redis_client.hgetall(name)
            result = dict(
                changed=True,
                result_dict=result_dict
            )

        except:
            logger.error("redis hash 类型(name:{name})values 获取失败。({error})".format(
                name=name,
                error=traceback.format_exc()
            ))
            result = dict(
                changed=False
            )
        return result

    def ListLpush(self, name, value):
        # list 队列  队列头添加
        #
        try:
            result_code = self.redis_client.lpush(name, value)
            logger.info("push redis over. key => {key} , value => {value}".format(
                key = name,
                value = value
            ))
            result = dict(
                changed = True,
                changed_code = result_code
            )
        except:
            logger.error("redis list 类型(name:{name}|value:{value})插入失败。({error})".format(
                name=name,
                value=value,
                error=traceback.format_exc()
            ))
            result = dict(
                changed=False
            )
        return result

    def ListRPop(self, name):
        # list 队列  队列尾部获取数据
        try:
            result = self.redis_client.rpop(name)
            result = dict(
                changed=True,
                result=result
            )
        except:
            logger.error("redis list 类型(name:{name})取值失败。({error})".format(
                name=name,
                error=traceback.format_exc()
            ))
            result = dict(
                changed=False
            )
        return result

    def SetAdd(self, name, value):
        # 普通 key  value 设置
        try:
            result = self.redis_client.sadd(name, value)
            result = dict(
                changed=True,
                result=result
            )
        except:
            logger.error("redis list 类型(name:{name})删除失败。({error})".format(
                name=name,
                error=traceback.format_exc()
            ))
            result = dict(
                changed=False
            )
        return result

    def SetPop(self, name):
        # 普通 key value 删除
        try:
            result = self.redis_client.spop(name)
            result = dict(
                changed=True,
                result=result
            )
        except:
            logger.error("redis list 类型(name:{name})删除失败。({error})".format(
                name=name,
                error=traceback.format_exc()
            ))
            result = dict(
                changed=False
            )
        return result


    def LRPop(self,keys):
        # 返回 redis brpop 对象，用以监听队列数据。
        return self.redis_client.brpop(keys)
