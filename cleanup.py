import time
from datetime import datetime
from database import get_db

def cleanup_expired_transactions():
    """Delete transactions where current time is greater than expires_at"""
    current_time = datetime.utcnow()
    
    with get_db() as conn:
        with conn.cursor() as cur:
            # Delete transactions where current time > expires_at
            cur.execute("""
                DELETE FROM transactions 
                WHERE expires_at < %s 
                RETURNING id, expires_at
            """, (current_time,))
            
            deleted = cur.fetchall()
            if deleted:
                for record in deleted:
                    print(f"Deleted transaction {record[0]} (expired at {record[1]})")
                print(f"Total deleted: {len(deleted)} expired transactions")

def run_cleanup_loop():
    """Run the cleanup task every minute"""
    print("Starting cleanup service...")
    
    while True:
        try:
            current_time = datetime.utcnow()
            print(f"Running cleanup check at {current_time}")
            cleanup_expired_transactions()
        except Exception as e:
            print(f"Error during cleanup: {e}")
        
        # Sleep for 1 minute
        time.sleep(60)

if __name__ == "__main__":
    run_cleanup_loop() 