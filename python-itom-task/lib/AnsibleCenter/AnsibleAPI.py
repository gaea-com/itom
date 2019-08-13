# -*- coding: utf-8 -*-
"""
__title__=''
__author__='jack'
__mtime__='2017/3/22'

"""
from __future__ import (absolute_import, division, print_function)
__metaclass__ = type

from collections import namedtuple
from ansible.parsing.dataloader import DataLoader
from ansible.vars import VariableManager
from ansible.inventory import Inventory
from ansible.playbook.play import Play
from ansible.executor.task_queue_manager import TaskQueueManager

from lib.AnsibleCenter.AnsibleLOG import ResultCallback
from conf import config


class AnsibleCenter(object):

    def __init__(self, parameter,
                 remote_user=config.SSH_USER,
                 module_name=None,
                 forks=1,
                 task_id=None):
        self.ip_list = parameter['ip_list']
        self.become_user = config.BecomeUser
        self.inventory_tmp = config.InventoryTmp
        self.forks = forks
        self.module_name = module_name
        self.become_method = config.BecomeMethod

        self.Options = namedtuple('Options',
                                  ['subset', 'ask_pass', 'become_user', 'poll_interval', 'sudo', 'private_key_file',
                                   'syntax', 'one_line', 'diff', 'sftp_extra_args', 'check', 'remote_user',
                                   'become_method', 'vault_password_file', 'output_file', 'ask_su_pass',
                                   'new_vault_password_file', 'inventory', 'forks', 'listhosts', 'ssh_extra_args',
                                   'module_name', 'become_ask_pass', 'seconds', 'become', 'su_user',
                                   'ask_sudo_pass', 'extra_vars', 'verbosity', 'tree', 'su', 'ssh_common_args',
                                   'connection', 'ask_vault_pass', 'timeout', 'module_path', 'sudo_user',
                                   'scp_extra_args']
                                  )
        self.options = self.Options(
            subset=None, ask_pass=False, become_user=self.become_user, poll_interval=15,
            sudo=True, private_key_file=config.PriviteKeyPath, syntax=None,
            one_line=None, diff=False, sftp_extra_args='', check=False,
            remote_user=remote_user, become_method=self.become_method,
            vault_password_file=None, output_file=None, ask_su_pass=False,
            new_vault_password_file=None, inventory=self.inventory_tmp,
            forks=self.forks, listhosts=None, ssh_extra_args='',
            module_name=module_name, become_ask_pass=False,
            seconds=0, become=True, su_user=None, ask_sudo_pass=False,
            extra_vars=[], verbosity=0, tree=None, su=True, ssh_common_args='',
            connection='smart', ask_vault_pass=False,
            timeout=config.AnsibleTimeout,
            module_path=None, sudo_user=None, scp_extra_args=''
        )

        self.variable_manager = VariableManager()
        self.loader = DataLoader()
        self.passwords = None
        self.results_callback = ResultCallback(
            model_name=module_name,
            task_id=task_id
        )
        self.inventory = Inventory(loader=self.loader,
                                   variable_manager=self.variable_manager,
                                   host_list=self.ip_list)
        self.variable_manager.set_inventory(self.inventory)

    def run_task(self, play_source):
        play = Play().load(
            data=play_source,
            variable_manager=self.variable_manager,
            loader=self.loader
        )

        # actually run it
        tqm = None
        try:
            tqm = TaskQueueManager(
                inventory=self.inventory,
                variable_manager=self.variable_manager,
                loader=self.loader,
                options=self.options,
                passwords=self.passwords,
                stdout_callback=self.results_callback,
            )
            tqm.run(play)

        finally:
            if tqm is not None:
                tqm.cleanup()
