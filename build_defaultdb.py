from blitzdb import FileBackend
import config.model as model

db = FileBackend('./rdiff-backup-web.db')

# Rows for user model
default_admin = model.Users({
	'name': 'admin',
	'email': 'admin@domain.com',
	'username': 'admin',
	'password': '21232f297a57a5a743894a0e4a801fc3',
	'administrator': True
})
demo_user = model.Users({
	'name': 'test',
	'email': 'test@domain.com',
	'username': 'test',
	'password': '21232f297a57a5a743894a0e4a801fc3',
	'administrator': False
})

db.begin()

# Add users
db.save(default_admin)
db.save(demo_user)

db.commit()