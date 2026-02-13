# APU Plastic Reduction Challenge - Architecture & File Relationships Guide

## Overview
This is a PHP web application for tracking plastic reduction efforts, awarding achievements, and displaying community impact statistics.

---

## 📁 Project Structure & File Relationships

### **Core Configuration Files (includes/)**

#### **1. config.php** - Central Configuration Hub
- **PURPOSE**: Defines all global constants and settings
- **CONTAINS**: 
  - Database credentials (DB_HOST, DB_USER, DB_PASS, DB_NAME)
  - Site name and URL constants
  - Session security settings
  - Timezone configuration
- **DEPENDENCIES**: None
- **USED BY**: Every single file in the application
- **WITHOUT IT**: No file can work; database connection impossible

#### **2. database.php** - Database Connection Management
- **PURPOSE**: Handles database connections and closures
- **FUNCTIONS**:
  - `getDBConnection()`: Creates MySQLi connection with error handling
  - `closeDBConnection($conn)`: Safely closes connections
- **DEPENDENCIES**: Requires config.php (for DB credentials)
- **USED BY**: auth.php, functions.php, all page files
- **WITHOUT IT**: Cannot execute any database queries

#### **3. auth.php** - Authentication & Authorization System
- **PURPOSE**: Manages user login, logout, roles, and session control
- **FUNCTIONS**:
  - `isLoggedIn()`: Checks if user has valid session
  - `hasRole($role)`: Verifies user role (admin/participant)
  - `requireLogin()`: Forces login page redirect if not logged in
  - `requireRole($role)`: Forces role check with redirect
  - `loginUser()`: Creates session after successful authentication
  - `logoutUser()`: Destroys session and logs out user
  - `getCurrentUser()`: Returns array of current user info
- **DEPENDENCIES**: Requires config.php, database.php
- **USED BY**: All protected pages (dashboard, log_entry, certificates, profile)
- **WITHOUT IT**: No access control; anyone could view private data

#### **4. functions.php** - Business Logic & Utility Functions
- **PURPOSE**: Core application logic for calculations, queries, and data validation
- **MAIN FUNCTIONS**:
  - **Input Handling**:
    - `sanitizeInput()`: Cleans user input to prevent XSS attacks
    - `isValidEmail()`: Validates email format
  - **Environmental Data**:
    - `getEnvironmentalFactors()`: Retrieves impact factors (CO2/water per item)
    - `calculateImpact()`: Calculates environmental impact from quantity
  - **Statistics**:
    - `getUserStats()`: Aggregates user's total impact
    - `getCommunityStats()`: Aggregates all users' combined impact
    - `getLeaderboard()`: Ranks top contributors
  - **Certificates**:
    - `checkAndAwardCertificates()`: Auto-awards certificates when criteria met
    - `getUserCertificates()`: Retrieves user's earned certificates
  - **Formatting**:
    - `formatNumber()`: Adds thousand separators (1000 → "1,000")
    - `formatDecimal()`: Formats to 2 decimal places
- **DEPENDENCIES**: Requires database.php
- **USED BY**: Almost every page file for calculations and display
- **WITHOUT IT**: No impact calculations, statistics, or certificate logic

---

### **Authentication Pages**

#### **login.php** - User Login
- **PURPOSE**: Authenticate users with username/email and password
- **WORKFLOW**:
  1. Check if already logged in (redirect to dashboard if yes)
  2. Display login form
  3. On POST: Validate credentials against users table
  4. If valid: Call `loginUser()` to create session
  5. Redirect to dashboard.php
- **DATABASE**: Reads from `users` table
- **DEPENDENCIES**: config, auth, database, functions
- **USED BY**: First page unauthenticated users see; referenced from register.php

#### **register.php** - New User Registration
- **PURPOSE**: Create new user accounts
- **WORKFLOW**:
  1. Check if already logged in (redirect if yes)
  2. Display registration form
  3. On POST: Validate inputs (length, email, duplicate checking)
  4. Hash password with bcrypt
  5. Insert into users table with role='participant'
  6. Show success message (user must log in next)
