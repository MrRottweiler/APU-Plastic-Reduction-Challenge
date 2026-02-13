# File Dependency & Relationship Map

## 🔗 File Dependency Chain

### **Tier 0: Foundation (No dependencies)**
```
config.php
├─ Defines: DB_HOST, DB_USER, DB_PASS, DB_NAME, SITE_NAME
├─ Sets: Session security, timezone
└─ Used by: EVERY file
```

### **Tier 1: Database & Authentication (Depends on Tier 0)**
```
database.php (requires config.php)
├─ Functions: getDBConnection(), closeDBConnection()
├─ Provides: MySQLi connection object
└─ Used by: auth.php, functions.php, all pages

auth.php (requires config.php, database.php)
├─ Functions: isLoggedIn(), hasRole(), requireLogin(), loginUser(), logoutUser(), getCurrentUser()
├─ Provides: Access control, session management
└─ Used by: All protected pages
```

### **Tier 2: Business Logic (Depends on Tier 0-1)**
```
functions.php (requires database.php)
├─ Input Handlers: sanitizeInput(), isValidEmail()
├─ Environmental: getEnvironmentalFactors(), calculateImpact()
├─ Statistics: getUserStats(), getCommunityStats(), getLeaderboard()
├─ Certificates: checkAndAwardCertificates(), getUserCertificates()
├─ Formatting: formatNumber(), formatDecimal()
└─ Used by: All pages for calculations, queries, and display
```

### **Tier 3: Frontend Files (Depends on Tier 0-2)**
```
css/style.css
├─ Styling: All HTML elements
├─ Responsive: Mobile hamburger menu, responsive layout
└─ Used by: All HTML pages

js/script.js
├─ Functions: formatNumber(), validateForm(), confirmAction()
├─ Features: Auto-hide alerts, smooth scrolling, form validation
└─ Used by: All HTML pages
```

### **Tier 4: Public Pages (Depends on Tier 0-3)**
```
index.php (requires config, auth, functions, database)
├─ Purpose: Public home page
├─ Database: getCommunityStats(), getLeaderboard()
├─ Visible to: Everyone (logged in or guest)
└─ Links to: login.php, register.php, dashboard.php (if logged in)

login.php (requires config, auth, database, functions)
├─ Purpose: User authentication
├─ Database: Reads users table
├─ Visible to: Guests and logged-in users
└─ Links to: dashboard.php, register.php

register.php (requires config, auth, database, functions)
├─ Purpose: New user registration
├─ Database: Writes to users table
├─ Visible to: Guests and logged-in users
└─ Links to: login.php
```

### **Tier 5: Protected User Pages (Requires: Tier 0-3 + requireLogin())**
```
dashboard.php (requires all, auth.requireLogin())
├─ Purpose: User home page
├─ Database: getUserStats(), getUserCertificates(), logs with JOINs
├─ Requires: User must be logged in
└─ Links to: log_entry.php, certificates.php, profile.php, admin pages (if admin)

log_entry.php (requires all, auth.requireLogin())
├─ Purpose: Log plastic reduction
├─ Database: getEnvironmentalFactors(), calculateImpact(), INSERT logs, checkAndAwardCertificates()
├─ Requires: User must be logged in
└─ Links to: dashboard.php

certificates.php (requires all, auth.requireLogin())
├─ Purpose: View earned certificates
├─ Database: getUserCertificates()
├─ Requires: User must be logged in
└─ Links to: dashboard.php, log_entry.php

profile.php (requires all, auth.requireLogin())
├─ Purpose: Manage account
├─ Database: getUserStats(), UPDATE users table
├─ Requires: User must be logged in
└─ Links to: dashboard.php
```

