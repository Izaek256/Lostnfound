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
            // Check if click is outside the menu and menu toggle
            if (nav && nav.classList.contains('active') && 
                !nav.contains(e.target) && 
                menuToggle && !menuToggle.contains(e.target)) {
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
    
    // Initialize read more functionality
    initializeReadMore();
});

// Read More functionality for item descriptions
function initializeReadMore() {
    var descriptions = document.querySelectorAll('.item-description');
    
    descriptions.forEach(function(desc) {
        var fullText = desc.textContent.trim();
        
        // Check if text is longer than 3 lines (approximately 200 characters)
        if (fullText.length > 200) {
            desc.classList.add('truncated');
            desc.setAttribute('data-full-text', fullText);
            
            // Check if read more button already exists
            var existingBtn = desc.parentNode.querySelector('.read-more-btn');
            if (!existingBtn) {
                // Create read more button
                var readMoreBtn = document.createElement('span');
                readMoreBtn.className = 'read-more-btn';
                readMoreBtn.textContent = 'Read more...';
                readMoreBtn.onclick = function() {
                    toggleDescription(desc, readMoreBtn);
                };
                
                desc.parentNode.insertBefore(readMoreBtn, desc.nextSibling);
            }
        }
    });
}

function toggleDescription(descElement, btnElement) {
    if (descElement.classList.contains('truncated')) {
        descElement.classList.remove('truncated');
        btnElement.textContent = 'Show less';
    } else {
        descElement.classList.add('truncated');
        btnElement.textContent = 'Read more...';
    }
}
