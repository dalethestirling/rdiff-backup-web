<?

######################################################################
#
# rdiff-backup-web-backup-web - a web front end for rdiff-backup-web-backup
#
# (c) June 2004 David Evans (goodevans@gmail.com)
#
# Useless without rdiff-backup-web-backup (c) Ben Escoto (bescoto@stanford.edu)
#
# For more information, see rdiff-backup-webbackupweb.sourceforge.net
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

// the -v2 cuts down on the extraneous info in the latest versions.
$RDIFF_BACKUP = "/usr/bin/rdiff-backup -v2";
$SUDO = "/usr/bin/sudo";
// Make sure that all these directory paths end in a /
$BACKUP_STORE_DIR = "/var/www/html/rdiff-backup-web/dstore/";
$MOUNT_TEMP_DIR   = "/var/www/html/rdiff-backup-web/mnt/";
$DOWNLOAD_LOCATION = "/var/www/html/rdiff-backup-web/tmp/";
// this stuff is here now instead of being in the perl script.
$MOUNT = "/bin/mount";
$UMOUNT = "/bin/umount";
$RM = "/bin/rm";

$config_db      = "rdiff";
$config_dbhost  = "localhost";
$config_dbuser  = "dbuser";
$config_dbpass  = "dbpass";

?>
