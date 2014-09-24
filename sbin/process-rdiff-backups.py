#! /bin/env python

# Command control 
import sh

# DB layer interface
import blitzdb

import json 
import os
import sys

# TODO: Clean up this bad code here very hacky
ROOT_DIR = '/' + '/'.join(
    os.path.dirname(os.path.abspath(__file__)).split('/')[1:-1]
)
sys.path.append(ROOT_DIR)

# rdiff-backup-web modules
import config.model as model

class RDBWGeneralException(Exception):
    """rdiff-backup-web general Exception"""
    pass
        

class Config(dict):
    def __init__(self, config_file='../config/rdiff-backup-web.json'):
        # Store absolute path to config param
        self.path = os.path.abspath(config_file)

        # Store raw JSON file 
        if os.path.isfile(config_file):
            with file(config_file, 'r') as f:
                self.raw = json.loads(f.read()) 
        else:
            raise RDBWGeneralException('Config File not Found')


        if self._validate_config(self.raw):
            # Add config options to dict interface
            self.update(self.raw)
            # Add config objects as attr
            for attr in self.raw:
                setattr(self, attr, self.raw[attr])
        else:
            raise RDBWGeneralException('Config Not Correct')

    def _validate_config(self, raw_json):
        # TODO: Validate the config
        return True


class Backups(object):
    def __init__(self, config):
        # Collect Config instance
        self.config = config if isinstance(config, Config) else None

        # Find DB and creat connection instance
        try:
            self.db = blitzdb.FileBackend(
                os.path.abspath(config.DB_PATH)
            )
        except:
            raise RDBWGeneralException('Config incomplete: DB_PATH not defined')

        self.backups = self.db.filter(model.Backups, {'active': True}, sort_by='seq')


def validate_dir(dir):
    def inner(func):
        if not os.path.isdir(dir):
            raise RDBWGeneralException(
                'Path is not a directory: %s' % str(dir)
            )
        return func
    return inner

def build_command(command):
    def inner(func):
        cmd_basename = os.path.basename(command)
        # Small assumption is that if cms in global scope it is already correct.
        if cmd_basename in globals().keys():
            return func
        # Check here if the basename and command match
        try:
            exec 'from sh import ' + cmd_basename in globals()
            return func
        except:
            if hasattr(config, cmd_basename.upper()):
                cmd = getattr(config, cmd_basename.upper())
                exec 'global ' + cmd_basename in globals()
                exec cmd_basename + '= sh.Command("' + cmd + '")' in globals()
                return func        
            else:
                raise RDBWGeneralException('Cannot find %s in system or defined in config' % cmd_basename)
                # TODO: Create test to get to FAIL here 
    return inner

def get_config():
    if not len(sys.argv) > 1:
        default_config = os.path.abspath('../config/rdiff-backup-web.json')
        config_file =  default_config if os.path.isfile(default_config) else None
    else:
        config_file = sys.argv[1] if os.path.isfile(sys.argv[1]) else None

    if config_file:
        config = Config(config_file=config_file)
        return config
    else:
        raise RDBWGeneralException('Could not find a valid config file.')



config = get_config()

@validate_dir(config.BACKUP_STORE_DIR)
@validate_dir(config.MOUNT_TEMP_DIR)
@build_command('rdiff_backup')
@build_command('mount')
@build_command('umount')
def samba_backup(backup):
    # # Mount SMBFS share 
    # mount('-t smbfs' backup.source, config.MOUNT_TEMP_DIR)
    # # Perform BACKUP 
    # rdiff_backup('-v2', config.MOUNT_TEMP_DIR, dstore)
    # # Unmount SMBFS share
    # umount(config.MOUNT_TEMP_DIR)
    print 'user: ', backup['source_user']
    print 'pass: ', backup['source_pass'] if backup.haskey('source_pass') else None
    print 'dir: ', backup['source_dir']
    print 'host: ', backup['source_host']
    print 'name: ', backup['name']

@validate_dir(config.BACKUP_STORE_DIR)
@build_command('rdiff_backup')
def ssh_backup(backup):
    # Perform BACKUP 
    #rdiff_backup()

    print 'name: ', backup['name']

@validate_dir(config.BACKUP_STORE_DIR)
@build_command('rdiff_backup')
def local_fs_backup(backup):
    # Perform BACKUP 
    #rdiff_backup()

    print 'name: ', backup['name']


backup_set = Backups(config)

for backup in backup_set.backups:
    if backup.type == 'SMB':
        samba_backup(backup)
    elif backup.type == 'SSH':
        ssh_backup(backup)
    elif backup.type == 'LOCAL':
        local_fs_backup(backup)
    else:
        # TODO: Add propper logging here 
        print 'Backup %s has unknow type of %s' % (backup.name, backup.type)
