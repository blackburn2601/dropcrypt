import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { 
  FaLock, 
  FaHome, 
  FaInfoCircle, 
  FaEnvelope,
  FaBook,
  FaCode,
  FaRocket,
  FaPlay,
  FaChevronDown,
  FaChevronRight,
  FaCopy,
  FaCheck,
  FaBars,
  FaTimes
} from 'react-icons/fa';
import './App.css';

function Documentation() {
  const [openSections, setOpenSections] = useState({
    createMessage: false,
    viewMessage: false
  });
  const [copiedCode, setCopiedCode] = useState('');
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const toggleMobileMenu = () => {
    setMobileMenuOpen(!mobileMenuOpen);
  };

  const toggleSection = (section) => {
    setOpenSections(prev => ({
      ...prev,
      [section]: !prev[section]
    }));
  };

  const copyCode = async (code, id) => {
    await navigator.clipboard.writeText(code);
    setCopiedCode(id);
    setTimeout(() => setCopiedCode(''), 2000);
  };

  const createMessageExample = `curl -X POST https://api.dropcrypt.org/api/messages/create \\
  -H "Content-Type: application/json" \\
  -d '{
    "content": "This is a confidential message that can be up to 10,000 characters long. Perfect for sharing detailed information, code snippets, or lengthy confidential documents securely.",
    "expiresIn": "1 hour"
  }'`;

  const createResponseExample = `{
  "accessToken": "95f29df2d28dfe8494a58403a7a6d1a86fc7d31a0880eb72a4963664298af14b",
  "token": "a90ed2520d4f0596fb8b359b762219e9919f4201348f1bec87d1700c2f993707",
  "expiresAt": "2023-12-01T15:30:00Z"
}`;

  const viewMessageExample = `curl https://api.dropcrypt.org/api/messages/view/95f29df2d28dfe8494a58403a7a6d1a86fc7d31a0880eb72a4963664298af14b?token=a90ed2520d4f0596fb8b359b762219e9919f4201348f1bec87d1700c2f993707`;

  const viewResponseExample = `{
  "content": "Hello World!",
  "expiresAt": "2023-12-01T15:30:00Z"
}`;

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
          <h1>API Documentation</h1>
          <p>Complete reference for integrating with the DropCrypt API</p>
        </section>

        <div className="message-form" style={{ marginBottom: '2rem' }}>
          <h2 style={{ textAlign: 'center', marginBottom: '2rem', color: 'var(--text-headline)' }}>
            <FaRocket style={{ marginRight: '0.5rem' }} />
            Getting Started
          </h2>
          
          <div style={{ fontSize: '1.1rem', lineHeight: '1.8', color: 'var(--text-tertiary)' }}>
            <p style={{ marginBottom: '1.5rem' }}>
              The DropCrypt API provides simple endpoints for creating and retrieving encrypted messages. 
              All messages are encrypted server-side with AES-256 and automatically deleted after being viewed once or when they expire.
            </p>
            
            <h3 style={{ color: '#667eea', marginBottom: '1rem' }}>Base URL</h3>
            <div style={{ 
              background: 'var(--bg-code)', 
              padding: '1rem', 
              borderRadius: '8px', 
              fontFamily: 'monospace',
              marginBottom: '1.5rem',
              border: '2px solid var(--border-secondary)',
              color: 'var(--text-primary)'
            }}>
              https://api.dropcrypt.org
            </div>
          </div>
        </div>

        <div className="features" style={{ marginBottom: '3rem' }}>
          <div className="feature-card">
            <div className="feature-icon">
              <FaCode />
            </div>
            <h3>RESTful API</h3>
            <p>Simple HTTP endpoints with JSON request/response format for easy integration.</p>
          </div>

          <div className="feature-card">
            <div className="feature-icon">
              <FaLock />
            </div>
            <h3>Secure by Default</h3>
            <p>All messages are encrypted before storage and automatically deleted after viewing.</p>
          </div>

          <div className="feature-card">
            <div className="feature-icon">
              <FaRocket />
            </div>
            <h3>Easy Integration</h3>
            <p>Use any HTTP client or programming language to integrate with the DropCrypt API.</p>
          </div>
        </div>

        {/* API Endpoints */}
        <div style={{ marginBottom: '3rem' }}>
          <h2 style={{ marginBottom: '2rem', color: 'var(--text-headline)', textAlign: 'center' }}>
            <FaBook style={{ marginRight: '0.5rem' }} />
            API Endpoints
          </h2>

          {/* Create Message Endpoint */}
          <div className="message-form" style={{ marginBottom: '2rem' }}>
            <div 
              onClick={() => toggleSection('createMessage')}
              style={{ 
                display: 'flex', 
                alignItems: 'center', 
                justifyContent: 'space-between',
                cursor: 'pointer',
                padding: '1rem',
                background: 'rgba(40, 167, 69, 0.1)',
                borderRadius: '10px',
                border: '2px solid rgba(40, 167, 69, 0.3)',
                marginBottom: '1rem'
              }}
            >
              <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
                <span style={{ 
                  background: '#28a745', 
                  color: 'white', 
                  padding: '0.25rem 0.75rem', 
                  borderRadius: '5px', 
                  fontSize: '0.8rem', 
                  fontWeight: 'bold' 
                }}>
                  POST
                </span>
                <span style={{ fontFamily: 'monospace', fontSize: '1.1rem', fontWeight: 'bold' }}>
                  /api/messages/create
                </span>
                <span style={{ color: 'var(--text-secondary)' }}>Create encrypted message</span>
              </div>
              {openSections.createMessage ? <FaChevronDown /> : <FaChevronRight />}
            </div>

            {openSections.createMessage && (
              <div style={{ padding: '1rem 0' }}>
                <h4 style={{ color: 'var(--text-headline-secondary)', marginBottom: '1rem' }}>Description</h4>
                <p style={{ color: 'var(--text-secondary)', marginBottom: '1.5rem' }}>
                  Creates a new encrypted message with specified expiration time. The message is encrypted server-side with AES-256 before storage.
                </p>

                <h4 style={{ color: 'var(--text-headline-secondary)', marginBottom: '1rem' }}>Request Body</h4>
                <div style={{ marginBottom: '1.5rem' }}>
                  <div style={{ 
                    background: 'var(--bg-code)', 
                    padding: '1rem', 
                    borderRadius: '8px', 
                    border: '2px solid var(--border-secondary)',
                    position: 'relative'
                  }}>
                    <button 
                      onClick={() => copyCode('{\n  "content": "Your secret message",\n  "expiresIn": "1 hour"\n}', 'request')}
                      style={{ 
                        position: 'absolute', 
                        top: '0.5rem', 
                        right: '0.5rem', 
                        background: 'none', 
                        border: 'none', 
                        cursor: 'pointer',
                        color: '#667eea'
                      }}
                    >
                      {copiedCode === 'request' ? <FaCheck /> : <FaCopy />}
                    </button>
                    <pre style={{ margin: 0, fontFamily: 'monospace', fontSize: '0.9rem', color: 'var(--text-primary)' }}>
{`{
  "content": "Your secret message",
  "expiresIn": "1 hour"
}`}
                    </pre>
                  </div>
                  <div style={{ marginTop: '1rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>
                    <strong>Fields:</strong>
                    <ul style={{ marginTop: '0.5rem', paddingLeft: '1.5rem' }}>
                      <li><code>content</code> (string, required): The message content to encrypt (max 10,000 characters)</li>
                      <li><code>expiresIn</code> (string, required): Expiration time - "5 minutes", "15 minutes", "30 minutes", "1 hour", "6 hours", "24 hours", or "7 days"</li>
                    </ul>
                  </div>
                </div>

                <h4 style={{ color: 'var(--text-headline-secondary)', marginBottom: '1rem' }}>Example Request</h4>
                <div style={{ marginBottom: '1.5rem' }}>
                  <div style={{ 
                    background: 'var(--bg-code)', 
                    padding: '1rem', 
                    borderRadius: '8px', 
                    border: '2px solid var(--border-secondary)',
                    position: 'relative'
                  }}>
                    <button 
                      onClick={() => copyCode(createMessageExample, 'create-curl')}
                      style={{ 
                        position: 'absolute', 
                        top: '0.5rem', 
                        right: '0.5rem', 
                        background: 'none', 
                        border: 'none', 
                        cursor: 'pointer',
                        color: '#667eea'
                      }}
                    >
                      {copiedCode === 'create-curl' ? <FaCheck /> : <FaCopy />}
                    </button>
                    <pre style={{ margin: 0, fontFamily: 'monospace', fontSize: '0.8rem', overflow: 'auto', color: 'var(--text-primary)' }}>
{createMessageExample}
                    </pre>
                  </div>
                </div>

                <h4 style={{ color: 'var(--text-headline-secondary)', marginBottom: '1rem' }}>Response (200 OK)</h4>
                <div style={{ 
                  background: 'var(--bg-code)', 
                  padding: '1rem', 
                  borderRadius: '8px', 
                  border: '2px solid var(--border-secondary)',
                  position: 'relative'
                }}>
                  <button 
                    onClick={() => copyCode(createResponseExample, 'create-response')}
                    style={{ 
                      position: 'absolute', 
                      top: '0.5rem', 
                      right: '0.5rem', 
                      background: 'none', 
                      border: 'none', 
                      cursor: 'pointer',
                      color: '#667eea'
                    }}
                  >
                    {copiedCode === 'create-response' ? <FaCheck /> : <FaCopy />}
                  </button>
                  <pre style={{ margin: 0, fontFamily: 'monospace', fontSize: '0.9rem', color: 'var(--text-primary)' }}>
{createResponseExample}
                  </pre>
                </div>
              </div>
            )}
          </div>

          {/* View Message Endpoint */}
          <div className="message-form">
            <div 
              onClick={() => toggleSection('viewMessage')}
              style={{ 
                display: 'flex', 
                alignItems: 'center', 
                justifyContent: 'space-between',
                cursor: 'pointer',
                padding: '1rem',
                background: 'rgba(0, 123, 255, 0.1)',
                borderRadius: '10px',
                border: '2px solid rgba(0, 123, 255, 0.3)',
                marginBottom: '1rem'
              }}
            >
              <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
                <span style={{ 
                  background: '#007bff', 
                  color: 'white', 
                  padding: '0.25rem 0.75rem', 
                  borderRadius: '5px', 
                  fontSize: '0.8rem', 
                  fontWeight: 'bold' 
                }}>
                  GET
                </span>
                <span style={{ fontFamily: 'monospace', fontSize: '1.1rem', fontWeight: 'bold' }}>
                  /api/messages/view/{'{accessToken}'}
                </span>
                <span style={{ color: 'var(--text-secondary)' }}>View encrypted message</span>
              </div>
              {openSections.viewMessage ? <FaChevronDown /> : <FaChevronRight />}
            </div>

            {openSections.viewMessage && (
              <div style={{ padding: '1rem 0' }}>
                <h4 style={{ color: 'var(--text-headline-secondary)', marginBottom: '1rem' }}>Description</h4>
                <p style={{ color: 'var(--text-secondary)', marginBottom: '1.5rem' }}>
                  Retrieves and decrypts a message using access token and encryption token. The message is automatically deleted after viewing.
                </p>

                <h4 style={{ color: 'var(--text-headline-secondary)', marginBottom: '1rem' }}>Parameters</h4>
                <div style={{ marginBottom: '1.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>
                  <ul style={{ paddingLeft: '1.5rem' }}>
                    <li><strong>Path:</strong> <code>accessToken</code> (string, required): The access token for the message</li>
                    <li><strong>Query:</strong> <code>token</code> (string, required): The encryption token for decrypting the message</li>
                  </ul>
                </div>

                <h4 style={{ color: 'var(--text-headline-secondary)', marginBottom: '1rem' }}>Example Request</h4>
                <div style={{ marginBottom: '1.5rem' }}>
                  <div style={{ 
                    background: 'var(--bg-code)', 
                    padding: '1rem', 
                    borderRadius: '8px', 
                    border: '2px solid var(--border-secondary)',
                    position: 'relative'
                  }}>
                    <button 
                      onClick={() => copyCode(viewMessageExample, 'view-curl')}
                      style={{ 
                        position: 'absolute', 
                        top: '0.5rem', 
                        right: '0.5rem', 
                        background: 'none', 
                        border: 'none', 
                        cursor: 'pointer',
                        color: '#667eea'
                      }}
                    >
                      {copiedCode === 'view-curl' ? <FaCheck /> : <FaCopy />}
                    </button>
                    <pre style={{ margin: 0, fontFamily: 'monospace', fontSize: '0.8rem', overflow: 'auto', color: 'var(--text-primary)' }}>
{viewMessageExample}
                    </pre>
                  </div>
                </div>

                <h4 style={{ color: 'var(--text-headline-secondary)', marginBottom: '1rem' }}>Response (200 OK)</h4>
                <div style={{ 
                  background: 'var(--bg-code)', 
                  padding: '1rem', 
                  borderRadius: '8px', 
                  border: '2px solid var(--border-secondary)',
                  position: 'relative',
                  marginBottom: '1rem'
                }}>
                  <button 
                    onClick={() => copyCode(viewResponseExample, 'view-response')}
                    style={{ 
                      position: 'absolute', 
                      top: '0.5rem', 
                      right: '0.5rem', 
                      background: 'none', 
                      border: 'none', 
                      cursor: 'pointer',
                      color: '#667eea'
                    }}
                  >
                    {copiedCode === 'view-response' ? <FaCheck /> : <FaCopy />}
                  </button>
                  <pre style={{ margin: 0, fontFamily: 'monospace', fontSize: '0.9rem', color: 'var(--text-primary)' }}>
{viewResponseExample}
                  </pre>
                </div>

                <h4 style={{ color: 'var(--text-headline-secondary)', marginBottom: '1rem' }}>Error Responses</h4>
                <div style={{ fontSize: '0.9rem', color: 'var(--text-secondary)' }}>
                  <ul style={{ paddingLeft: '1.5rem' }}>
                    <li><strong>404 Not Found:</strong> Message not found or already viewed</li>
                    <li><strong>410 Gone:</strong> Message has expired</li>
                    <li><strong>400 Bad Request:</strong> Invalid parameters</li>
                  </ul>
                </div>
              </div>
            )}
          </div>
        </div>

        <div className="message-form" style={{ marginTop: '3rem', textAlign: 'center' }}>
          <h2 style={{ marginBottom: '2rem', color: 'var(--text-headline)' }}>
            Ready to Build with DropCrypt?
          </h2>
          <p style={{ marginBottom: '2rem', color: 'var(--text-secondary)', fontSize: '1.1rem' }}>
            Start integrating secure messaging into your applications today.
          </p>
          <Link to="/" className="btn" style={{ textDecoration: 'none', display: 'inline-flex' }}>
            <FaLock />
            Try the API
          </Link>
        </div>
      </main>
    </div>
  );
}

export default Documentation; 