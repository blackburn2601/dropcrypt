import React, { useState, useEffect } from 'react';
import { useParams, useSearchParams, Link } from 'react-router-dom';
import { 
  FaLock, 
  FaHome, 
  FaInfoCircle, 
  FaEnvelope,
  FaShieldAlt,
  FaEye,
  FaExclamationTriangle,
  FaClock,
  FaCheckCircle,
  FaTimesCircle,
  FaBook,
  FaBars,
  FaTimes
} from 'react-icons/fa';
import { analytics } from './analytics';
import './App.css';

function ViewMessage() {
  const { accessToken } = useParams();
  const [searchParams] = useSearchParams();
  const encryptionKey = searchParams.get('token');
  
  const [message, setMessage] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [messageViewed, setMessageViewed] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const toggleMobileMenu = () => {
    setMobileMenuOpen(!mobileMenuOpen);
  };

  useEffect(() => {
    const fetchMessage = async () => {
      if (!accessToken || !encryptionKey) {
        setError('Invalid link: Missing access token or encryption key');
        setLoading(false);
        return;
      }

      try {
        const url = `http://localhost:80/api/messages/view/${accessToken}?token=${encryptionKey}`;
        console.log('Making API call to:', url);
        
        const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
          },
        });

        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);

        if (response.ok) {
          const data = await response.json();
          console.log('Response data:', data);
          setMessage(data);
          setMessageViewed(true);
          
          // Analytics: Message Viewed
          analytics.messageViewed();
        } else {
          // Log the response text for debugging
          const errorText = await response.text();
          console.log('Error response text:', errorText);
          
          if (response.status === 404) {
            setError('Message not found or has already been viewed');
          } else if (response.status === 410) {
            setError('Message has expired');
          } else {
            setError(`Failed to retrieve message (Status: ${response.status})`);
          }
        }
      } catch (error) {
        console.error('Network error:', error);
        setError('Network error: Unable to connect to server');
      } finally {
        setLoading(false);
      }
    };

    fetchMessage();
  }, [accessToken, encryptionKey]);

  return (
    <div className="app">
      <header className="header">
        <div className="header-content">
          <div className="header-top">
            <Link to="/" className="logo">
              <FaLock />
              DropCrypt
            </Link>
            <button className="mobile-menu-toggle" onClick={toggleMobileMenu}>
              {mobileMenuOpen ? <FaTimes /> : <FaBars />}
            </button>
          </div>
          <nav className={`nav ${mobileMenuOpen ? 'open' : ''}`}>
            <Link to="/" onClick={() => setMobileMenuOpen(false)}><FaHome /> Home</Link>
            <Link to="/about" onClick={() => setMobileMenuOpen(false)}><FaInfoCircle /> About</Link>
            <Link to="/contact" onClick={() => setMobileMenuOpen(false)}><FaEnvelope /> Contact</Link>
            <Link to="/documentation" onClick={() => setMobileMenuOpen(false)}><FaBook /> API Docs</Link>
          </nav>
        </div>
      </header>

      <main className="main">
        <section className="hero">
          <h1>Secure Message</h1>
          <p>Viewing encrypted message with one-time access</p>
        </section>

        {loading && (
          <div className="message-form" style={{ textAlign: 'center' }}>
            <div style={{ fontSize: '3rem', marginBottom: '1rem' }}>🔓</div>
            <h2 style={{ color: 'var(--text-primary)', marginBottom: '1rem' }}>Decrypting Message...</h2>
            <p style={{ color: 'var(--text-secondary)' }}>Please wait while we securely decrypt your message.</p>
          </div>
        )}

        {error && (
          <div className="message-form">
            <div style={{ textAlign: 'center', marginBottom: '2rem' }}>
              <div style={{ fontSize: '4rem', color: '#dc3545', marginBottom: '1rem' }}>
                <FaTimesCircle />
              </div>
              <h2 style={{ color: '#dc3545', marginBottom: '1rem' }}>Access Denied</h2>
            </div>
            
            <div style={{ 
              background: 'rgba(220, 53, 69, 0.1)', 
              border: '2px solid rgba(220, 53, 69, 0.3)', 
              color: '#dc3545', 
              padding: '1.5rem', 
              borderRadius: '15px', 
              textAlign: 'center',
              marginBottom: '2rem'
            }}>
              <FaExclamationTriangle style={{ marginRight: '0.5rem' }} />
              {error}
            </div>

            <div style={{ textAlign: 'center' }}>
              <h3 style={{ color: 'var(--text-primary)', marginBottom: '1rem' }}>Why might this happen?</h3>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '1rem', textAlign: 'left' }}>
                <div style={{ background: 'rgba(102, 126, 234, 0.1)', padding: '1rem', borderRadius: '10px' }}>
                  <h4 style={{ color: '#667eea', marginBottom: '0.5rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                    <FaEye /> Already Viewed
                  </h4>
                  <p style={{ color: 'var(--text-secondary)', fontSize: '0.9rem' }}>Messages can only be viewed once for security.</p>
                </div>
                
                <div style={{ background: 'rgba(102, 126, 234, 0.1)', padding: '1rem', borderRadius: '10px' }}>
                  <h4 style={{ color: '#667eea', marginBottom: '0.5rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                    <FaClock /> Expired
                  </h4>
                  <p style={{ color: 'var(--text-secondary)', fontSize: '0.9rem' }}>The message has passed its expiration time.</p>
                </div>
                
                <div style={{ background: 'rgba(102, 126, 234, 0.1)', padding: '1rem', borderRadius: '10px' }}>
                  <h4 style={{ color: '#667eea', marginBottom: '0.5rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                    <FaLock /> Invalid Link
                  </h4>
                  <p style={{ color: 'var(--text-secondary)', fontSize: '0.9rem' }}>The link may be corrupted or incomplete.</p>
                </div>
              </div>
            </div>
          </div>
        )}

        {message && (
          <div className="message-form">
            <div style={{ textAlign: 'center', marginBottom: '2rem' }}>
              <div style={{ fontSize: '4rem', color: '#28a745', marginBottom: '1rem' }}>
                <FaCheckCircle />
              </div>
              <h2 style={{ color: '#28a745', marginBottom: '1rem' }}>Message Decrypted Successfully</h2>
            </div>

            <div style={{ marginBottom: '2rem' }}>
              <label style={{ display: 'block', marginBottom: '0.5rem', color: 'var(--text-primary)', fontWeight: '600' }}>
                <FaShieldAlt style={{ marginRight: '0.5rem' }} />
                Decrypted Message:
              </label>
              <div style={{
                background: 'var(--bg-code)',
                border: '2px solid var(--border-secondary)',
                borderRadius: '15px',
                padding: '1.5rem',
                fontSize: '1.1rem',
                lineHeight: '1.6',
                color: 'var(--text-primary)',
                minHeight: '100px',
                whiteSpace: 'pre-wrap',
                wordBreak: 'break-word'
              }}>
                {message.content || 'No content available'}
              </div>
            </div>

            {message.expiresAt && (
              <div style={{ marginBottom: '2rem' }}>
                <label style={{ display: 'block', marginBottom: '0.5rem', color: 'var(--text-primary)', fontWeight: '600' }}>
                  <FaClock style={{ marginRight: '0.5rem' }} />
                  Message Details:
                </label>
                <div style={{
                  background: 'rgba(102, 126, 234, 0.1)',
                  border: '2px solid rgba(102, 126, 234, 0.2)',
                  borderRadius: '10px',
                  padding: '1rem'
                }}>
                  <p style={{ margin: 0, color: 'var(--text-secondary)' }}>
                    <strong>Expiration:</strong> {new Date(message.expiresAt).toLocaleString()}
                  </p>
                </div>
              </div>
            )}

            <div style={{ 
              background: 'rgba(220, 53, 69, 0.1)', 
              border: '2px solid rgba(220, 53, 69, 0.3)', 
              borderRadius: '15px', 
              padding: '1.5rem',
              textAlign: 'center'
            }}>
              <h3 style={{ color: '#dc3545', marginBottom: '1rem' }}>
                <FaShieldAlt style={{ marginRight: '0.5rem' }} />
                Security Information
              </h3>
              <p style={{ color: '#721c24', margin: 0 }}>
                This message has been automatically and permanently deleted from our servers. 
                The sender will need to create a new encrypted message if they want to share it again.
              </p>
            </div>
          </div>
        )}

        <div className="features">
          <div className="feature-card">
            <div className="feature-icon">
              <FaEye />
            </div>
            <h3>One-Time View</h3>
            <p>Messages are automatically deleted after being viewed once for maximum security.</p>
          </div>

          <div className="feature-card">
            <div className="feature-icon">
              <FaShieldAlt />
            </div>
            <h3>End-to-End Encryption</h3>
            <p>Your message was decrypted locally using the key embedded in your secure link.</p>
          </div>

          <div className="feature-card">
            <div className="feature-icon">
              <FaClock />
            </div>
            <h3>Auto-Expiration</h3>
            <p>Messages self-destruct even if they haven't been viewed yet when they expire.</p>
          </div>
        </div>

        <div className="message-form" style={{ marginTop: '3rem', textAlign: 'center' }}>
          <h2 style={{ marginBottom: '2rem', color: 'var(--text-headline)' }}>
            Want to Send Your Own Secure Message?
          </h2>
          <Link to="/" className="btn" style={{ textDecoration: 'none', display: 'inline-flex' }}>
            <FaLock />
            Create Secure Message
          </Link>
        </div>
      </main>
    </div>
  );
}

export default ViewMessage; 