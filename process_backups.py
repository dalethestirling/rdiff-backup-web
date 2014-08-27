#! /bin/env python

######################################################################
#
# process_backups.py
#
# Part of rdiff-backup-web
#
# (c) July 2007 Dale Stirling rdiffbackupweb@puredistortion.com
#
# This program is free software; you can redistribute it and/or modify 
# it under the terms of the GNU General Public License as published by 
# the Free Software Foundation; either version 2 of the License, or 
# any later version.
#
# This program is distributed in the hope that it will be useful, but 
# WITHOUT ANY WARRANTY; without even the implied warranty of 
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU 
# General Public License for more details.
#
# You should have received a copy of the GNU General Public License 
# along with this program; if not, write to the Free Software 
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 
# 02111-1307 USA
#
######################################################################

### DEBUGING ###
# 0 = Debuggung off
# 1 = Debugging on
DEBUG = 1

### CONFIG VARIABLES ###

config_php = '/var/www/html/rdiff-backup-web/config.php'

### IMPORT MODULES ###

import MySQLdb

### FUNCTIONS ###

# Config file import.
def conf_read(config_file_path):
	import re
	import os
	import string
	
	#variable_expr = '^\$*\=*\;'
	
	config_file_path_test = os.path.isfile(config_file_path)
	if not config_file_path_test == True:
		print 'Invalid path to Rdiff backup web config file (config.php) please check path configured for this application'
	else:	
		dict01 = dict()	
	   	f = file(config_file_path, 'r')
 	  	expr = re.compile('\A\$.*$')
 		for line in f:		
			result = expr.match(line)
			# print 'DEBUG: %s' % (result)
			if result:
				# print line
				nocarrage = line.strip('\n')
				nodollar = nocarrage.strip('$')
				nospace = nodollar.strip(' ')
				nosemicolon = nospace.strip(';')
				notab = nosemicolon.strip('\t')
				variablesplit = notab.split('=')
				variablesplit0 = variablesplit[0].strip(' ')
				variablesplit1 = variablesplit[1].strip(' ')
				variablesplit0a = variablesplit0.strip('"')
				variablesplit1a = variablesplit1.strip('"')
				dict01[variablesplit0a] = variablesplit1a
		return dict01
	
# DB Query Function #
def db_query(hostaddress, dbusername, dbpassword, dbname, dbquery):
	conn = MySQLdb.connect(host = hostaddress, user = dbusername, passwd = dbpassword, db = dbname)
	cursor = conn.cursor()

      	rundbquery = cursor.execute(dbquery)
	result = cursor.fetchall()
	return result
	cursor.close()
	conn.close()

# SSH Backup Function #
def ssh_backup(rdiff_backup_path, backup_source, backup_store_path, backup_dir, includes, excludes):
	import commands
	import re
	# Create correct backup directory
	if re.compile('\A/.*').search(backup_dir):
		backup_home = backup_dir
	else:
		backup_home = '%s%s' % (backup_store_path, backup_dir)
	# Build and execute Backup Command
	backup_command = '%s %s %s %s %s' % (rdiff_backup_path, includes, excludes, backup_source, backup_home)
	print backup_command
	backup_exec = commands.getstatusoutput(backup_command)
	return backup_exec

def samba_backup(rdiff_backup_path, mount_path, backup_source, backup_store_path, backup_dir, mount_tmp_dir, mount_options, unmount_path, includes, excludes):
	import commands
	import re
        # Create correct backup directory
        if re.compile('\A/.*').search(backup_dir):
                backup_home = backup_dir
        else:
                backup_home = '%s%s' % (backup_store_path, backup_dir)

	# Mounting the Backup to the tmp dir.
	mount_command = '%s %s %s %s' % (mount_path, mount_options, backup_source, mount_tmp_dir)
	mount_command_exec = commands.getstatusoutput(mount_command)

	# Backup data from mounted directory.
        backup_command = '%s %s %s %s %s' % (rdiff_backup_path, includes, excludes, mount_tmp_dir, backup_home)
        backup_exec = commands.getstatusoutput(backup_command)

	# Unmount the directory from mount_tmp_dir.
	#unmount_command = '%s %s' % (unmount_path, mount_tmp_dir)
	#unmount_command_exec = commands.getstatusoutput(unmount_command)
	
	# return Output Value
	return mount_command_exec   

