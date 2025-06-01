import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { 
  FaLock, 
  FaHome, 
  FaInfoCircle, 
  FaEnvelope,
  FaGithub,
  FaLinkedin,
  FaTwitter,
  FaPaperPlane,
  FaUser,
  FaCommentDots,
  FaMapMarkerAlt,
  FaPhone,
  FaDiscord,
  FaSlack,
  FaBook,
  FaBars,
  FaTimes
} from 'react-icons/fa';
import './App.css';

function Contact() {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    subject: '',
    message: ''
  });
  const [submitted, setSubmitted] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const toggleMobileMenu = () => {
    setMobileMenuOpen(!mobileMenuOpen);
  };

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    // Here you would typically send the form data to your backend
    console.log('Form submitted:', formData);
    setSubmitted(true);
    setTimeout(() => setSubmitted(false), 3000);
    setFormData({ name: '', email: '', subject: '', message: '' });
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
          <h1>Get In Touch</h1>
          <p>Have questions about DropCrypt? We'd love to hear from you. Reach out and let's talk.</p>
        </section>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))', gap: '2rem', marginBottom: '3rem' }}>
          {/* Contact Form */}
          <div className="message-form">
            <h2 style={{ textAlign: 'center', marginBottom: '2rem', color: 'var(--text-headline)' }}>
              <FaPaperPlane style={{ marginRight: '0.5rem' }} />
              Send us a Message
            </h2>
            
            {submitted && (
              <div style={{ 
                background: 'rgba(40, 167, 69, 0.1)', 
                border: '2px solid rgba(40, 167, 69, 0.3)', 
                color: '#28a745', 
                padding: '1rem', 
                borderRadius: '10px', 
                marginBottom: '1.5rem',
                textAlign: 'center',
                animation: 'slideInUp 0.5s ease'
              }}>
                ✅ Message sent successfully! We'll get back to you soon.
              </div>
            )}

            <form onSubmit={handleSubmit}>
              <div className="form-group">
                <label htmlFor="name">
                  <FaUser />
                  Full Name
                </label>
                <input
                  type="text"
                  id="name"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  placeholder="Enter your full name"
                  required
                />
              </div>

              <div className="form-group">
                <label htmlFor="email">
                  <FaEnvelope />
                  Email Address
                </label>
                <input
                  type="email"
                  id="email"
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  placeholder="your.email@example.com"
                  required
                />
              </div>

              <div className="form-group">
                <label htmlFor="subject">
                  <FaCommentDots />
                  Subject
                </label>
                <select
                  id="subject"
                  name="subject"
                  value={formData.subject}
                  onChange={handleChange}
                  style={{
                    width: '100%',
                    padding: '1rem',
                    border: '2px solid var(--border-input)',
                    borderRadius: '10px',
                    fontSize: '1rem',
                    background: 'var(--bg-input)',
                    color: 'var(--text-primary)',
                    cursor: 'pointer',
                    transition: 'all 0.3s ease'
                  }}
                  required
                >
                  <option value="">Select a subject</option>
                  <option value="general">General Inquiry</option>
                  <option value="bug">Bug Report</option>
                  <option value="feature">Feature Request</option>
                  <option value="security">Security Issue</option>
                  <option value="business">Business Inquiry</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <div className="form-group">
                <label htmlFor="message">
                  <FaCommentDots />
                  Message
                </label>
                <textarea
                  id="message"
                  name="message"
                  value={formData.message}
                  onChange={handleChange}
                  placeholder="Tell us more about your inquiry..."
                  style={{ minHeight: '150px' }}
                  required
                />
              </div>

              <button type="submit" className="btn">
                <FaPaperPlane />
                Send Message
              </button>
            </form>
          </div>

          {/* Contact Information */}
          <div className="message-form">
            <h2 style={{ textAlign: 'center', marginBottom: '2rem', color: 'var(--text-headline)' }}>
              <FaEnvelope style={{ marginRight: '0.5rem' }} />
              Contact Information
            </h2>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '1rem', padding: '1rem', background: 'rgba(102, 126, 234, 0.1)', borderRadius: '10px' }}>
                <div style={{ background: '#667eea', color: 'white', padding: '0.75rem', borderRadius: '10px', fontSize: '1.2rem' }}>
                  <FaEnvelope />
                </div>
                <div>
                  <h4 style={{ margin: '0 0 0.25rem 0', color: 'var(--text-headline-secondary)' }}>Email</h4>
                  <p style={{ margin: 0, color: 'var(--text-secondary)' }}>hello@dropcrypt.com</p>
                </div>
              </div>

              <div style={{ display: 'flex', alignItems: 'center', gap: '1rem', padding: '1rem', background: 'rgba(102, 126, 234, 0.1)', borderRadius: '10px' }}>
                <div style={{ background: '#667eea', color: 'white', padding: '0.75rem', borderRadius: '10px', fontSize: '1.2rem' }}>
                  <FaDiscord />
                </div>
                <div>
                  <h4 style={{ margin: '0 0 0.25rem 0', color: 'var(--text-headline-secondary)' }}>Discord</h4>
                  <p style={{ margin: 0, color: 'var(--text-secondary)' }}>Join our community server</p>
                </div>
              </div>

              <div style={{ display: 'flex', alignItems: 'center', gap: '1rem', padding: '1rem', background: 'rgba(102, 126, 234, 0.1)', borderRadius: '10px' }}>
                <div style={{ background: '#667eea', color: 'white', padding: '0.75rem', borderRadius: '10px', fontSize: '1.2rem' }}>
                  <FaGithub />
                </div>
                <div>
                  <h4 style={{ margin: '0 0 0.25rem 0', color: 'var(--text-headline-secondary)' }}>GitHub</h4>
                  <p style={{ margin: 0, color: 'var(--text-secondary)' }}>Contribute to our open source project</p>
                </div>
              </div>
            </div>

            <div style={{ marginTop: '2rem', padding: '1.5rem', background: 'var(--bg-tertiary)', borderRadius: '15px', border: '2px solid var(--border-primary)' }}>
              <h3 style={{ marginBottom: '1rem', color: 'var(--text-headline-secondary)', textAlign: 'center' }}>Follow Us</h3>
              <div style={{ display: 'flex', justifyContent: 'center', gap: '1rem' }}>
                <a href="#" style={{ 
                  display: 'flex', 
                  alignItems: 'center', 
                  justifyContent: 'center', 
                  width: '50px', 
                  height: '50px', 
                  background: '#1da1f2', 
                  color: 'white', 
                  borderRadius: '50%', 
                  fontSize: '1.5rem',
                  textDecoration: 'none',
                  transition: 'all 0.3s ease'
                }}>
                  <FaTwitter />
                </a>
                <a href="#" style={{ 
                  display: 'flex', 
                  alignItems: 'center', 
                  justifyContent: 'center', 
                  width: '50px', 
                  height: '50px', 
                  background: '#333', 
                  color: 'white', 
                  borderRadius: '50%', 
                  fontSize: '1.5rem',
                  textDecoration: 'none',
                  transition: 'all 0.3s ease'
                }}>
                  <FaGithub />
                </a>
                <a href="#" style={{ 
                  display: 'flex', 
                  alignItems: 'center', 
                  justifyContent: 'center', 
                  width: '50px', 
                  height: '50px', 
                  background: '#0077b5', 
                  color: 'white', 
                  borderRadius: '50%', 
                  fontSize: '1.5rem',
                  textDecoration: 'none',
                  transition: 'all 0.3s ease'
                }}>
                  <FaLinkedin />
                </a>
              </div>
            </div>
          </div>
        </div>

        <div className="features">
          <div className="feature-card">
            <div className="feature-icon">
              <FaCommentDots />
            </div>
            <h3>Quick Response</h3>
            <p>We typically respond to inquiries within 24 hours during business days.</p>
          </div>

          <div className="feature-card">
            <div className="feature-icon">
              <FaLock />
            </div>
            <h3>Secure Communication</h3>
            <p>All communications are handled securely and your privacy is always protected.</p>
          </div>

          <div className="feature-card">
            <div className="feature-icon">
              <FaUser />
            </div>
            <h3>Community Support</h3>
            <p>Join our community forums and Discord server for peer-to-peer help and discussions.</p>
          </div>
        </div>

        <div className="message-form" style={{ marginTop: '3rem', textAlign: 'center' }}>
          <h2 style={{ marginBottom: '2rem', color: 'var(--text-headline)' }}>
            Ready to Start Using DropCrypt?
          </h2>
          <p style={{ marginBottom: '2rem', color: 'var(--text-secondary)', fontSize: '1.1rem' }}>
            Join thousands of users who trust DropCrypt for their secure messaging needs.
          </p>
          <Link to="/" className="btn" style={{ textDecoration: 'none', display: 'inline-flex' }}>
            <FaLock />
            Start Sending Secure Messages
          </Link>
        </div>
      </main>
    </div>
  );
}

export default Contact; 