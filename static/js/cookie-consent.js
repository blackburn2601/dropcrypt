class CookieConsent {
    constructor() {
        this.banner = document.querySelector('.cookie-banner');
        this.settingsPanel = document.querySelector('.cookie-settings');
        
        // Check if we already have consent
        if (!this.getCookie('cookie_consent')) {
            this.showBanner();
        }
        
        this.initializeEventListeners();
    }

    showBanner() {
        if (this.banner) {
            this.banner.classList.add('show');
        }
    }

    hideBanner() {
        if (this.banner) {
            this.banner.classList.remove('show');
        }
    }

    toggleSettings() {
        if (this.settingsPanel) {
            this.settingsPanel.classList.toggle('show');
        }
    }

    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    setCookie(name, value, days = 365) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = `expires=${date.toUTCString()}`;
        document.cookie = `${name}=${value};${expires};path=/;SameSite=Lax`;
    }

    saveConsent(type) {
        const consent = {
            necessary: true,
            analytics: type === 'all' || document.getElementById('analytics')?.checked,
            marketing: type === 'all' || document.getElementById('marketing')?.checked,
            timestamp: new Date().toISOString(),
            type: type
        };

        this.setCookie('cookie_consent', JSON.stringify(consent));
        this.hideBanner();
    }

    initializeEventListeners() {
        document.getElementById('acceptAll')?.addEventListener('click', () => this.saveConsent('all'));
        document.getElementById('acceptSelected')?.addEventListener('click', () => this.saveConsent('selected'));
        document.getElementById('showSettings')?.addEventListener('click', () => this.toggleSettings());
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new CookieConsent());
} else {
    new CookieConsent();
} 