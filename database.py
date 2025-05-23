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
    """Initialize the database with required tables."""
    with get_db() as conn:
        with conn.cursor() as cur:
            # Create transactions table
            cur.execute("""
                CREATE TABLE IF NOT EXISTS transactions (
                    id TEXT PRIMARY KEY,
                    token_hash TEXT NOT NULL,
                    content_enc TEXT NOT NULL,
                    created_at TIMESTAMP NOT NULL,
                    expires_at TIMESTAMP NOT NULL,
                    accessed BOOLEAN DEFAULT FALSE
                )
            """)
            
            # Create transactions history table
            cur.execute("""
                CREATE TABLE IF NOT EXISTS transactions_history (
                    id TEXT PRIMARY KEY,
                    created_at TIMESTAMP NOT NULL,
                    completed_at TIMESTAMP NOT NULL,
                    completion_type TEXT NOT NULL
                )
            """)
            
            # Create index on expires_at for cleanup
            cur.execute("""
                CREATE INDEX IF NOT EXISTS idx_transactions_expires_at 
                ON transactions(expires_at);
            """) 