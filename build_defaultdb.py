from blitzdb import FileBackend
import config.model as model
import hashlib
import os

def hash_password(password, salt):
    t_sha = hashlib.sha512()
    t_sha.update(password)
    t_sha.update(salt)
    return '%s$%s' % (str(t_sha.hexdigest()), salt)

db = FileBackend('./rdiff-backup-web.db')

# Rows for user model
default_admin = model.Users({
	'name': 'admin',
	'email': 'admin@domain.com',
	'username': 'admin',
	'password': hash_password('admin', os.urandom(20).encode('hex')),
	'administrator': True
})
demo_user = model.Users({
	'name': 'test',
	'email': 'test@domain.com',
	'username': 'test',
	'password': hash_password('test', os.urandom(20).encode('hex')),
	'administrator': False
})

# Rows for demo backups
# Define Backups
backup1 = model.Backups({
	'seq':1,
	'name': 'smb_with_user',
	'type': 'SMB',
	'source_user': 'backup',
	'source_pass': 'do_backups',
	'source_dir': '/backup1',
	'keep_for': '1d',
	'active': True
})

backup2 = model.Backups({
	'seq':2,
	'name': 'smb_with_guest',
	'type': 'SMB',
	'source_user': 'guest',
	'source_dir': '/backup2',
	'source_host': '127.0.0.1',
	'keep_for': '1d',
	'active': True
})

backup3 = model.Backups({
	'seq':3,
	'name': 'ssh_backup',
	'type': 'SSH',
	'source_dir': '/mnt/backup3',
	'source_host': '127.0.0.1',
	'keep_for': '1d',
	'active': True
})

backup4 = model.Backups({
	'seq':4,
	'type': 'LOCAL',
	'source_dir': '/mnt/backup4',
	'keep_for': '1d',
	'active': False
})



db.begin()

# Add users
db.save(default_admin)
db.save(demo_user)

# Add backups
db.save(backup1)
db.save(backup2)
db.save(backup3)
db.save(backup4)

db.commit()

# Build Auth Rows
admin_usr = db.get(model.Users, {'username': 'admin'})
test_usr = db.get(model.Users, {'username': 'test'})

backups = db.filter(model.Backups, {})

auth_rows = []

for backup in backups:
	if backup['source_dir'] in ['/mnt/backup3', '/backup1']:
		auth_rows.append(model.Auth({
			'user_id': admin_usr['pk'], 
			'backup_id': backup['pk']
		}))

	auth_rows.append(model.Auth({
			'user_id': test_usr['pk'], 
			'backup_id': backup['pk']
		}))

# Add rows to dict
db.begin()
for row in auth_rows:
	db.save(row)
db.commit()