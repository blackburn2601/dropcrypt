import os
import secrets
from datetime import datetime, timedelta
import calendar

from waitress import serve
from flask import Flask, render_template, request, abort, make_response, redirect, url_for
from flask_babel import Babel, gettext as _
from encryption import encrypt_data, decrypt_data, hash_token
from database import init_db, get_db
from config import SECRET_KEY, TTL_MINUTES

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

# Initialize database
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

        with get_db() as conn:
            with conn.cursor() as cur:
                cur.execute("""
                    INSERT INTO transactions (id, token_hash, content_enc, created_at, expires_at)
                    VALUES (%s, %s, %s, %s, %s)
                """, (uid, token_hashed, enc, created, expires))

        link = f"{request.url_root}view/{uid}?token={token}"
        return render_template('index.html', link=link)

    return render_template('index.html')

# View and decrypt the message
@app.route('/view/<id>')
def view(id):
    token = request.args.get('token', '')
    if not token:
        abort(403)

    with get_db() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                SELECT token_hash, content_enc, accessed, expires_at, created_at 
                FROM transactions 
                WHERE id = %s
            """, (id,))
            row = cur.fetchone()
            
            if not row:
                return render_template('view.html', error=_("This message is no longer available."))

            token_hash, enc, accessed, expires_at, created_at = row
            now = datetime.utcnow()
            
            # Check if expired or already accessed
            if accessed or expires_at < now:
                completion_type = 'expired' if expires_at < now else 'accessed'
                # Log to history before deleting
                cur.execute("""
                    INSERT INTO transactions_history (id, created_at, completed_at, completion_type)
                    VALUES (%s, %s, %s, %s)
                """, (id, created_at, now, completion_type))
                cur.execute('DELETE FROM transactions WHERE id = %s', (id,))
                return render_template('view.html', error=_("This message has expired or was already accessed."))

            if hash_token(token) != token_hash:
                return render_template('view.html', error=_("Invalid token."))

            try:
                decrypted = decrypt_data(enc, token)
            except:
                return render_template('view.html', error=_("Decryption failed."))

            # Log successful access to history before deleting
            cur.execute("""
                INSERT INTO transactions_history (id, created_at, completed_at, completion_type)
                VALUES (%s, %s, %s, %s)
            """, (id, created_at, now, 'read'))
            cur.execute('DELETE FROM transactions WHERE id = %s', (id,))
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

@app.route('/legalnotice')
def legalnotice():
    return render_template('legal_notice.html')

@app.route('/privacypolicy')
def privacypolicy():
    return render_template('privacy_policy.html')

@app.route('/termsofservice')
def termsofservice():
    return render_template('terms_of_service.html')

@app.route('/donationnotice')
def donationnotice():
    return render_template('donation_notice.html')

@app.route('/statistics')
def statistics():
    with get_db() as conn:
        with conn.cursor() as cur:
            # Total transactions ever (from history)
            cur.execute("""
                SELECT COUNT(*) FROM transactions_history
            """)
            total_transactions = cur.fetchone()[0]

            # Currently open transactions
            cur.execute("""
                SELECT COUNT(*) FROM transactions 
                WHERE expires_at > NOW() AND accessed = false
            """)
            open_transactions = cur.fetchone()[0]

            # Transactions this month (from both current and history)
            first_day = datetime.now().replace(day=1, hour=0, minute=0, second=0, microsecond=0)
            last_day = (first_day.replace(day=calendar.monthrange(first_day.year, first_day.month)[1])
                       .replace(hour=23, minute=59, second=59))
            
            # Count from history
            cur.execute("""
                SELECT COUNT(*) FROM transactions_history 
                WHERE created_at BETWEEN %s AND %s
            """, (first_day, last_day))
            monthly_history = cur.fetchone()[0]

            # Count from current transactions
            cur.execute("""
                SELECT COUNT(*) FROM transactions 
                WHERE created_at BETWEEN %s AND %s
            """, (first_day, last_day))
            monthly_current = cur.fetchone()[0]

            monthly_transactions = monthly_history + monthly_current

    return render_template('statistics.html', 
                         total_transactions=total_transactions,
                         open_transactions=open_transactions,
                         monthly_transactions=monthly_transactions)

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