### **Tier 6: Protected Admin Pages (Requires: Tier 0-3 + requireRole('admin'))**
```
admin/dashboard.php (requires ../includes/*, auth.requireRole('admin'))
├─ Purpose: Admin overview
├─ Database: getCommunityStats(), recent logs with JOINs
├─ Requires: User must be admin
└─ Links to: admin/users.php, admin/logs.php, etc.

admin/users.php (requires ../includes/*)
├─ Purpose: Manage user accounts
├─ Database: SELECT users with aggregated logs, UPDATE user status
├─ Requires: User must be admin

admin/logs.php (requires ../includes/*)
├─ Purpose: Manage log entries
├─ Database: SELECT/DELETE from logs table
├─ Requires: User must be admin

admin/certificates.php (requires ../includes/*)
├─ Purpose: Create/award/delete certificates
├─ Database: CRUD operations on certificates and user_certificates
├─ Requires: User must be admin

admin/reports.php (requires ../includes/*)
├─ Purpose: Generate analytics reports
├─ Database: Statistical queries
├─ Requires: User must be admin

admin/revoke_certificate.php (requires ../includes/*)
├─ Purpose: Revoke user certificates
├─ Database: DELETE from user_certificates
├─ Requires: User must be admin
```

---

## 📊 Dependency Graph (Simplified)

```
┌─────────────────┐
│   config.php    │ (FOUNDATION - Defines all constants)
└────────┬────────┘
         │
    ┌────┴────┬──────────────┐
    │         │              │
┌───▼──┐  ┌──▼────────┐  ┌─▼──────────┐
│ auth │  │ database  │  │ functions  │
└───┬──┘  │           │  │            │
    │     └──┬────────┘  └────┬───────┘
    │        │                │
    └────┬───┴────────────────┘
         │
    ┌────▼──────────────────────┐
    │  All Page Files            │
    │  (Protected by requireLogin│
    │   or requireRole)          │
    └───┬──────────────────────┬─┘
        │                      │
    ┌───▼────┐          ┌─────▼────┐
    │ User   │          │  Admin    │
    │ Pages  │          │  Pages    │
    └────────┘          └───────────┘
        │
    ┌───┴────────────┬──────────────┐
    │                │              │
┌───▼────┐      ┌────▼────┐   ┌────▼────┐
│ CSS    │      │ Images  │   │ JS      │
│ Design │      │ Assets  │   │ Scripts │
└────────┘      └─────────┘   └─────────┘
```

---

## 🔄 Critical Relationships (What Breaks If Removed)

### **If you remove config.php:**
- ❌ Database credentials undefined
- ❌ No SITE_NAME constant
- ❌ Session not initialized
- ❌ **Everything breaks immediately**

