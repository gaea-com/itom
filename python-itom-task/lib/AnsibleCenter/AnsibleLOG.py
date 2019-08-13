# -*- coding: utf-8 -*-
"""
__title__ = ''
__author__ = 'jack'
__mtime__ = '2017/3/22'

"""
import json
import logging
import traceback
import re
from ansible import constants as C
from ansible.plugins.callback import CallbackBase
from ansible.utils.color import colorize, hostcolor
from lib import RedisClient
from lib.utils import jwt_helper

redis_cli = RedisClient.RedisConnection()
logger = logging.getLogger("ansible")


def error_msg_check(error):

    metch_1 = re.compile(r".*(Is a directory:).*")
    if len(metch_1.findall(error)) > 0:
        return "remote file is a directory, fetch cannot work on directories"


class ResultCallback(CallbackBase):
    """
    This is the callback interface, which simply prints messages
    to stdout when new callback events are received.

    self.result = {
        'status' : '200', # (200 success  500 error)
        'error'  ： ''，  # 记录服务器的异常状态
        'data' : {
            'changed' : 'true|false',  # 执行成功
            'stderr' : '',             # 执行失败的返回结果
            'stdout' : '',             # 执行成功的返回结果
            'cmd'    : '',             # 执行的命令
            'sudo'   : 'true|false',   # 是否使用sudo
            'IP'     : ''              # 执行的机器 IP
        }
    }

    """

    CALLBACK_VERSION = 2.0
    CALLBACK_TYPE = 'stdout'
    CALLBACK_NAME = 'default'

    def __init__(self, model_name, task_id):
        self.MName = model_name
        self._play = None
        self._last_task_banner = None
        self.result = {
            'status': False,
            'ip': ''
        }
        self.TaskID = task_id
        super(ResultCallback, self).__init__()

    def collect_message(self, info):
        try:
            logger.info("任务处理结果: {}".format(info))
            redis_cli.ListLpush(self.TaskID, json.dumps(info))

        except:
            logger.error('Put (%s) to queue is failed.' % info)

    def success_out(self, result, ip):
        msg = self.result
        try:
            if isinstance(result, dict):
                msg['ip'] = ip
                msg['status'] = True
                msg['error'] = ''

                if self.MName == 'shell':
                    msg['stdout'] = result['stdout']
                    msg['cmd'] = result['invocation']['module_args']['_raw_params']

                elif self.MName == 'copy':
                    if not result.get("changed"):
                        logger.error("ansible copy module is failed.\nresult ==> {result}".format(result=result))
                        msg['status'] = False
                        msg['dest'] = result.get('dest', None)
                        msg['src'] = result.get('path', None)
                        msg['mode'] = result.get('mode', None)
                        msg['owner'] = result.get('owner', None)
                        msg['size'] = result.get('size', None)
                        msg['state'] = result.get('state', None)
                        if result.get("module_stdout"):
                            msg['error'] = result.get("module_stdout")
                        else:
                            if result.get('src', True) and result.get("dest"):
                                msg['status'] = True
                                msg['stdout'] = result.get('msg', 'success.')
                                msg['token'] = jwt_helper.create_jwt(
                                    {"dest": result.get("dest")}
                                )
                            else:
                                msg['error'] = result.get('msg', 'file is not exists.')

                    else:
                        msg['dest'] = result.get('dest')
                        msg['src'] = result.get('src')
                        msg['mode'] = result.get('mode')
                        msg['owner'] = result.get('owner')
                        msg['size'] = result.get('size')
                        msg['state'] = result.get('state')
                        msg['token'] = jwt_helper.create_jwt(
                            {"dest": result.get("dest")}
                        )
                        msg['stdout'] = "success"

        except:
            logger.error('Collect message is failed.({})'.format(result))
        finally:
            self.collect_message(msg)

    def failed_out(self, result, ip):
        msg = self.result
        try:
            if isinstance(result, dict):
                msg['ip'] = ip
                msg['error'] = ''
                unreachable = result.get('unreachable', False)
                msg['status'] = False

                if unreachable:
                    msg['error'] = result['msg']
                    msg['status'] = False

                if self.MName == 'shell':
                    if result.get('changed', False):
                        msg['error'] = "{}...{}...{}".format(result.get("stderr", "error"),
                                                             result.get("module_stdout", "error"),
                                                             result.get("exception"))
                        msg['cmd'] = result['invocation']['module_args']['_raw_params']

                    else:
                        try:
                            msg['error'] = result['msg']
                            msg['cmd'] = result['invocation']['module_args']['_raw_params']

                        except:
                            msg['error'] = result['msg']

                elif self.MName == 'copy':
                    logger.error("ansible copy module is failed.\nresult ==> {result}".format(result=result))
                    msg['status'] = False
                    msg['dest'] = result.get('dest', None)
                    msg['src'] = result.get('path', None)
                    msg['mode'] = result.get('mode', None)
                    msg['owner'] = result.get('owner', None)
                    msg['size'] = result.get('size', None)
                    msg['state'] = result.get('state', None)
                    if result.get("module_stdout"):
                        msg['error'] = error_msg_check(result.get("module_stdout"))
                    else:
                        msg['error'] = result.get('msg', 'file is not exists.')

        except:
            logger.error('Collect message is failed.({})'.format(result))
            logger.error("error data ==> {error}".format(error=traceback.format_exc()))

        finally:
            self.collect_message(msg)

    def v2_runner_on_failed(self, result, ignore_errors=False):
        if self._play.strategy == 'free' and self._last_task_banner != result._task._uuid:
            self._print_task_banner(result._task)

        delegated_vars = result._result.get('_ansible_delegated_vars', None)
        if 'exception' in result._result:
            if self._display.verbosity < 3:
                error = result._result['exception'].strip().split('\n')[-1]
                msg = "An exception occurred during task execution. To see the full traceback, use -vvv. The error was: %s" % error
            else:
                msg = "An exception occurred during task execution. The full traceback is:\n" + result._result['exception']

            logger.error(msg)

        if result._task.loop and 'results' in result._result:
            self._process_items(result)

        else:
            if delegated_vars:
                ansible_msg = "fatal: [%s -> %s]: FAILED! => %s" % (result._host.get_name(), delegated_vars['ansible_host'], self._dump_results(result._result))
                self.failed_out(result._result, result._host.get_name())
                #self._display.display("fatal: [%s -> %s]: FAILED! => %s" % (result._host.get_name(), delegated_vars['ansible_host'], self._dump_results(result._result)), color=C.COLOR_ERROR)
            else:
                self.failed_out(result._result,result._host.get_name())
                #self._display.display("fatal: [%s]: FAILED! => %s" % (result._host.get_name(), self._dump_results(result._result)), color=C.COLOR_ERROR)

        if ignore_errors:
            ansible_msg = "...ignoring"
            info = "v2_runner_on_failed >>>>>" + ansible_msg
            logger.error(info)
            #self._display.display("...ignoring", color=C.COLOR_SKIP)

    def v2_runner_on_skipped(self, result):
        # print "===========  v2_runner_on_skipped  =========="
        # print "{}".format(result)
        # print "============================================="
        if C.DISPLAY_SKIPPED_HOSTS:
            if self._play.strategy == 'free' and self._last_task_banner != result._task._uuid:
                self._print_task_banner(result._task)

            if result._task.loop and 'results' in result._result:
                self._process_items(result)
            else:
                msg = "skipping: [%s]" % result._host.get_name()
                if (self._display.verbosity > 0 or '_ansible_verbose_always' in result._result) and not '_ansible_verbose_override' in result._result:
                    msg += " => %s" % self._dump_results(result._result)

                info = "v2_runner_on_skipped >>>>>" + msg
                logger.info(info)
                #self._display.display(msg, color=C.COLOR_SKIP)

    def v2_runner_on_unreachable(self, result):
        # print "===========  v2_runner_on_unreachable  =========="
        # print "{}".format(result)
        # print "================================================="
        if self._play.strategy == 'free' and self._last_task_banner != result._task._uuid:
            self._print_task_banner(result._task)

        delegated_vars = result._result.get('_ansible_delegated_vars', None)
        if delegated_vars:
            ansible_msg = "fatal: [%s -> %s]: UNREACHABLE! => %s" % (result._host.get_name(), delegated_vars['ansible_host'], self._dump_results(result._result))
            self.failed_out(result._result, result._host.get_name())
            #self._display.display("fatal: [%s -> %s]: UNREACHABLE! => %s" % (result._host.get_name(), delegated_vars['ansible_host'], self._dump_results(result._result)), color=C.COLOR_UNREACHABLE)
        else:
            ansible_msg = "fatal: [%s]: UNREACHABLE! => %s" % (result._host.get_name(), self._dump_results(result._result))
            self.failed_out(result._result, result._host.get_name())
            #self._display.display("fatal: [%s]: UNREACHABLE! => %s" % (result._host.get_name(), self._dump_results(result._result)), color=C.COLOR_UNREACHABLE)

    def v2_playbook_on_no_hosts_matched(self):
        # print "===========  v2_playbook_on_no_hosts_matched  =========="
        # print "========================================================"
        ansible_msg = "skipping: no hosts matched"
        info = "v2_playbook_on_no_hosts_matched >>>>>" + ansible_msg
        logger.info(info)
        #self._display.display("skipping: no hosts matched", color=C.COLOR_SKIP)

    def v2_playbook_on_no_hosts_remaining(self):
        # print "===========  v2_playbook_on_no_hosts_remaining  =========="
        # print "=========================================================="
        ansible_msg = "NO MORE HOSTS LEFT"
        info = "v2_playbook_on_no_hosts_remaining >>>>>" + ansible_msg
        logger.info(info)
        #self._display.banner("NO MORE HOSTS LEFT")

    def v2_playbook_on_task_start(self, task, is_conditional):
        # print "===========  v2_playbook_on_task_start  =========="
        # print "{}".format(task)
        # print "=================================================="

        if self._play.strategy != 'free':
            self._print_task_banner(task)

    def _print_task_banner(self, task):
        # print "===========  _print_task_banner  =========="
        # print "{}".format(task)
        # print "==========================================="
        # args can be specified as no_log in several places: in the task or in
        # the argument spec.  We can check whether the task is no_log but the
        # argument spec can't be because that is only run on the target
        # machine and we haven't run it thereyet at this time.
        #
        # So we give people a config option to affect display of the args so
        # that they can secure this if they feel that their stdout is insecure
        # (shoulder surfing, logging stdout straight to a file, etc).
        args = ''
        if not task.no_log and C.DISPLAY_ARGS_TO_STDOUT:
            args = u', '.join(u'%s=%s' % a for a in task.args.items())
            args = u' %s' % args

        #LOG.info_(u"TASK [%s%s]" % (task.get_name().strip(), args))
        if self._display.verbosity >= 2:
            path = task.get_path()
            if path:
                ansible_tmp = u"task path: %s" % path
                info = "_print_task_banner >>>>>" + ansible_tmp
                logger.info(info)
                #self._display.display(u"task path: %s" % path, color=C.COLOR_DEBUG)

        self._last_task_banner = task._uuid

    def v2_playbook_on_cleanup_task_start(self, task):
        # print "===========  v2_playbook_on_cleanup_task_start  =========="
        # print "{}".format(task)
        # print "=========================================================="
        logger.info("CLEANUP TASK [%s]" % task.get_name().strip())

    def v2_playbook_on_handler_task_start(self, task):
        logger.info("RUNNING HANDLER [%s]" % task.get_name().strip())

    def v2_playbook_on_play_start(self, play):
        name = play.get_name().strip()
        if not name:
            msg = "ansible.start"
        else:
            msg = "ansible.[%s].start" % name

        self._play = play
        logger.info(msg)

    def v2_on_file_diff(self, result):
        # print "===========  v2_on_file_diff  =========="
        # print "{}".format(result)
        # print "========================================"
        if result._task.loop and 'results' in result._result:
            for res in result._result['results']:
                if 'diff' in res and res['diff'] and res.get('changed', False):
                    diff = self._get_diff(res['diff'])
                    if diff:
                        self._display.display(diff)
        elif 'diff' in result._result and result._result['diff'] and result._result.get('changed', False):
            diff = self._get_diff(result._result['diff'])
            if diff:
                self._display.display(diff)

    def v2_runner_item_on_ok(self, result):
        # print "===========  v2_runner_item_on_ok  =========="
        # print "{}".format(result)
        # print "============================================="
        delegated_vars = result._result.get('_ansible_delegated_vars', None)
        if result._task.action in ('include', 'include_role'):
            return
        elif result._result.get('changed', False):
            msg = 'changed'
            color = C.COLOR_CHANGED
        else:
            msg = 'ok'
            color = C.COLOR_OK

        if delegated_vars:
            msg += ": [%s -> %s]" % (result._host.get_name(), delegated_vars['ansible_host'])
        else:
            msg += ": [%s]" % result._host.get_name()

        msg += " => (item=%s)" % (self._get_item(result._result),)

        if (self._display.verbosity > 0 or '_ansible_verbose_always' in result._result) and not '_ansible_verbose_override' in result._result:
            msg += " => %s" % self._dump_results(result._result)
        info = "v2_runner_item_on_ok >>>>>" + msg
        logger.info(info)
        #self._display.display(msg, color=color)

    def v2_runner_item_on_failed(self, result):
        # print "===========  v2_runner_item_on_failed  =========="
        # print "{}".format(result)
        # print "================================================="
        delegated_vars = result._result.get('_ansible_delegated_vars', None)
        if 'exception' in result._result:
            if self._display.verbosity < 3:
                # extract just the actual error message from the exception text
                error = result._result['exception'].strip().split('\n')[-1]
                msg = "An exception occurred during task execution. To see the full traceback, use -vvv. The error was: %s" % error
            else:
                msg = "An exception occurred during task execution. The full traceback is:\n" + result._result['exception']

            self._display.display(msg, color=C.COLOR_ERROR)

        msg = "failed: "
        if delegated_vars:
            msg += "[%s -> %s]" % (result._host.get_name(), delegated_vars['ansible_host'])
        else:
            msg += "[%s]" % (result._host.get_name())
        info = "v2_runner_item_on_failed >>>>>" + msg + " (item=%s) => %s" % (self._get_item(result._result), self._dump_results(result._result))
        logger.info(info)
        #self._display.display(msg + " (item=%s) => %s" % (self._get_item(result._result), self._dump_results(result._result)), color=C.COLOR_ERROR)
        self._handle_warnings(result._result)

    def v2_runner_item_on_skipped(self, result):
        # print "===========  v2_runner_item_on_skipped  =========="
        # print "{}".format(result)
        # print "=================================================="
        if C.DISPLAY_SKIPPED_HOSTS:
            msg = "skipping: [%s] => (item=%s) " % (result._host.get_name(), self._get_item(result._result))
            if (self._display.verbosity > 0 or '_ansible_verbose_always' in result._result) and not '_ansible_verbose_override' in result._result:
                msg += " => %s" % self._dump_results(result._result)
            info = 'v2_runner_item_on_skipped >>>>>' + msg
            logger.info(info)
            self._display.display(msg, color=C.COLOR_SKIP)

    def v2_playbook_on_include(self, included_file):
        # print "===========  v2_playbook_on_include  =========="
        # print "{}".format(included_file)
        # print "==============================================="
        msg = 'included: %s for %s' % (included_file._filename, ", ".join([h.name for h in included_file._hosts]))
        info = 'v2_playbook_on_include >>>>' + msg
        logger.info(info)
        self._display.display(msg, color=C.COLOR_SKIP)

    def v2_playbook_on_stats(self, stats):
        # print "===========  v2_playbook_on_stats  =========="
        # print "{}".format(stats)
        # print "============================================="
        logger.info("PLAY RECAP")

        hosts = sorted(stats.processed.keys())
        for h in hosts:
            t = stats.summarize(h)

            self._display.display(u"%s : %s %s %s %s" % (
                hostcolor(h, t),
                colorize(u'ok', t['ok'], C.COLOR_OK),
                colorize(u'changed', t['changed'], C.COLOR_CHANGED),
                colorize(u'unreachable', t['unreachable'], C.COLOR_UNREACHABLE),
                colorize(u'failed', t['failures'], C.COLOR_ERROR)),
                screen_only=True
            )

            self._display.display(u"%s : %s %s %s %s" % (
                hostcolor(h, t, False),
                colorize(u'ok', t['ok'], None),
                colorize(u'changed', t['changed'], None),
                colorize(u'unreachable', t['unreachable'], None),
                colorize(u'failed', t['failures'], None)),
                log_only=True
            )

        self._display.display("", screen_only=True)

    def v2_playbook_on_start(self, playbook):
        # print "===========  v2_playbook_on_start  ========"
        # print "{}".format(playbook)
        # print "==========================================="
        if self._display.verbosity > 1:
            from os.path import basename
            logger.info("PLAYBOOK: %s" % basename(playbook._file_name))

        if self._display.verbosity > 3:
            if self._options is not None:
                for option in dir(self._options):
                    if option.startswith('_') or option in ['read_file', 'ensure_value', 'read_module']:
                        continue
                    val =  getattr(self._options,option)
                    if val:
                        self._display.vvvv('%s: %s' % (option,val))

    def v2_runner_retry(self, result):
        # print "===========  v2_runner_retry  ============="
        # print "{}".format(result)
        # print "==========================================="
        msg = "FAILED - RETRYING: %s (%d retries left)." % (result._task, result._result['retries'] - result._result['attempts'])
        if (self._display.verbosity > 2 or '_ansible_verbose_always' in result._result) and not '_ansible_verbose_override' in result._result:
            msg += "Result was: %s" % self._dump_results(result._result)
        info = 'v2_runner_retry >>>>' + msg
        logger.info(info)
        self._display.display(msg, color=C.COLOR_DEBUG)


    def v2_runner_on_ok(self, result):
        # print "===========  v2_runner_on_ok  =========="
        # print "{}".format(result)
        # print "========================================"
        if self._play.strategy == 'free' and self._last_task_banner != result._task._uuid:
            self._print_task_banner(result._task)

        self._clean_results(result._result, result._task.action)

        delegated_vars = result._result.get('_ansible_delegated_vars', None)
        self._clean_results(result._result, result._task.action)
        if result._task.action in ('include', 'include_role'):
            return
        elif result._result.get('changed', False):
            if delegated_vars:
                msg = "SUCCESS: [%s -> %s] | ==>\n" % (result._host.get_name(), delegated_vars['ansible_host'])
            else:
                msg = "SUCCESS: [%s] | ==>\n" % result._host.get_name()
            color = C.COLOR_CHANGED
        else:
            if delegated_vars:
                msg = "ok: [%s -> %s]" % (result._host.get_name(), delegated_vars['ansible_host'])
            else:
                msg = "ok: [%s]" % result._host.get_name()
            color = C.COLOR_OK
        stdout_msg = "%s" % (result._result.get('stdout', 'OK'))

        if result._task.loop and 'results' in result._result:
            self._process_items(result)
        else:

            if (
                    self._display.verbosity > 0 or '_ansible_verbose_always' in result._result) and not '_ansible_verbose_override' in result._result:
                msg += " => %s" % (self._dump_results(result._result),)

            self.success_out(result._result, result._host.get_name())

            #self._display.display(msg, color=color)

        self._handle_warnings(result._result)