import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { 
  FaLock, 
  FaShieldAlt, 
  FaClock, 
  FaEye, 
  FaHome, 
  FaInfoCircle, 
  FaEnvelope,
  FaGithub,
  FaLinkedin,
  FaTwitter,
  FaCode,
  FaServer,
  FaDatabase,
  FaBook,
  FaBars,
  FaTimes
} from 'react-icons/fa';
import './App.css';

function About() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const toggleMobileMenu = () => {
    setMobileMenuOpen(!mobileMenuOpen);
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
          <h1>About DropCrypt</h1>
          <p>Learn more about our mission to provide secure, private message sharing for everyone.</p>
        </section>

        <div className="message-form">
          <h2 style={{ textAlign: 'center', marginBottom: '2rem', color: 'var(--text-headline)' }}>
            <FaInfoCircle style={{ marginRight: '0.5rem' }} />
            Our Mission
          </h2>
          
          <div style={{ fontSize: '1.1rem', lineHeight: '1.8', color: 'var(--text-tertiary)' }}>
            <p style={{ marginBottom: '1.5rem' }}>
              DropCrypt was born from a simple belief: <strong>privacy should be accessible to everyone</strong>. 
              In an age where digital communication is constantly monitored and data breaches are commonplace, 
              we provide a safe haven for your most sensitive conversations.
            </p>
            
            <p style={{ marginBottom: '1.5rem' }}>
              Our platform uses military-grade AES-256 encryption to protect your messages, ensuring that only 
              you and your intended recipient can read them. With features like one-time viewing and automatic 
              expiration, your secrets truly disappear after being shared.
            </p>
            
            <p>
              Whether you're a journalist protecting sources, a business sharing confidential information, 
              or simply someone who values their privacy, DropCrypt provides the security you need with 
              the simplicity you want.
            </p>
          </div>
        </div>

        <div className="features">
          <div className="feature-card">
            <div className="feature-icon">
              <FaCode />
            </div>
            <h3>Open Source</h3>
            <p>Our code is open source and auditable. Transparency builds trust, and we have nothing to hide.</p>
          </div>

          <div className="feature-card">
            <div className="feature-icon">
              <FaServer />
            </div>
            <h3>Zero Knowledge</h3>
            <p>We can't read your messages even if we wanted to. Your data is encrypted before it reaches our servers.</p>
          </div>

          <div className="feature-card">
            <div className="feature-icon">
              <FaDatabase />
            </div>
            <h3>No Tracking</h3>
            <p>We don't store personal information, track users, or sell data. Your privacy is not our product.</p>
          </div>
        </div>

        <div className="message-form" style={{ marginTop: '3rem' }}>
          <h2 style={{ textAlign: 'center', marginBottom: '2rem', color: 'var(--text-headline)' }}>
            <FaShieldAlt style={{ marginRight: '0.5rem' }} />
            How It Works
          </h2>
          
          <div className="features" style={{ marginTop: '0' }}>
            <div style={{ background: 'rgba(102, 126, 234, 0.1)', padding: '1.5rem', borderRadius: '15px', border: '2px solid rgba(102, 126, 234, 0.2)' }}>
              <h4 style={{ color: '#667eea', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                <span style={{ background: '#667eea', color: 'white', borderRadius: '50%', width: '24px', height: '24px', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '0.8rem' }}>1</span>
                Create Message
              </h4>
              <p style={{ color: 'var(--text-secondary)', lineHeight: '1.6' }}>
                Write your message and choose an expiration time. The message is sent to the server where it's encrypted with AES-256.
              </p>
            </div>

            <div style={{ background: 'rgba(102, 126, 234, 0.1)', padding: '1.5rem', borderRadius: '15px', border: '2px solid rgba(102, 126, 234, 0.2)' }}>
              <h4 style={{ color: '#667eea', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                <span style={{ background: '#667eea', color: 'white', borderRadius: '50%', width: '24px', height: '24px', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '0.8rem' }}>2</span>
                Share Secure Link
              </h4>
              <p style={{ color: 'var(--text-secondary)', lineHeight: '1.6' }}>
                Get a unique link containing the access token and encryption key. Share it through your preferred channel.
              </p>
            </div>

            <div style={{ background: 'rgba(102, 126, 234, 0.1)', padding: '1.5rem', borderRadius: '15px', border: '2px solid rgba(102, 126, 234, 0.2)' }}>
              <h4 style={{ color: '#667eea', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                <span style={{ background: '#667eea', color: 'white', borderRadius: '50%', width: '24px', height: '24px', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '0.8rem' }}>3</span>
                Self-Destruct
              </h4>
              <p style={{ color: 'var(--text-secondary)', lineHeight: '1.6' }}>
                The message is deleted permanently after being read once or when the expiration time is reached.
              </p>
            </div>
          </div>
        </div>

        <div className="message-form" style={{ marginTop: '3rem', textAlign: 'center' }}>
          <h2 style={{ marginBottom: '2rem', color: 'var(--text-headline)' }}>
            Ready to Send Secure Messages?
          </h2>
          <Link to="/" className="btn" style={{ textDecoration: 'none', display: 'inline-flex' }}>
            <FaLock />
            Start Encrypting
          </Link>
        </div>
      </main>
    </div>
  );
}

export default About; 