import React, { useState, useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, Link, useLocation } from 'react-router-dom';
import { 
  FaLock, 
  FaShieldAlt, 
  FaClock, 
  FaEye, 
  FaPaperPlane, 
  FaCopy, 
  FaCheck, 
  FaHome, 
  FaInfoCircle, 
  FaEnvelope,
  FaBook,
  FaBars,
  FaTimes
} from 'react-icons/fa';
import About from './About';
import Contact from './Contact';
import ViewMessage from './ViewMessage';
import Documentation from './Documentation';
import { analytics, initializeAnalytics, trackPageView } from './analytics';
import './App.css';

// Analytics Hook für Page Tracking
function usePageTracking() {
  const location = useLocation();
  
  useEffect(() => {
    trackPageView(location.pathname, document.title);
  }, [location]);
}

function Home() {
  const [message, setMessage] = useState('');
  const [expiresIn, setExpiresIn] = useState('1 hour');
  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(false);
  const [copied, setCopied] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  // Page Tracking Hook
  usePageTracking();

  const toggleMobileMenu = () => {
    setMobileMenuOpen(!mobileMenuOpen);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    
    const startTime = Date.now();
    
    try {
      const response = await fetch('http://localhost:80/api/messages/create', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          content: message,
          expiresIn: expiresIn
        }),
      });
      
      const responseTime = Date.now() - startTime;
      
      if (response.ok) {
        const data = await response.json();
        setResult(data);
        setMessage('');
        
        // Analytics: Message Created
        analytics.messageCreated(expiresIn);
        analytics.apiResponseTime('/api/messages/create', responseTime);
      } else {
        console.error('Failed to create message');
        analytics.errorOccurred('api_error', `Failed to create message: ${response.status}`);
      }
    } catch (error) {
      console.error('Error:', error);
      analytics.errorOccurred('network_error', error.message);
    } finally {
      setLoading(false);
    }
  };

  const handleCopy = async () => {
    console.log(result.token);
    if (result && result.accessToken && result.token) {
      const link = `http://localhost:3000/view/${result.accessToken}?token=${result.token}`;
      await navigator.clipboard.writeText(link);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
      
      // Analytics: Link Copied
      analytics.linkCopied();
    }
  };

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
          <h1>Secure Message Sharing</h1>
          <p>Send encrypted messages that self-destruct after being read. Your privacy is our priority.</p>
        </section>

        <div className="message-form">
          <h2 style={{ textAlign: 'center', marginBottom: '2rem', color: 'var(--text-headline)' }}>
            <FaPaperPlane style={{ marginRight: '0.5rem' }} />
            Create Secure Drop
          </h2>
          
          <form onSubmit={handleSubmit}>
            <div className="form-group">
              <label htmlFor="message">
                <FaLock />
                Your Secret Message
                <span style={{ 
                  marginLeft: 'auto', 
                  fontSize: '0.9rem', 
                  color: message.length > 9000 ? '#dc3545' : message.length > 8000 ? '#ffc107' : 'var(--text-secondary)',
                  fontWeight: 'normal'
                }}>
                  {message.length.toLocaleString()}/10,000
                </span>
              </label>
              <textarea
                id="message"
                value={message}
                onChange={(e) => setMessage(e.target.value)}
                placeholder="Enter your confidential message here..."
                maxLength={10000}
                required
                style={{ minHeight: '150px' }}
              />
              {message.length > 9000 && (
                <div style={{ 
                  marginTop: '0.5rem', 
                  color: '#dc3545', 
                  fontSize: '0.9rem' 
                }}>
                  ⚠️ Approaching character limit ({10000 - message.length} characters remaining)
                </div>
              )}
              {message.length > 8000 && message.length <= 9000 && (
                <div style={{ 
                  marginTop: '0.5rem', 
                  color: '#ffc107', 
                  fontSize: '0.9rem' 
                }}>
                  📝 Large message ({10000 - message.length} characters remaining)
                </div>
              )}
            </div>

            <div className="form-group">
              <label htmlFor="expires">
                <FaClock />
                Expiration Time
              </label>
              <select
                id="expires"
                value={expiresIn}
                onChange={(e) => setExpiresIn(e.target.value)}
              >
                <option value="5 minutes">5 minutes</option>
                <option value="15 minutes">15 minutes</option>
                <option value="30 minutes">30 minutes</option>
                <option value="1 hour">1 hour</option>
                <option value="6 hours">6 hours</option>
                <option value="24 hours">24 hours</option>
                <option value="7 days">7 days</option>
              </select>
            </div>

            <button type="submit" className="btn" disabled={loading || !message.trim()}>
              {loading ? (
                <>⏳ Encrypting...</>
              ) : (
                <>
                  <FaShieldAlt />
                  Encrypt & Send
                </>
              )}
            </button>
          </form>
        </div>

        {result && (
          <div className="result-section">
            <h3 style={{ marginBottom: '1rem', color: 'var(--text-headline-secondary)' }}>
              ✅ Message Encrypted Successfully!
            </h3>
            <p style={{ marginBottom: '1rem', color: 'var(--text-secondary)' }}>
              Share this secure link with your recipient. The message will be deleted after viewing or when it expires.
            </p>
            <div className="result-content">
              <div className="result-link">
                {`http://localhost:3000/view/${result.accessToken}?token=${result.token}`}
              </div>
              <button onClick={handleCopy} className="copy-btn">
                {copied ? <FaCheck /> : <FaCopy />}
                {copied ? 'Copied!' : 'Copy Link'}
              </button>
            </div>
          </div>
        )}

        <div className="features">
          <div className="feature-card">
            <div className="feature-icon">
              <FaShieldAlt />
            </div>
            <h3>End-to-End Encryption</h3>
            <p>Messages are encrypted with AES-256 encryption before being stored. Only you and your recipient can read them.</p>
          </div>

          <div className="feature-card">
            <div className="feature-icon">
              <FaEye />
            </div>
            <h3>One-Time View</h3>
            <p>Messages are automatically deleted after being read once, ensuring maximum security and privacy.</p>
          </div>

          <div className="feature-card">
            <div className="feature-icon">
              <FaClock />
            </div>
            <h3>Auto-Expiration</h3>
            <p>Set custom expiration times. Messages self-destruct even if they haven't been read yet.</p>
          </div>
        </div>
      </main>
    </div>
  );
}

function App() {
  useEffect(() => {
    // Initialize Analytics on App mount
    initializeAnalytics();
  }, []);

  return (
    <Router>
      <Routes>
        <Route path="/" element={<Home />} />
        <Route path="/about" element={<About />} />
        <Route path="/contact" element={<Contact />} />
        <Route path="/view/:accessToken" element={<ViewMessage />} />
        <Route path="/documentation" element={<Documentation />} />
      </Routes>
    </Router>
  );
}

export default App; 