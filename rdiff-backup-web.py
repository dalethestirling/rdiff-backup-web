from flask import Flask, request, flash, redirect, url_for, render_template
from flask_blitzdb import BlitzDB
from flask_login import LoginManager, login_required, login_user, logout_user, current_user
import config.model as model
import hashlib
import sh

__version__ = "2.0.0"
__authour__ = 'Dale Stirling (http://github.com/puredistortion)'

# Move to blitzdb
USERNAME = 'admin'
PASSWORD = 'admin'
BLITZDB_DATABASE = r'./rdiff-backup-web.db'
DEBUG=True

app = Flask(__name__)
# remove with db implementation 
app.config.from_object(__name__)

# Set secret key
app.secret_key = '1234'

# Setup the db 
db = BlitzDB(app)

# flask login setup
login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = "login"

@login_manager.user_loader
def load_user(userid):
    try:
        conn = db.connection
        return conn.get(model.Users, {'pk': userid})
    except:
        return None

def hash_password(password, salt):
    t_sha = hashlib.sha512()
    t_sha.update(password)
    t_sha.update(salt)
    return '%s$%s' % (str(t_sha.hexdigest()), salt)

def get_hostname():
    try:
        hostname = sh.hostname()
    except:
        hostname = None

    return str(hostname) if hostname else ''

def authenticate_user(username, password):
    try:
        conn = db.connection
        user_obj = conn.get(model.Users, {'name': username})
        if hash_password(password, user_obj['password'].split('$')[1]) == user_obj['password']:
            return user_obj
        else: 
            return None
    except:
        return None

@app.route('/login', methods=['GET', 'POST'])
def login():
    error_type = None
    error_msg = None

    if request.method == 'POST':

        user_auth = authenticate_user(
            request.form['username'], 
            request.form['password']
        )

        if not user_auth:
            error_type = 'error'
            error_msg = 'Invalid user or password'
        else:
            login_user(user_auth)
            flash('You were logged in')
            return redirect(
                request.args.get('next') or url_for('index')
            )

    return render_template(
        'login.html',
        error_type=error_type,
        error_msg=error_msg
    )

@app.route('/logout')
@login_required
def logout():
    logout_user()
    flash('You were logged out')
    return redirect(url_for('login'))

@app.route('/')
@login_required
def index():
    
    # If admin show admin menu
    if 'administrator' in current_user.keys() and current_user['administrator']:
        administrator = True
    else:
        administrator = False
    
    # Get Backups for user  
    conn = db.connection
    backup_ids = conn.filter(model.Auth, {'user_id': current_user['pk']})

    backup_records = []
    for record in backup_ids:
        try:
            backup_records.append(conn.get(
                model.Backups, 
                {'pk': record['backup_id']}
            ))
        except:
            # TODO: Add logging of disparate Auth records
            pass

    # Render template
    return render_template(
        'index.html',
        administrator=administrator,
        backups=backup_records,
        hostname=get_hostname(),
        username=current_user['username']
    )

@app.route('/backup/<name>')
def backup(name):
    # valid user for backup and logged in

    # Get backup configs 

    # Get increments

    # Render Template 
    backup = 'backup: %s' % str(name)

    return render_template('backup.html', backup=backup)

@app.route('/backup/<name>/<increment>')
def backup_increment(name, increment):
    # valid user for backup and logged in

    # Get backup file tree

    # List file tree in template.
    return 'increment: %s of backup: %s' % (str(increment), str(name))

@app.route('/about')
@login_required
def about():
    return render_template('about.html', version=__version__)

# @app.route('/admin')
# def admin_panel():
#     # Validate if admin user
#     if not is_admin:
#         abort(401)
    
#     if request.method == 'POST':
#         # Process action
#         return 'admin post' 
#     else:
#         # Render template
#         return 'admin'

# application execution 
if __name__ == '__main__': 
    app.run()
