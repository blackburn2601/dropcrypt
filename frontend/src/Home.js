import React, { useState } from 'react';
import { Box, Container, Typography, TextField, Button, Paper, Snackbar, Alert } from '@mui/material';
import { styled } from '@mui/material/styles';

const StyledPaper = styled(Paper)(({ theme }) => ({
  padding: theme.spacing(4),
  marginTop: theme.spacing(4),
  background: 'rgba(255, 255, 255, 0.9)',
  backdropFilter: 'blur(10px)',
  borderRadius: theme.spacing(2),
  boxShadow: '0 8px 32px rgba(0, 0, 0, 0.1)',
}));

const StyledTextField = styled(TextField)(({ theme }) => ({
  marginBottom: theme.spacing(2),
  '& .MuiOutlinedInput-root': {
    borderRadius: theme.spacing(1),
  },
}));

const StyledButton = styled(Button)(({ theme }) => ({
  marginTop: theme.spacing(2),
  padding: theme.spacing(1.5, 4),
  borderRadius: theme.spacing(1),
  textTransform: 'none',
  fontSize: '1.1rem',
}));

const Home = () => {
  const [message, setMessage] = useState('');
  const [expiry, setExpiry] = useState('24');
  const [notification, setNotification] = useState({ open: false, message: '', severity: 'success' });

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      const response = await fetch('/api/messages', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          content: message,
          expiryHours: parseInt(expiry),
        }),
      });

      if (!response.ok) {
        throw new Error('Failed to create secure drop');
      }

      const data = await response.json();
      setNotification({
        open: true,
        message: 'Secure drop created successfully! Copy your link.',
        severity: 'success',
      });
      setMessage('');
      // Handle the response (e.g., show the secure link)
    } catch (error) {
      setNotification({
        open: true,
        message: 'Failed to create secure drop. Please try again.',
        severity: 'error',
      });
    }
  };

  return (
    <Container maxWidth="md">
      <Box sx={{ textAlign: 'center', mt: 8, mb: 4 }}>
        <Typography variant="h2" component="h1" gutterBottom>
          DropCrypt
        </Typography>
        <Typography variant="h5" color="textSecondary" gutterBottom>
          Create secure, self-destructing messages
        </Typography>
      </Box>

      <StyledPaper elevation={3}>
        <form onSubmit={handleSubmit}>
          <StyledTextField
            fullWidth
            multiline
            rows={6}
            variant="outlined"
            label="Your secure message"
            value={message}
            onChange={(e) => setMessage(e.target.value)}
            placeholder="Type your message here..."
          />
          <StyledTextField
            fullWidth
            type="number"
            variant="outlined"
            label="Message expiry (hours)"
            value={expiry}
            onChange={(e) => setExpiry(e.target.value)}
            inputProps={{ min: 1, max: 168 }}
          />
          <StyledButton
            fullWidth
            variant="contained"
            color="primary"
            type="submit"
            disabled={!message.trim()}
          >
            Create Secure Drop
          </StyledButton>
        </form>
      </StyledPaper>

      <Snackbar
        open={notification.open}
        autoHideDuration={6000}
        onClose={() => setNotification({ ...notification, open: false })}
      >
        <Alert
          onClose={() => setNotification({ ...notification, open: false })}
          severity={notification.severity}
          sx={{ width: '100%' }}
        >
          {notification.message}
        </Alert>
      </Snackbar>
    </Container>
  );
};

export default Home; 