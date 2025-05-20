import os
import sqlite3
import secrets

from waitress import serve
from datetime import datetime, timedelta
from flask import Flask, render_template, request, abort, make_response, redirect, url_for
from flask_babel import Babel, gettext as _
from encryption import encrypt_data, decrypt_data, hash_token
from config import DB_PATH, TTL_MINUTES, SECRET_KEY

# Initialize Flask app
app = Flask(__name__)
app.secret_key = SECRET_KEY

# Babel configuration
app.config['BABEL_DEFAULT_LOCALE'] = 'de'
app.config['BABEL_TRANSLATION_DIRECTORIES'] = 'translations'

# Language selection logic
def get_locale():
    return request.cookies.get('lang') or 'de'

# Babel setup with custom locale selector
babel = Babel()
babel.init_app(app, locale_selector=get_locale)

# Make language available in templates
@app.context_processor
def inject_locale():
    return dict(get_locale=get_locale)

# Initialize SQLite database if it does not exist
def init_db():
    if not os.path.exists(DB_PATH):
        with sqlite3.connect(DB_PATH) as conn:
            conn.execute('''
                CREATE TABLE IF NOT EXISTS transactions (
                    id TEXT PRIMARY KEY,
                    token_hash TEXT NOT NULL,
                    content_enc TEXT NOT NULL,
                    created_at TEXT NOT NULL,
                    expires_at TEXT NOT NULL,
                    accessed INTEGER DEFAULT 0
                );
            ''')

def get_db():
    return sqlite3.connect(DB_PATH)

init_db()

# Home page with message creation
@app.route('/', methods=['GET', 'POST'])
def index():
    if request.method == 'POST':
        text = request.form['content']
        if not text.strip():
            return render_template('index.html', error=_("Please enter a message."))

        uid = secrets.token_hex(16)
        token = secrets.token_hex(12)

        enc = encrypt_data(text, token)
        token_hashed = hash_token(token)
        created = datetime.utcnow()
        expires = created + timedelta(minutes=TTL_MINUTES)

        with get_db() as db:
            db.execute('''
                INSERT INTO transactions (id, token_hash, content_enc, created_at, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ''', (uid, token_hashed, enc, created.isoformat(), expires.isoformat()))

        link = f"{request.url_root}view/{uid}?token={token}"
        return render_template('index.html', link=link)

    return render_template('index.html')

# View and decrypt the message
@app.route('/view/<id>')
def view(id):
    token = request.args.get('token', '')
    if not token:
        abort(403)

    with get_db() as db:
        row = db.execute('SELECT token_hash, content_enc, accessed, expires_at FROM transactions WHERE id = ?', (id,)).fetchone()
        if not row:
            return render_template('view.html', error=_("This message is no longer available."))

        token_hash, enc, accessed, expires_at = row
        if accessed or datetime.fromisoformat(expires_at) < datetime.utcnow():
            return render_template('view.html', error=_("This message has expired or was already accessed."))

        if hash_token(token) != token_hash:
            return render_template('view.html', error=_("Invalid token."))

        try:
            decrypted = decrypt_data(enc, token)
        except:
            return render_template('view.html', error=_("Decryption failed."))

        db.execute('DELETE FROM transactions WHERE id = ?', (id,))
        return render_template('view.html', content=decrypted)

# Static pages
@app.route('/about')
def about():
    return render_template('about.html')

@app.route('/contact')
def contact():
    return render_template('contact.html')

@app.route('/privacy')
def privacy():
    return render_template('privacy.html')

@app.route('/donate')
def donate():
    return render_template('donate.html')

# Error handling
@app.errorhandler(404)
def not_found(e):
    return render_template("404.html"), 404

@app.route('/switch_language', methods=['GET', 'POST'])
def switch_language():
    if request.method == 'GET':
        lang = request.args.get('lang', 'de')
    else:
        lang = request.form.get('lang', 'de')
    
    if lang not in ['de', 'en']:
        lang = 'de'
        
    response = make_response(redirect(request.referrer or url_for('index')))
    response.set_cookie('lang', lang, max_age=365*24*3600)  # Cookie valid for 1 year
    return response

if __name__ == "__main__":
    serve(app, host="0.0.0.0", port=5000)
