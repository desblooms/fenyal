/**
 * Theme Switcher JavaScript
 * Handles theme selection and application
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme switcher
    initThemeSwitcher();
});

function initThemeSwitcher() {
    const themeSwitchers = document.querySelectorAll('.theme-option');
    themeSwitchers.forEach(option => {
        option.addEventListener('click', function() {
            const theme = this.getAttribute('data-theme');
            setTheme(theme);
        });
    });
}

function setTheme(theme) {
    // Update the theme in the UI
    const themeLink = document.getElementById('theme-css');
    themeLink.href = `assets/css/themes/${theme}.css`;
    
    // Save the theme preference
    saveThemePreference(theme);
    
    // Update active state in theme picker
    document.querySelectorAll('.theme-option').forEach(option => {
        if (option.getAttribute('data-theme') === theme) {
            option.classList.add('ring-2', 'ring-offset-2', 'ring-primary');
        } else {
            option.classList.remove('ring-2', 'ring-offset-2', 'ring-primary');
        }
    });
}

function saveThemePreference(theme) {
    // Send AJAX request to save theme preference
    fetch('includes/theme_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            theme: theme
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Theme preference saved');
        } else {
            console.error('Error saving theme preference');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Function to apply the current theme
function applyCurrentTheme() {
    const themeLink = document.getElementById('theme-css');
    if (themeLink) {
        const currentTheme = themeLink.getAttribute('href').split('/').pop().replace('.css', '');
        document.querySelectorAll('.theme-option').forEach(option => {
            if (option.getAttribute('data-theme') === currentTheme) {
                option.classList.add('ring-2', 'ring-offset-2', 'ring-primary');
            }
        });
    }
}

// Apply current theme when the theme switcher is loaded
window.addEventListener('load', applyCurrentTheme);