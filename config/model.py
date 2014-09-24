from blitzdb import Document

class Users(Document):

    # Extended for flask-login support
    def is_authenticated(self):
        return True
 
    def is_active(self):
        return True
 
    def is_anonymous(self):
        return False

    def get_id(self):
        return self['pk']

    def __repr__(self):
        return '<User %r>' % (self['username'])

class Backups(Document):
    pass

class Auth(Document):
    pass