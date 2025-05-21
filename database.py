import os
import psycopg2
from psycopg2.pool import SimpleConnectionPool
from contextlib import contextmanager

# Database configuration
DB_CONFIG = {
    'dbname': os.environ.get('POSTGRES_DB', 'dropcrypt'),
    'user': os.environ.get('POSTGRES_USER', 'postgres'),
    'password': os.environ.get('POSTGRES_PASSWORD', 'postgres'),
    'host': os.environ.get('POSTGRES_HOST', 'localhost'),
    'port': os.environ.get('POSTGRES_PORT', '5432')
}

# Connection pool
pool = SimpleConnectionPool(
    minconn=1,
    maxconn=10,
    **DB_CONFIG
)

@contextmanager
def get_db():
    """Get a database connection from the pool"""
    conn = pool.getconn()
    try:
        yield conn
        conn.commit()
    except Exception:
        conn.rollback()
        raise
    finally:
        pool.putconn(conn)

def init_db():
    """Initialize the database schema"""
    with get_db() as conn:
        with conn.cursor() as cur:
            # Create transactions table if it doesn't exist
            cur.execute("""
                CREATE TABLE IF NOT EXISTS transactions (
                    id VARCHAR(32) PRIMARY KEY,
                    token_hash VARCHAR(64) NOT NULL,
                    content_enc TEXT NOT NULL,
                    created_at TIMESTAMP NOT NULL,
                    expires_at TIMESTAMP NOT NULL,
                    accessed BOOLEAN DEFAULT FALSE
                );
            """)
            
            # Create index on expires_at for cleanup
            cur.execute("""
                CREATE INDEX IF NOT EXISTS idx_transactions_expires_at 
                ON transactions(expires_at);
            """) 