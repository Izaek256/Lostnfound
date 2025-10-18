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
