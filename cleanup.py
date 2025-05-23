import os
import time
import psycopg2
from datetime import datetime, timedelta
import logging

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

def get_db():
    """Get database connection."""
    return psycopg2.connect(
        dbname=os.environ.get('POSTGRES_DB', 'dropcrypt'),
        user=os.environ.get('POSTGRES_USER', 'dropcrypt'),
        password=os.environ.get('POSTGRES_PASSWORD', 'dropcrypt'),
        host=os.environ.get('POSTGRES_HOST', 'localhost')
    )

def cleanup_expired():
    """Clean up expired transactions and log them to history."""
    with get_db() as conn:
        with conn.cursor() as cur:
            now = datetime.utcnow()
            
            # First, identify expired transactions
            cur.execute("""
                SELECT id, created_at
                FROM transactions 
                WHERE expires_at < %s
            """, (now,))
            expired = cur.fetchall()
            
            if expired:
                # Log expired transactions to history
                cur.executemany("""
                    INSERT INTO transactions_history (id, created_at, completed_at, completion_type)
                    VALUES (%s, %s, %s, 'expired')
                """, [(id, created_at, now) for id, created_at in expired])
                
                # Then delete them
                cur.execute("""
                    DELETE FROM transactions 
                    WHERE expires_at < %s
                """, (now,))
                
                logging.info(f"Cleaned up {len(expired)} expired transactions")
            else:
                logging.info("No expired transactions found")

def main():
    """Main cleanup loop."""
    logging.info("Starting cleanup service")
    
    while True:
        try:
            cleanup_expired()
        except Exception as e:
            logging.error(f"Error during cleanup: {e}")
        
        # Sleep for 5 minutes
        time.sleep(300)

if __name__ == "__main__":
    main() 