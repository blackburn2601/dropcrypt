// Theme toggle functionality
document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.querySelector('.theme-toggle');
    const html = document.documentElement;
    
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
    }

    // Language dropdown functionality
    function toggleLangDropdown(event) {
        event.stopPropagation();
        const dropdown = event.currentTarget.nextElementSibling;
        const isExpanded = event.currentTarget.getAttribute('aria-expanded') === 'true';
        
        // Close all other dropdowns
        document.querySelectorAll('.lang-dropdown-content').forEach(content => {
            if (content !== dropdown) {
                content.classList.remove('show');
                content.previousElementSibling.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Toggle current dropdown
        dropdown.classList.toggle('show');
        event.currentTarget.setAttribute('aria-expanded', !isExpanded);
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.lang-dropdown')) {
            document.querySelectorAll('.lang-dropdown-content').forEach(content => {
                content.classList.remove('show');
                content.previousElementSibling.setAttribute('aria-expanded', 'false');
            });
        }
    });

    // Attach click handler to language dropdown buttons
    document.querySelectorAll('.lang-dropdown-btn').forEach(btn => {
        btn.addEventListener('click', toggleLangDropdown);
    });
});

// Copy link functionality
function copyLink() {
    const shareLink = document.getElementById('shareLink');
    const copySuccess = document.getElementById('copySuccess');
    
    if (shareLink && copySuccess) {
        shareLink.select();
        document.execCommand('copy');
        
        copySuccess.classList.add('visible');
        setTimeout(() => {
            copySuccess.classList.remove('visible');
        }, 2000);
    }
}

// Show loader functionality
function showLoader(event) {
    event.preventDefault();
    const form = event.target;
    const content = form.content.value.trim();
    
    if (!content) {
        return false;
    }

    const loader = document.getElementById('loader');
    if (loader) {
        loader.classList.add('active');
        
        setTimeout(() => {
            form.submit();
        }, 700);
    }
} 