- **DATABASE**: Inserts into `users` table
- **DEPENDENCIES**: config, auth, database, functions
- **LINKED TO**: login.php (user registers, then logs in)

---

### **Protected User Pages (Require Authentication)**

#### **dashboard.php** - User Home Page
- **PURPOSE**: Shows user's personal impact statistics and recent activity
- **REQUIRES**: `requireLogin()` - user must be logged in
- **DISPLAYS**:
  - User's total items saved, CO2 prevented, water conserved
  - Recent log entries (last 10)
  - Recent certificates (up to 4)
  - Motivational stats
- **DATABASE QUERIES**:
  - `getUserStats()`: Get aggregated user statistics
  - `getUserCertificates()`: Get user's certificates
  - Direct query to `logs` table with JOIN to `environmental_factors`
- **DEPENDENCIES**: config, auth, functions, database
- **LINKS TO**: log_entry.php, certificates.php, profile.php

#### **log_entry.php** - Create Plastic Reduction Log
- **PURPOSE**: Allows users to log plastic items they've saved
- **REQUIRES**: `requireLogin()` - user must be logged in
- **WORKFLOW**:
  1. Display form with item type, quantity, and date
  2. On POST: Validate inputs
  3. Calculate impact using `calculateImpact()`
  4. Insert into `logs` table with calculated co2_saved, water_saved
  5. Call `checkAndAwardCertificates()` for automatic achievements
  6. Show success message with environmental impact
- **DATABASE**:
  - Reads: `environmental_factors` table
  - Writes: `logs` table
  - Reads: `certificates` table (for auto-award check)
  - Writes: `user_certificates` table (if earning cert)
- **JAVASCRIPT**: Client-side calculation preview as user types
- **DEPENDENCIES**: config, auth, functions, database

#### **certificates.php** - View User Achievements
- **PURPOSE**: Display all certificates user has earned
- **REQUIRES**: `requireLogin()` - user must be logged in
- **DISPLAYS**:
  - Grid of earned certificates
  - Certificate details (name, description, award date)
  - Personal message from admin (if any)
  - Motivational message if no certificates yet
- **DATABASE**: 
  - Queries: `user_certificates` table
  - JOINs: `certificates`, `users` tables
- **DEPENDENCIES**: config, auth, functions
- **GAMIFICATION**: Encourages users to log more entries

#### **profile.php** - User Account Management
- **PURPOSE**: Allows users to manage account and change password
- **REQUIRES**: `requireLogin()` - user must be logged in
- **FEATURES**:
  - Display user info (username, email, created date, last login)
  - Update email address
  - Change password (requires current password verification)
  - Show lifetime statistics
- **DATABASE**:
  - Reads: `users` table
  - Reads: Uses `getUserStats()` for statistics
  - Writes: `users` table (email, password_hash updates)
- **DEPENDENCIES**: config, auth, functions, database

#### **index.php** - Public Home Page
- **PURPOSE**: Landing page visible to all users (logged in or guest)
- **DISPLAYS**:
  - Hero section with call-to-action
  - Community statistics (total participants, items, CO2, water)
  - Top 10 leaderboard
  - Navigation links
- **DATABASE**:
  - `getCommunityStats()`: Total community impact
  - `getLeaderboard()`: Top contributors
- **SPECIAL**: Auto-creates default admin account on first run
- **DEPENDENCIES**: config, auth, functions, database

---

### **Admin Pages (Require Admin Role)**

#### **admin/dashboard.php** - Admin Overview
- **PURPOSE**: Shows administrative summary and recent activity
- **REQUIRES**: `requireRole('admin')` - only admins can access
- **DISPLAYS**:
  - Total users, items, CO2, water (community stats)
  - Recent 10 log entries with usernames
- **DATABASE**:
  - `getCommunityStats()`: Overall statistics
  - JOINs: `logs`, `users`, `environmental_factors` tables
- **DEPENDENCIES**: config, auth, functions, database (from parent dir)