### **If you remove database.php:**
- ❌ getDBConnection() unavailable
- ❌ No database queries possible
- ❌ auth.php breaks (can't query users table)
- ❌ functions.php breaks (can't get environmental factors)
- ❌ **All data access breaks**

### **If you remove auth.php:**
- ❌ requireLogin() unavailable
- ❌ No access control
- ❌ Anyone can access protected pages
- ❌ **Security completely compromised**

### **If you remove functions.php:**
- ❌ No sanitizeInput() (XSS vulnerability)
- ❌ No calculateImpact() (can't log entries)
- ❌ No getUserStats() (dashboard shows no stats)
- ❌ No certificates (auto-award fails)
- ❌ **Business logic completely broken**

### **If you remove style.css:**
- ❌ No styling applied
- ❌ Pages render in plain HTML (unusable)
- ❌ Mobile menu broken (CSS hamburger)

### **If you remove script.js:**
- ❌ Minor: Auto-hide alerts don't work
- ⚠️ Form validation still works (server-side)
- ⚠️ Smooth scrolling disabled
- ℹ️ App still functions (but worse UX)

---

## 📈 Data Flow Diagram

### **Registration & Login Process**
```
User visits register.php
    ↓
[HTML Form Displays]
    ↓
User submits form
    ↓
register.php calls:
  - sanitizeInput() [functions.php]
  - isValidEmail() [functions.php]
  - getDBConnection() [database.php]
  - password_hash()
    ↓
INSERT INTO users table
    ↓
Success message
    ↓
User visits login.php
    ↓
User submits credentials
    ↓
login.php calls:
  - getDBConnection() [database.php]
  - password_verify() (check hashed password)
    ↓
loginUser() [auth.php] called
  - Sets $_SESSION variables
  - Updates last_login in database
    ↓
Redirect to dashboard.php
    ↓
requireLogin() [auth.php] checks session
    ↓
Dashboard displays user's stats
    ↓
getUserStats() [functions.php]
  - Sums all logs for this user
  - Returns totals
```

### **Logging Plastic Reduction**
```
User clicks "Log Entry" link
    ↓
log_entry.php loads
    ↓
requireLogin() [auth.php] checks access
    ↓
getEnvironmentalFactors() [functions.php]
  - Queries environmental_factors table
  - Returns impact per item type
    ↓
[HTML Form with impact preview (JavaScript)]
    ↓
User submits form
    ↓
validateForm() [server-side]
    ↓
calculateImpact() [functions.php]
  - Multiplies per-item impact × quantity
    ↓
INSERT INTO logs table
  - Stores quantity, co2_saved, water_saved
    ↓
checkAndAwardCertificates() [functions.php]
  - Calculates new total items
  - Finds auto-certificates user qualifies for
  - INSERTs into user_certificates
    ↓
Success message
    ↓
User views dashboard.php
    ↓
Stats updated automatically
```

### **Admin Award Certificate**
```
Admin visits admin/certificates.php
    ↓
requireRole('admin') [auth.php] checks permission
    ↓
getEnvironmentalFactors() - Shows users and certificates
    ↓
Admin selects user + certificate + message
    ↓
Form submitted
    ↓
INSERT INTO user_certificates table
    ↓
Success message
    ↓
User visits certificates.php
    ↓
getUserCertificates() [functions.php]
  - JOINs user_certificates + certificates + users tables
  - Returns certificate details
    ↓
[Certificates displayed to user]
```

---

## 🎯 File Usage Matrix

| File | Used By | Purpose |
|------|---------|---------|
| **includes/config.php** | ALL | Constants & session setup |
| **includes/database.php** | All includes & pages | DB connection |
| **includes/auth.php** | All protected pages | Access control |
| **includes/functions.php** | All pages | Business logic |
| **css/style.css** | All HTML pages | Styling |
| **js/script.js** | All HTML pages | Interactivity |
| **login.php** | Users | Authentication |
| **register.php** | Users | Account creation |
| **index.php** | Everyone | Public home page |
| **dashboard.php** | Logged-in users | User home |
| **log_entry.php** | Logged-in users | Log entries |
| **certificates.php** | Logged-in users | View achievements |
| **profile.php** | Logged-in users | Account settings |
| **admin/dashboard.php** | Admins | Admin overview |
| **admin/users.php** | Admins | User management |
| **admin/logs.php** | Admins | Log management |
| **admin/certificates.php** | Admins | Certificate management |
| **admin/reports.php** | Admins | Analytics |

---

## 🔐 Access Control Flow

```
User visits any page
    ↓
Page checks: requireLogin() ?
    ├─ NO  → Display public page (index, login, register)
    ├─ YES → Check if isLoggedIn()
    │        ├─ NO  → Redirect to login.php
    │        └─ YES → Continue
    │
Page checks: requireRole('admin') ?
    ├─ NO  → Display regular user page
    └─ YES → Check if hasRole('admin')
             ├─ NO  → Redirect to dashboard.php
             └─ YES → Display admin page
```

---

## 📝 Key Takeaways

1. **Tier 0 (config.php)** is the absolute foundation
2. **Tier 1** (database.php, auth.php) enable core functionality
3. **Tier 2** (functions.php) contains all business logic
4. **Tier 3** (CSS, JS) enhance user experience
5. **Tiers 4-6** are the pages users see

**The app follows a hierarchical dependency model** where higher tiers depend on lower tiers, but not vice versa. This prevents circular dependencies and makes the code maintainable.

**To debug:** Start from the lowest tier (config → database → auth → functions) and work up. If config is wrong, nothing works. If functions is broken, business logic fails.
