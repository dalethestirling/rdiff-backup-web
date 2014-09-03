import flask

# Move to blitzdb
USERNAME = 'admin'
PASSWORD = 'admin'

app = flask.Flask(__name__)
# remove with db implementation 
app.config.from_object(__name__)

def logged_in():
    return True

def is_admin():
    return True

@app.before_request
def before_request():
    # init blitzdb here 
    pass

@app.teardown_request
def teardown_request(exception):
    # close blitzdb 
    pass

@app.route('/login', methods=['GET', 'POST'])
def login():
    error = None
    if request.method == 'POST':
        if request.form['username'] != app.config['USERNAME']:
            error = 'Invalid username'
        elif request.form['password'] != app.config['PASSWORD']:
            error = 'Invalid password'
        else:
            session['logged_in'] = True
            flash('You were logged in')
            return redirect(url_for('show_entries'))
    return render_template('login.html', error=error)

@app.route('/logout')
def logout():
    session.pop('logged_in', None)
    flash('You were logged out')
    return redirect(url_for('show_entries'))

@app.route('/')
def index():
    if not logged_in(): 
        error = 'You must be logged in!'
        return render_template('login.html', error=error)

    # Is admin

    # Get Backups for user  

    # Expose admin tab if user exists

    # Render template
    return 'index'

@app.route('/backup/<name>'):
def backup(name):
    # valid user for backup and logged in

    # Get backup configs 

    # Get increments

    # Render Template 
    return 'backup: %s' % str(name)

@app.route('/backup/<name>/<increment>')
def backup_increment(name, increment):
    # valid user for backup and logged in

    # Get backup file tree
     
    # List file tree in template.
    return 'increment: %s of backup: %s' % (str(name), str(increment))

@app.route('/admin')
def admin_panel():
    # Validate if admin user
    if not is_admin:
        abort(401)
    
    if request.method == 'POST':
        # Process action
        return 'admin post' 
    else:
        # Render template
        return 'admin'

