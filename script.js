// Simple JavaScript for Lost and Found Portal
// Basic form validation

function validateForm() {
    var title = document.getElementById('title');
    var description = document.getElementById('description');
    var location = document.getElementById('location');
    var contact = document.getElementById('contact');
    
    if (title && title.value == '') {
        alert('Please enter item title');
        return false;
    }
    
    if (description && description.value == '') {
        alert('Please enter description');
        return false;
    }
    
    if (location && location.value == '') {
        alert('Please enter location');
        return false;
    }
    
    if (contact && contact.value == '') {
        alert('Please enter contact email');
        return false;
    }
    
    // Simple email check
    if (contact && contact.value.indexOf('@') == -1) {
        alert('Please enter a valid email');
        return false;
    }
    
    return true;
}

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    var menuToggle = document.querySelector('.menu-toggle');
    var nav = document.querySelector('nav');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!nav.contains(e.target) && !menuToggle.contains(e.target)) {
                nav.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });
        
        // Close menu when clicking a link
        var navLinks = document.querySelectorAll('nav a');
        navLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                nav.classList.remove('active');
                menuToggle.classList.remove('active');
            });
        });
    }
});
