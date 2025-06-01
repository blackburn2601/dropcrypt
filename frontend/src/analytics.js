// Google Analytics Service
export const GA_MEASUREMENT_ID = process.env.REACT_APP_GA_MEASUREMENT_ID || 'GA_MEASUREMENT_ID';

// Initialisiere Google Analytics
export const initializeAnalytics = () => {
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('config', GA_MEASUREMENT_ID, {
      page_title: document.title,
      page_location: window.location.href,
    });
  }
};

// Page View Tracking
export const trackPageView = (path, title) => {
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('config', GA_MEASUREMENT_ID, {
      page_path: path,
      page_title: title,
    });
  }
};

// Event Tracking
export const trackEvent = (action, category = 'general', label = '', value = 0) => {
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', action, {
      event_category: category,
      event_label: label,
      value: value,
    });
  }
};

// Custom Events für DropCrypt
export const analytics = {
  // Message Events
  messageCreated: (expiresIn) => {
    trackEvent('message_created', 'message', expiresIn);
  },
  
  messageViewed: () => {
    trackEvent('message_viewed', 'message');
  },
  
  linkCopied: () => {
    trackEvent('link_copied', 'interaction');
  },
  
  // Navigation Events
  pageView: (pageName) => {
    trackEvent('page_view', 'navigation', pageName);
  },
  
  // Error Events
  errorOccurred: (errorType, errorMessage) => {
    trackEvent('error', 'error', `${errorType}: ${errorMessage}`);
  },
  
  // Performance Events
  apiResponseTime: (endpoint, responseTime) => {
    trackEvent('api_response_time', 'performance', endpoint, responseTime);
  }
}; 