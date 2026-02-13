// ========================================
// APU PLASTIC REDUCTION CHALLENGE
// Main JavaScript File
// ========================================
// PURPOSE: Provides client-side interactivity, form validation, and UI enhancements
// RELATIONSHIPS:
//   - Loaded by: All HTML pages (via <script src="js/script.js"></script>)
//   - Works with: CSS styles, HTML forms, server-side PHP
//   - No dependencies on other JS files (standalone)

// ========================================
// Utility Functions
// ========================================

/**
 * formatNumber(num) - Formats number with thousand separators
 * USAGE: formatNumber(1000) returns "1,000"
 * PURPOSE: Display large numbers in readable format
 */
function formatNumber(num) {
    return num.toLocaleString();
}

/**
 * confirmAction(message) - Shows confirmation dialog
 * PARAMETER: message - Text to display in confirmation box
 * RETURNS: true if user clicks OK, false if user clicks Cancel
 * USAGE: if (confirmAction('Delete this entry?')) { ... delete ... }
 * PURPOSE: Prevents accidental deletion of data
 */
function confirmAction(message) {
    return confirm(message || 'Are you sure you want to proceed?');
}

/**
 * validateForm(formId) - Validates all required fields in a form
 * PARAMETER: formId - HTML ID of the form to validate
 * RETURNS: true if all required fields are filled, false otherwise
 * USAGE: if (validateForm('myForm')) { submitForm(); }
 * 
 * WHAT IT DOES:
 *   1. Gets all [required] input fields in the form
 *   2. Checks if each has a value
 *   3. Highlights empty fields in red
 *   4. Returns true only if all are filled
 * 
 * PURPOSE: Client-side validation before server submission
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.style.borderColor = '#f44336'; // Red border for empty field
        } else {
            input.style.borderColor = '#e0e0e0'; // Normal border for filled field
        }
    });
    
    return isValid;
}

// ========================================
// Page Load Events
// ========================================

/**
 * Auto-hide alerts/messages after 5 seconds
 * 
 * HOW IT WORKS:
 *   1. Selects all elements with class 'alert' (success/error messages)
 *   2. For each alert, waits 5 seconds
 *   3. Fades out opacity to 0 (invisible)
 *   4. Removes element from DOM
 * 
 * BENEFIT: Alerts don't clutter the page permanently
 * USED BY: Success/error messages after form submissions
 */
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        // Set timeout to run after 5000 milliseconds (5 seconds)
        setTimeout(() => {
            // Fade out effect (opacity transition)
            alert.style.opacity = '0';
            // After fade completes, remove element
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

/**
 * toggleMobileMenu() - Toggles mobile navigation menu visibility
 * 
 * HOW IT WORKS:
 *   1. Selects the main navigation element
 *   2. Toggles 'mobile-open' class
 *   3. CSS classes handle showing/hiding based on screen size
 * 
 * PURPOSE: Mobile responsiveness - shows menu on small screens
 */
function toggleMobileMenu() {
    const nav = document.querySelector('.main-nav');
    if (nav) {
        nav.classList.toggle('mobile-open');
    }
}

// ========================================
// Smooth Scrolling
// ========================================

/**
 * Smooth scroll to page sections
 * 
 * HOW IT WORKS:
 *   1. Selects all anchor links (href="#something")
 *   2. Prevents default jump behavior
 *   3. Smoothly scrolls to target section
 *   4. Aligns target to top of viewport
 * 
 * USAGE: <a href="#section-id">Jump to Section</a>
 *        <section id="section-id">Content here</section>
 * 
 * PURPOSE: Improves user experience with smooth transitions
 */
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        // Skip empty anchors
        if (href === '#') return;
        
        e.preventDefault(); // Prevent default jump
        const target = document.querySelector(href);
        if (target) {
            // Scroll smoothly to target element
            target.scrollIntoView({
                behavior: 'smooth', // Smooth animation
                block: 'start'       // Align to top of viewport
            });
        }
    });
});