#### **admin/users.php** - User Management
- **PURPOSE**: Admins can view and manage user accounts
- **REQUIRES**: `requireRole('admin')`
- **FEATURES**:
  - List all users with their stats
  - Activate/deactivate user accounts
  - Show total entries and items per user
- **DATABASE**:
  - Reads: `users` table with aggregated `logs` data
  - Writes: `users` table (status updates)
- **DEPENDENCIES**: All includes files (with ../ path)

#### **admin/logs.php** - Log Management
- **PURPOSE**: Admins can view all user logs and delete invalid entries
- **REQUIRES**: `requireRole('admin')`
- **FEATURES**:
  - Paginated table of all logs
  - Filter/search capabilities
  - Delete individual log entries
  - Shows user who logged each entry
- **DATABASE**:
  - Reads: `logs` table with JOINs to `users`, `environmental_factors`
  - Deletes: From `logs` table

#### **admin/certificates.php** - Certificate Management
- **PURPOSE**: Admins can create new certificates and award them to users
- **REQUIRES**: `requireRole('admin')`
- **FEATURES**:
  - Create new certificates (manual or auto type)
  - Award certificates to users (with optional message)
  - Delete certificates
  - View all awarded certificates (recent 20)
  - Create auto-certificates that trigger at quantity threshold
- **DATABASE**:
  - Reads/Writes: `certificates` table
  - Reads/Writes: `user_certificates` table
  - Reads: `users` table

#### **admin/reports.php** - Analytics & Reporting
- **PURPOSE**: Generate reports on community activity and environmental impact
- **REQUIRES**: `requireRole('admin')`
- **FEATURES**: (Likely statistics and export functionality)

#### **admin/revoke_certificate.php** - Revoke Awards
- **PURPOSE**: Remove certificates from users if needed
- **REQUIRES**: `requireRole('admin')`

---

### **Frontend Files**

#### **css/style.css** - Main Stylesheet
- **PURPOSE**: All styling and layout for the application
- **CONTAINS**:
  - CSS variables (colors, spacing, typography)
  - Responsive design (mobile, tablet, desktop)
  - Component styles (cards, buttons, forms, tables)
  - Header navigation with hamburger menu
  - Certificate card styling
  - Animations and transitions
- **SCOPE**: Applies to all HTML pages
- **FEATURES**:
  - CSS-only mobile menu (no JavaScript required)
  - Green color scheme for environmental theme
  - Print-friendly styles
  - Accessible colors and fonts

#### **js/script.js** - JavaScript Interactivity
- **PURPOSE**: Client-side functionality and UX enhancements
- **FUNCTIONS**:
  - `formatNumber()`: Display large numbers with commas
  - `confirmAction()`: Show confirmation dialogs before deletion
  - `validateForm()`: Client-side form validation
  - Alert auto-hide: Removes success/error messages after 5 seconds
  - Mobile menu toggle
  - Smooth scroll to page sections
- **SCOPE**: Loaded on all pages via script tag

---

## 🔄 Data Flow & Relationships

### **User Registration & Login Flow**
```
register.php → users table (INSERT new user)
             → login.php (user logs in)
             → config.php (session starts)
             → auth.php (loginUser creates session)
             → dashboard.php (logged-in user home)
```

### **Logging Plastic Reduction**
```
log_entry.php (user submits form)
    → getEnvironmentalFactors() (get impact per item)
    → calculateImpact() (multiply by quantity)
    → logs table (INSERT new entry)
    → checkAndAwardCertificates() (check if user qualifies)
    → user_certificates table (INSERT new cert if qualified)
    → dashboard.php (shows updated stats)
```

### **Displaying Statistics**
```
dashboard.php calls:
    → getUserStats() reads logs table (user's totals)
    → getUserCertificates() reads user_certificates table
    → JOINs to certificates for display
    
index.php calls:
    → getCommunityStats() sums all logs
    → getLeaderboard() ranks users by items
```

### **Admin Certificate Management**
```
admin/certificates.php
    → Can CREATE certificates in certificates table
    → Can AWARD certificates via user_certificates table
    → Can DELETE certificates (may cascade)
    → Displays awarded certificates with JOINs
```

