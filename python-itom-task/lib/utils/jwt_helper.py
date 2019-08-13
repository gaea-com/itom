# -*- coding: utf-8 -*-
"""
__title__ = ''
__author__ = 'jack'
__mtime__ = '2017/12/12'

"""
import jwt
import logging
import traceback
from jwt.exceptions import DecodeError
from conf import config
logger = logging.getLogger("ansible")


def create_jwt(messages):
    """
    创建 jwt token，以时间戳为参数，token 有效期在配置文件中配置。
    :return: string  token
    """
    encoded_jwt = jwt.encode(messages, config.JWT_SECRET_KEY, algorithm='HS256')
    logger.debug(("create_jwt >>>", encoded_jwt))
    return encoded_jwt


def decode_jwt(jwt_string):
    """
    jwt token 转义，并检查时间是否被篡改或有效时间已过。
    :param jwt_string: string  jwt_token 字符串
    :return: bool | string
    """
    try:
        decode_string = jwt.decode(jwt_string, config.JWT_SECRET_KEY, algorithms=['HS256'])
        return decode_string

    except DecodeError as error:
        error_string = "jwt 解码失败 {0} ==> {1}".format(jwt_string,error)
        logger.error(error_string)
        return False

    except jwt.exceptions:
        error_string = "jwt 解码失败 {0} ==> {1}".format(jwt_string, traceback.format_exc())
        logger.error(error_string)
        return False


if __name__ == "__main__":
    pass
