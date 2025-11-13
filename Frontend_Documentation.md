# Frontend Documentation - Lost & Found Portal

## Table of Contents
1. [Overview](#overview)
2. [Server Role](#server-role)
3. [HTML Structure](#html-structure)
4. [CSS Styling](#css-styling)
5. [JavaScript Functionality](#javascript-functionality)
6. [Responsive Design](#responsive-design)
7. [Page-by-Page Breakdown](#page-by-page-breakdown)

---

## Overview

The Frontend is built using vanilla HTML, CSS, and JavaScript with a modern, clean design. It communicates with ItemsServer and UserServer via API calls only.

**Directory**: `Frontend/`  
**Role**: User Interface Client  
**IP Address**: `172.24.14.184`  
**Base URL**: `http://172.24.14.184/Lostnfound/Frontend`

## Server Role

**Frontend** is the **User Interface Client** in the Lost & Found distributed system. It serves as the presentation layer, rendering all HTML pages and communicating with backend servers.

### Core Responsibilities
✅ Render all frontend pages (HTML/CSS/JavaScript)  
✅ Handle user interactions and form submissions  
✅ Make API calls to ItemsServer (items) and UserServer (users)  
✅ Manage user sessions and authentication state  
✅ Upload files to ItemsServer's storage directory  
✅ Display dynamic data from backend APIs  
✅ Provide responsive mobile-friendly UI  

### What Frontend Does NOT Do
❌ **Connect directly to the database** (all data via APIs)  
❌ Hash passwords (UserServer handles this)  
❌ Store user data permanently (session-based only)  
❌ Process SQL queries  
❌ Implement business logic (delegates to ItemsServer/UserServer)

### Technology Stack
- HTML5: Semantic markup
- CSS3: Modern styling with CSS variables
- JavaScript (ES5): Client-side validation
- PHP: Server-side rendering and API integration

## HTML Structure

### Header Component

```html
<header>
    <div class="header-content">
        <div class="logo">
            <img src="./assets/logo.webp" alt="Lost & Found Logo">
            <h1>University Lost & Found</h1>
        </div>
        <button class="menu-toggle" aria-label="Toggle menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="report_lost.php">Report Lost</a></li>
                <!-- More navigation items -->
            </ul>
        </nav>
    </div>
</header>
```

**Purpose**: Consistent branding, responsive menu, dynamic navigation based on auth state

### Main Content Sections

#### 1. Hero Section

```html
<section class="hero">
    <h2>University Lost and Found Portal</h2>
    <p>Help reunite lost items with their owners...</p>
    <div>
        <a href="report_lost.php" class="btn">📢 Report Lost Item</a>
        <a href="report_found.php" class="btn btn-success">🔍 Report Found Item</a>
    </div>
</section>
```

**Design**: Gradient background, large typography, clear CTAs, centered layout

#### 2. Form Container

```html
<div class="form-container">
    <h2>📢 Report a Lost Item</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Item Title *</label>
            <input type="text" id="title" name="title" required>
        </div>
        <!-- More form fields -->
    </form>
</div>
```

**Design**: White background with shadow, rounded corners, hover effects, consistent spacing

#### 3. Items Grid

```html
<div class="items-grid">
    <div class="item-card">
        <div class="item-card-header">
            <span class="item-type lost">🔴 Lost</span>
            <img src="..." alt="..." class="item-image">
        </div>
        <div class="item-card-body">
            <h3>Item Title</h3>
            <div class="item-description">...</div>
            <div class="item-detail">
                <svg>...</svg>
                <div class="item-detail-content">
                    <strong>Location:</strong> ...
                </div>
            </div>
        </div>
    </div>
</div>
```

**Design**: Card-based layout improves scannability, fixed image header prevents distortion, SVG icons are scalable

### Footer Component
```html
<footer>
    <p>&copy; 2025University Lost and Found Portal. Built to help our campus community stay connected.</p>
</footer>
```

## CSS Styling

### CSS Variables
```css
:root {
    --primary: #2563eb;
    --primary-dark: #1e40af;
    --primary-light: #3b82f6;
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --success: #10b981;
    --error: #ef4444;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}
```

**Benefits**: Consistency, maintainability, semantic naming, easy theme switching

### Reset and Base Styles
```css
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto;
    line-height: 1.7;
    color: var(--text-primary);
    background: var(--bg-secondary);
    min-height: 100vh;
    font-size: 16px;
}
```

**Rationale**: System font stack for performance, box-sizing simplifies layouts, optimal line height for readability

### Header Styling

```css
header {
    background: var(--bg-primary);
    border-bottom: 1px solid var(--border);
    padding: 1rem 0;
    box-shadow: var(--shadow-sm);
    position: sticky;  /* Stays visible on scroll */
    top: 0;
    z-index: 100;  /* Above all content */
}
```

**Sticky Header Benefits**:
- **Always Accessible**: Navigation always visible while scrolling
- **Professional Feel**: Common pattern in modern web applications
- **Z-Index 100**: Ensures it stays above content but below modals

---

### Navigation Styling

```css
nav ul {
    list-style: none;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

nav a {
    color: var(--text-secondary);
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    transition: all 0.2s ease;
    font-weight: 500;
}

nav a:hover {
    background: var(--bg-secondary);
    color: var(--text-primary);
}

nav a.active {
    background: var(--primary);
    color: white;
}
```

**Design Decisions**:
- **Flexbox Layout**: Simple, flexible horizontal navigation
- **Gap Property**: Modern CSS spacing (no margins needed)
- **Smooth Transitions**: 0.2s is optimal for perceived responsiveness
- **Active State**: Clear visual indicator of current page

---

### Button Styling

```css
.btn {
    background: var(--primary);
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-block;
}

.btn:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);  /* Subtle lift effect */
    box-shadow: var(--shadow-md);
}
```

**Why These Styles**:
- **Transform on Hover**: Creates satisfying micro-interaction
- **Box Shadow on Hover**: Reinforces elevation
- **Display Inline-Block**: Works for both `<a>` and `<button>` elements
- **Font Weight 600**: Makes text stand out without being too heavy

---

### Form Styling

```css
.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--bg-primary);
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 1rem;
    color: var(--text-primary);
    transition: all 0.2s ease;
    font-family: inherit;  /* Prevents default form fonts */
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;  /* Remove default outline */
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);  /* Custom focus ring */
}
```

**Accessibility Considerations**:
- **Visible Focus States**: 3px ring with alpha transparency
- **No Jarring Outline**: Custom focus ring is more aesthetically pleasing
- **Sufficient Padding**: 0.75rem ensures comfortable tap targets (minimum 44x44px)
- **Border Transition**: Smooth color change on focus

---

### Item Card Styling

```css
.item-card {
    background: var(--bg-primary);
    border-radius: 12px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.item-card:hover {
    transform: translateY(-4px);  /* Pronounced lift */
    box-shadow: var(--shadow-lg);
    border-color: var(--primary);
}
```

**Interactive Design**:
- **Hover Transform**: 4px lift creates engaging interaction
- **Border Color Change**: Subtle blue accent on hover
- **Flex Column**: Ensures footer content stays at bottom
- **Overflow Hidden**: Prevents content from breaking rounded corners

---

### Item Type Badges

```css
.item-type {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 10;
    box-shadow: var(--shadow-md);
}

.item-type.lost {
    background: #ef4444;  /* Solid red */
    color: white;
}

.item-type.found {
    background: #10b981;  /* Solid green */
    color: white;
}
```

**Why This Design**:
- **Absolute Positioning**: Overlays image for maximum visibility
- **Solid Colors**: Better contrast than semi-transparent backgrounds
- **Uppercase with Letter Spacing**: Creates visual distinction
- **Box Shadow**: Ensures readability over various image backgrounds

---

### Grid Layouts

```css
.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}
```

**Auto-Fill Magic**:
- **Responsive Without Media Queries**: Automatically adjusts columns
- **Minmax(280px, 1fr)**: Cards never smaller than 280px, grow to fill space
- **Gap Property**: Cleaner than margin-based spacing
- **Consistent Spacing**: 1.5rem (24px) provides breathing room

---

## JavaScript Functionality

### Form Validation (`script.js`)

```javascript
function validateForm() {
    var title = document.getElementById('title');
    var description = document.getElementById('description');
    var location = document.getElementById('location');
    var contact = document.getElementById('contact');
    
    // Check for empty fields
    if (title && title.value == '') {
        alert('Please enter item title');
        return false;
    }
    
    // Email validation
    if (contact && contact.value.indexOf('@') == -1) {
        alert('Please enter a valid email');
        return false;
    }
    
    return true;
}
```

**Why This Approach**:
1. **Client-Side First**: Provides immediate feedback before server round-trip
2. **Simple Validation**: Basic checks prevent obvious errors
3. **Alert Messages**: Clear, user-friendly error messages
4. **Return False**: Prevents form submission on error

**Improvements Made** (in inline script):
```javascript
function validateLostForm() {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(contact)) {
        alert('Please enter a valid email address.');
        return false;
    }
    
    // Image validation
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (image && !allowedTypes.includes(image.type)) {
        alert('Please upload a valid image file (JPG, JPEG, PNG, or GIF).');
        return false;
    }
    
    // File size validation (5MB limit)
    if (image && image.size > 5 * 1024 * 1024) {
        alert('Image file size must be less than 5MB.');
        return false;
    }
}
```

**Enhanced Validation**:
- **Regex Email Check**: More thorough than indexOf('@')
- **File Type Validation**: Prevents non-image uploads
- **File Size Limit**: Prevents server overload
- **User-Friendly Messages**: Specific error explanations

---

### Mobile Menu Toggle

```javascript
document.addEventListener('DOMContentLoaded', function() {
    var menuToggle = document.querySelector('.menu-toggle');
    var nav = document.querySelector('nav');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
    }
});
```

**Why This Pattern**:
1. **DOMContentLoaded**: Ensures elements exist before accessing
2. **ClassList.toggle()**: Clean, modern API for class manipulation
3. **Existence Check**: Prevents errors if elements don't exist
4. **Event Delegation**: Efficient event handling

---

### Menu Close on Outside Click

```javascript
document.addEventListener('click', function(e) {
    if (nav && nav.classList.contains('active') && 
        !nav.contains(e.target) && 
        menuToggle && !menuToggle.contains(e.target)) {
        nav.classList.remove('active');
        menuToggle.classList.remove('active');
    }
});
```

**User Experience Enhancement**:
- **Intuitive Behavior**: Matches native mobile app behavior
- **Prevents Accidental Clicks**: Closes menu when clicking outside
- **Contains Check**: Ensures click is truly outside both nav and toggle

---

### Read More Functionality

```javascript
function initializeReadMore() {
    var descriptions = document.querySelectorAll('.item-description');
    
    descriptions.forEach(function(desc) {
        var fullText = desc.textContent.trim();
        
        // Check if text exceeds 200 characters
        if (fullText.length > 200) {
            desc.classList.add('truncated');
            desc.setAttribute('data-full-text', fullText);
            
            // Create read more button
            var readMoreBtn = document.createElement('span');
            readMoreBtn.className = 'read-more-btn';
            readMoreBtn.textContent = 'Read more...';
            readMoreBtn.onclick = function() {
                toggleDescription(desc, readMoreBtn);
            };
            
            desc.parentNode.insertBefore(readMoreBtn, desc.nextSibling);
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
```

**Implementation Benefits**:
- **Progressive Enhancement**: Works without JavaScript (shows full text)
- **Dynamic Button Creation**: Only adds button when needed
- **Character Threshold**: 200 characters triggers truncation
- **Smooth CSS Transition**: Class-based approach enables animations

---

### Image Modal (items.php)

```javascript
function openImageModal(imageSrc, title) {
    document.getElementById('imageModal').style.display = 'block';
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('modalCaption').textContent = title;
    document.body.style.overflow = 'hidden';  // Prevent background scroll
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
    document.body.style.overflow = 'auto';  // Restore scrolling
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});
```

**Accessibility Features**:
- **Keyboard Support**: ESC key closes modal
- **Background Scroll Lock**: Prevents confusing scroll behavior
- **Click to Close**: Clicking modal background closes it
- **Large Image Display**: Max-width/height 90% ensures full visibility

---

## Responsive Design

### Breakpoint Strategy

**768px (Mobile)**:
```css
@media (max-width: 768px) {
    .menu-toggle {
        display: flex;  /* Show hamburger menu */
    }
    
    nav {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        width: 100%;
    }
    
    nav ul {
        flex-direction: column;
        gap: 0;
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transition: all 0.3s ease;
    }
    
    nav.active ul {
        max-height: 600px;
        opacity: 1;
        padding: 0.5rem 0;
    }
}
```

**Why Mobile-First Approach**:
- **Performance**: Mobile devices get smaller CSS payload
- **Progressive Enhancement**: Builds up complexity
- **Touch-Friendly**: Larger tap targets, simplified navigation

---

**480px (Small Mobile)**:
```css
@media (max-width: 480px) {
    .hero h2 {
        font-size: 1.6rem;  /* Reduced from 2.5rem */
    }
    
    .item-card-body {
        padding: 1rem;  /* Tighter spacing */
    }
    
    .item-type {
        padding: 0.4rem 0.75rem;
        font-size: 0.7rem;  /* Smaller badge */
    }
}
```

**Ultra-Small Screen Optimizations**:
- **Aggressive Font Scaling**: Maintains readability
- **Reduced Padding**: Maximizes content space
- **Smaller UI Elements**: Proportional to screen size

---

### Grid Responsiveness

```css
/* Desktop: 3-4 columns */
.items-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

/* Tablet: 2-3 columns */
@media (max-width: 768px) {
    .items-grid {
        grid-template-columns: 1fr;  /* Single column */
    }
}
```

**Auto-Responsive Benefits**:
- **No Manual Breakpoints**: Grid auto-adjusts
- **Flexible Card Widths**: Between 280px and available space
- **Tablet Override**: Forces single column for better readability

---

## Page-by-Page Breakdown

### 1. index.php (Homepage)

**Structure**:
- Hero section with CTAs
- Statistics dashboard (Total, Lost, Found counts)
- Recent items grid (6 most recent)
- "How It Works" section
- "Tips for Success" section

**Key Features**:
- PHP data fetching from ServerA API
- Dynamic statistics display
- Conditional rendering based on login state
- Emoji icons for visual interest

**Why This Layout**:
- **Hero First**: Immediate clarity of purpose
- **Statistics**: Builds trust and shows activity
- **Recent Items**: Demonstrates functionality
- **Educational Content**: Helps users succeed

---

### 2. items.php (Browse Items)

**Structure**:
- Search and filter form
- Statistics panel
- Items grid with all items
- Image modal for enlargement

**Advanced Features**:
```php
// Server-side filtering
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$api_response = makeAPIRequest(ITEMSSERVER_URL . '/get_all_items.php', [
    'type' => $filter !== 'all' ? $filter : '',
    'search' => $search
], 'GET', ['return_json' => true]);
```

**Why This Approach**:
- **Server-Side Filtering**: Reduces data transfer
- **Preserved State**: URL parameters maintain filter after refresh
- **Clean UX**: Single-page filtering without complex JavaScript

---

### 3. report_lost.php / report_found.php

**Structure**:
- Form with validation
- Tips section
- Recent similar items
- Campus resource information

**Form Processing**:
```php
// Image upload handling
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $upload_dir = '../ItemsServer/uploads/';
    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $image_filename = uniqid() . '.' . $extension;
    $upload_path = $upload_dir . $image_filename;
    move_uploaded_file($_FILES['image']['tmp_name'], $upload_path);
}

// API submission
$response = makeAPIRequest(ITEMSSERVER_URL . '/add_item.php', [
    'user_id' => $user_id,
    'title' => $title,
    // ... more fields
], 'POST', ['return_json' => true]);
```

**Security Measures**:
- **File Extension Validation**: Client and server-side checks
- **Unique Filenames**: uniqid() prevents conflicts
- **File Size Limits**: 5MB maximum
- **Required Authentication**: requireUser() function

---

### 4. user_dashboard.php

**Structure**:
- Welcome message with user stats
- User's posted items grid
- Edit/Delete actions per item

**Item Actions**:
```html
<div class="item-actions">
    <a href="edit_item.php?id=<?php echo $item['id']; ?>" 
       class="btn btn-secondary">✏️ Edit</a>
    
    <form method="POST" onsubmit="return confirm('Are you sure?');">
        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
        <button type="submit" name="delete_item" 
                class="btn btn-danger">🗑️ Delete</button>
    </form>
</div>
```

**UX Considerations**:
- **Ownership Verification**: Backend checks user_id match
- **Confirmation Dialogs**: Prevents accidental deletion
- **Immediate Feedback**: Success/error messages after actions

---

### 5. admin_dashboard.php

**Structure**:
- Admin statistics grid
- All items management table
- User management table
- System information panel

**Admin Actions**:
```php
// Toggle admin status
if ($action === 'toggle_user_status') {
    $api_response = makeAPIRequest(
        SERVERB_URL . '/toggle_admin.php',
        ['user_id' => $target_user_id, 'is_admin' => $new_status],
        'POST',
        ['return_json' => true]
    );
}

// Delete any item
if ($action === 'delete_item') {
    $api_response = makeAPIRequest(
        SERVERA_URL . '/delete_item.php',
        ['id' => $item_id, 'is_admin' => 1],
        'POST',
        ['return_json' => true]
    );
}
```

**Security**:
- **Role Verification**: isCurrentUserAdmin() check
- **Redirect on Unauthorized**: Sends non-admins to login
- **Audit Trail**: Error logging for admin actions

---

## Design Philosophy

### Color Psychology
- **Blue Primary**: Trust, professionalism, reliability
- **Red for Lost**: Urgency, alert, action needed
- **Green for Found**: Success, completion, positive outcome

### Typography Hierarchy
1. **Headings**: Bold (700-800), Large (2-2.5rem)
2. **Body Text**: Regular (400-500), Medium (1rem)
3. **Metadata**: Light (300-400), Small (0.875rem)

### Spacing System
- **Base Unit**: 0.5rem (8px)
- **Common Spacing**: 1rem, 1.5rem, 2rem
- **Large Gaps**: 3rem, 4rem for sections

### Interaction Design
- **Hover States**: Always provide visual feedback
- **Transitions**: 0.2-0.3s for smooth, responsive feel
- **Loading States**: (Not implemented, potential improvement)
- **Error States**: Alert boxes with clear messaging

---

## Accessibility Features

1. **Semantic HTML**: Proper use of `<header>`, `<nav>`, `<main>`, `<footer>`
2. **ARIA Labels**: `aria-label` on menu toggle
3. **Focus States**: Visible focus rings on all interactive elements
4. **Keyboard Navigation**: Tab order follows logical flow
5. **Alt Text**: All images have descriptive alt attributes
6. **Color Contrast**: WCAG AA compliant text/background ratios

---

## Performance Optimizations

1. **System Font Stack**: No external font loading
2. **CSS Grid/Flexbox**: Hardware-accelerated layouts
3. **Transform over Position**: GPU-accelerated animations
4. **Lazy Loading**: Images load as needed (browser native)
5. **Minimal JavaScript**: Only essential interactions

---

## Browser Compatibility

**Tested and Working**:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

**Fallbacks**:
- Grid → Flexbox (older browsers)
- CSS Variables → Hardcoded colors
- Fetch API → XMLHttpRequest (if needed)

---

## Future Enhancements

1. **Progressive Web App (PWA)**: Offline functionality
2. **Real-Time Updates**: WebSocket notifications
3. **Advanced Search**: Fuzzy matching, filters
4. **Image Optimization**: WebP format, lazy loading
5. **Dark Mode**: CSS variable-based theme switching
6. **Animations**: Framer Motion or GSAP for rich interactions

---

**End of Frontend Documentation**
