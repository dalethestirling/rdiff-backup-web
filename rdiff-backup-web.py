import flask
import flask_blitzdb
import flask_login
import config.model as model

# Move to blitzdb
USERNAME = 'admin'
PASSWORD = 'admin'
BLITZDB_DATABASE = r'./rdiff-backup-web.db'
DEBUG=True

app = flask.Flask(__name__)
# remove with db implementation 
app.config.from_object(__name__)

# Set secret key
app.secret_key = '1234'

# Setup the db 
db = flask_blitzdb.BlitzDB(app)

# flask login setup
login_manager = flask_login.LoginManager()
login_manager.init_app(app)

@login_manager.user_loader
def load_user(userid):
    try:
        conn = db.connection
        return conn.get(model.Users, {'id': userid})
    except:
        return None

def hash_password(password):
    return password

def authenticate_user(username, password):
    try:
        conn = db.connection
        user_obj = conn.get(model.Users, {'name': username})
        if hash_password(password) == user_obj['password']:
            return user_obj
        else: 
            return None
    except:
        return None

@app.route('/login', methods=['GET', 'POST'])
def login():
    error = None
    if flask.request.method == 'POST':

        user_auth = authenticate_user(
            flask.request.form['username'], 
            flask.request.form['password']
        )

        if not user_auth:
            error = 'Invalid user or password'
        else:
            flask_login.login_user(user_auth)
            flask.flash('You were logged in')
            return flask.redirect(url_for('index'))

    return flask.render_template('login.html', error=error)

# @app.route('/logout')
# def logout():
#     session.pop('logged_in', None)
#     flash('You were logged out')
#     return redirect(url_for('show_entries'))

@app.route('/')
@flask_login.login_required
def index():
    # Is admin

    # Get Backups for user  

    # Expose admin tab if user exists

    # Render template
    return flask.render_template('base.html')

@app.route('/backup/<name>')
def backup(name):
    # valid user for backup and logged in

    # Get backup configs 

    # Get increments

    # Render Template 
    backup = 'backup: %s' % str(name)

    return flask.render_template('backup.html', backup=backup)

@app.route('/backup/<name>/<increment>')
def backup_increment(name, increment):
    # valid user for backup and logged in

    # Get backup file tree

    # List file tree in template.
    return 'increment: %s of backup: %s' % (str(increment), str(name))

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