---

## 🗄️ Database Tables & Relationships

### **users**
- Stores user accounts (participants and admins)
- Foreign key in: logs, user_certificates
- Fields: user_id, username, email, password_hash, role, status, created_at, last_login

### **logs**
- Stores individual plastic reduction entries
- Foreign keys: user_id → users, factor_id → environmental_factors
- Fields: log_id, user_id, factor_id, quantity, log_date, co2_saved, water_saved, created_at

### **environmental_factors**
- Stores impact data per item type (bottle, bag, container)
- Used to calculate impact when logging
- Fields: factor_id, item_type, co2_saved_grams, water_saved_liters, data_source, is_active

### **certificates**
- Defines available certificates
- Foreign key in: user_certificates
- Fields: certificate_id, name, description, criteria_type (auto/manual), criteria_value, design_style

### **user_certificates**
- Junction table linking users to their earned certificates
- Foreign keys: user_id → users, certificate_id → certificates, awarded_by → users
- Fields: user_certificate_id, user_id, certificate_id, awarded_date, awarded_by, personal_message

---

## 🔐 Security Features

1. **Password Security**: Uses `password_hash()` with bcrypt (one-way encryption)
2. **Input Sanitization**: `sanitizeInput()` prevents XSS attacks
3. **Prepared Statements**: All queries use `bind_param()` to prevent SQL injection
4. **Session Security**: 
   - HTTPOnly cookies prevent JavaScript access
   - Cookies-only mode (not URL parameters)
5. **Role-Based Access**: `requireRole()` ensures only authorized users access admin pages
6. **CSRF Protection**: Form submission requires POST (not GET)
7. **Email Validation**: Validates email format before storage

---

## 🎯 Key Application Flows

### **Auto-Certificate Award Trigger**
```
User logs items → calculateImpact() → INSERT to logs
→ checkAndAwardCertificates() called
→ SUM(quantity) from logs for this user
→ Find certificates where criteria_value <= total_items
→ Check if user doesn't already have it (LEFT JOIN)
→ INSERT new certificate if eligible
```

### **Admin User Deactivation**
```
Admin clicks "Deactivate" button in users.php
→ POST to same page with toggle_status
→ UPDATE users SET status='inactive' WHERE user_id=?
→ User cannot log in next time (login.php checks status)
→ User's logs/certificates remain (just can't access)
```

---

## 📱 Responsive Design

- **Mobile-First CSS**: Styles work on phones by default
- **CSS Hamburger Menu**: No JavaScript needed for menu toggle
  - Hidden checkbox + label for interactivity
  - CSS `:checked` selector shows/hides nav
- **Breakpoints**: Adjusts for tablet and desktop
- **Viewport Meta Tag**: All pages include for mobile scaling

---

## 🚀 How to Extend the Application

1. **Add New Environmental Factor**:
   - Insert row into `environmental_factors` table
   - Automatically available in forms (via `getEnvironmentalFactors()`)

2. **Add New Certificate Type**:
   - Create in `admin/certificates.php`
   - Set criteria_type='manual' for admin-only awards
   - Set criteria_type='auto' for automatic thresholds

3. **Add New Admin Report**:
   - Create report page in `admin/reports.php`
   - Use `getCommunityStats()` and custom queries
   - Ensure `requireRole('admin')` at top

4. **Modify User Stats Calculation**:
   - Edit `getUserStats()` in `functions.php`
   - Changes apply everywhere `getUserStats()` is called

---

## 📝 Summary

**Without these files, the app cannot work:**
- **config.php**: No database connection
- **database.php**: No queries execute
- **auth.php**: No access control
- **functions.php**: No business logic
- **style.css**: No styling
- **users, logs, certificates tables**: No data storage

All other pages depend on this core infrastructure. The application uses a **MVC-like pattern** where:
- **Includes/**: Model layer (data & logic)
- **PHP Pages**: Controller layer (handle requests & prepare data)
- **HTML & CSS**: View layer (display data to users)
- **JavaScript**: Enhanced interactivity