def nfs_backup(rdiff_backup_path, mount_path, backup_source, backup_store_path, backup_dir, mount_tmp_dir, unmount_path, includes, excludes):
	import commands
	import re
        # Create correct backup directory
        if re.compile('\A/.*').search(backup_dir):
                backup_home = backup_dir
        else:
                backup_home = '%s%s' % (backup_store_path, backup_dir)

	# Mounting the Backup to the tmp dir.
	mount_command = '%s %s %s' % (mount_path, backup_source, mount_tmp_dir)
	mount_command_exec = commands.getstatusoutput(mount_command)

	# Backup data from mounted directory.
        backup_command = '%s %s %s %s %s' % (rdiff_backup_path, includes, excludes, mount_tmp_dir, backup_home)
        backup_exec = commands.getstatusoutput(backup_command)
	
	# Unmount the directory from mount_tmp_dir.
	#unmount_command = '%s %s' % (unmount_path, mount_tmp_dir)
	#unmount_command_exec = commands.getstatusoutput(unmount_command)
	
	# return Output Value
	return mount_command_exec   


# Stale incremental Cleanup #
def remove_backups(rdiff_backup_path, backup_window, backup_store_path, backup_dir):
        import commands
        import re
        # Create correct backup directory
        if re.compile('\A/.*').search(backup_dir):
                backup_home = backup_dir
        else:
                backup_home = '%s%s' % (backup_store_path, backup_dir)
	# Remove Stale backups
	remove_backup_command = '%s --remove-older-than %s --force %s' % (rdiff_backup_path, backup_window, backup_home)
	remove_backup_exec = commands.getstatusoutput(remove_backup_command)
	return remove_backup_exec

### MAIN LOOP ###

# Read in configuration file
getconfig = conf_read(config_php)

# Define configuration variables
rdiffpath = getconfig['RDIFF_BACKUP']
sudopath = getconfig['SUDO']
backupstore = getconfig['BACKUP_STORE_DIR']
mounttmpdir = getconfig['MOUNT_TEMP_DIR']
dloadlocation = getconfig['DOWNLOAD_LOCATION']
mountpath = getconfig['MOUNT']
umountpath = getconfig['UMOUNT']
rmpath = getconfig['RM']
dbase = getconfig['config_db']
dbasehost = getconfig['config_dbhost']
dbaseuser = getconfig['config_dbuser']
dbasepass = getconfig['config_dbpass']

if DEBUG == 1:
	print 'Config variable test [DOWNLOAD_LOCATION]: %s\n' % (dloadlocation)

# Collecting SSH Backup 
ssh_backup_query = "SELECT * FROM backupsetup WHERE connect_type='SSH' AND active<>0 ORDER BY sequence"
ssh_backup_query_fetch = db_query(dbasehost, dbaseuser, dbasepass, dbase, ssh_backup_query)

if DEBUG == 1:
	query_ssh_length = 'ssh_backup_query returned %s results\n' % (len(ssh_backup_query_fetch))
	print query_ssh_length
	print ssh_backup_query_fetch

# Running backups for SSH backups first.
for result in ssh_backup_query_fetch:
	backup_ssh = ssh_backup(rdiffpath, result[2], backupstore, result[3], result[4], result[5])
	cleanup_ssh = remove_backups(rdiffpath, result[6], backupstore, result[3])
	if DEBUG == 1:
		print 'Rdiff running SSH backup tasks output: %s' % (backup_ssh[1])
		print 'Rdiff running cleanup tasks (SSH) output: %s' % (cleanup_ssh[1])

# Collecting SAMBA Backup 
samba_backup_query = "SELECT * FROM backupsetup WHERE connect_type='Samba' AND active<>0 ORDER BY sequence"
samba_backup_query_fetch = db_query(dbasehost, dbaseuser, dbasepass, dbase, samba_backup_query)

if DEBUG == 1:
        query_samba_length = 'samba_backup_query returned %s results\n' % (len(samba_backup_query_fetch))
        print query_samba_length
        print samba_backup_query_fetch

# Running backups for SAMBA.
for result in samba_backup_query_fetch:
        backup_samba = samba_backup(rdiffpath, mountpath, result[2], backupstore, result[3], mounttmpdir, result[8], umountpath, result[4], result[5])
	cleanup_samba = remove_backups(rdiffpath, result[6], backupstore, result[3])
        if DEBUG == 1:
                print 'Rdiff running SAMBA backup tasks output: %s' % (backup_samba[1])
                print 'Rdiff running cleanup tasks (SAMBA) output: %s' % (cleanup_samba[1])

# Collecting NFS Backup
nfs_backup_query = "SELECT * FROM backupsetup WHERE connect_type='NFS' AND active<>0 ORDER BY sequence"
nfs_backup_query_fetch = db_query(dbasehost, dbaseuser, dbasepass, dbase, nfs_backup_query)

print nfs_backup_query_fetch

# Running backups for NFS.
for result in nfs_backup_query_fetch:
	backup_nfs = nfs_backup(rdiffpath, mountpath, result[2], backupstore, result[3], mounttmpdir, umountpath, result[4], result[5])
	print backup_nfs
