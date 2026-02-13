# Quick Reference Guide - File Purposes & Relationships

## 📚 Documentation Files Created

This package includes comprehensive documentation:

1. **ARCHITECTURE_GUIDE.md** - Complete system architecture and file relationships
2. **FILE_DEPENDENCIES.md** - Dependency chains and how files connect
3. **DATABASE_SCHEMA.md** - Database tables, fields, and relationships
4. **CODE_COMMENTS** - Detailed inline comments in all PHP files (see code itself)

---

## 🎯 Quick Answer: "Why Do I Need This File?"

### **Core Files (MUST HAVE - Application Won't Work Without)**

| File | Purpose | If Missing |
|------|---------|-----------|
| **includes/config.php** | Global constants, DB credentials, session setup | ❌ Database connection fails, no constants |
| **includes/database.php** | Database connection management | ❌ No database queries work |
| **includes/auth.php** | User login/logout, access control | ❌ No authentication, security completely compromised |
| **includes/functions.php** | Business logic, impact calculations, statistics | ❌ No calculations, statistics, or certificates |

### **Frontend Files (Nice to Have - App Functions Without, But Looks Bad)**

| File | Purpose | If Missing |
|------|---------|-----------|
| **css/style.css** | All styling and layout | ❌ Pages display as plain HTML (unusable) |
| **js/script.js** | Client-side validation, UX enhancements | ⚠️ Forms still work, but missing polish |

### **Authentication Pages (CRITICAL - Users Can't Access App)**

| File | Purpose | If Missing |
|------|---------|-----------|
| **login.php** | User authentication | ❌ No one can log in |
| **register.php** | New user signup | ⚠️ New users can't create accounts |

### **User Pages (User-Facing Features - Requires LOGIN)**

| File | Purpose | If Missing |
|------|---------|-----------|
| **index.php** | Public home page | ⚠️ No home page for guests |
| **dashboard.php** | User statistics and recent activity | ❌ Core feature missing |
| **log_entry.php** | Create plastic reduction logs | ❌ Users can't log entries |
| **certificates.php** | View earned achievements | ⚠️ Users can't see certificates |
| **profile.php** | Account management | ⚠️ Users can't change password/email |

### **Admin Pages (Admin-Only Features)**

| File | Purpose | If Missing |
|------|---------|-----------|
| **admin/dashboard.php** | Admin statistics overview | ⚠️ No admin overview |
| **admin/users.php** | Manage user accounts | ⚠️ Can't deactivate users |
| **admin/logs.php** | Manage all log entries | ⚠️ Can't delete invalid logs |
| **admin/certificates.php** | Create and award certificates | ⚠️ Can't create/award certificates |
| **admin/reports.php** | Analytics and reports | ⚠️ No reports |
| **admin/revoke_certificate.php** | Revoke user certificates | ⚠️ Can't revoke awards |

---

## 🔗 "How Do These Files Connect?"

### **File Inclusion Chain (What Includes What)**

```
Every protected page does this at the top:
┌────────────────────────────────────┐
│ require_once 'includes/config.php' │ ← Start here (loads session, constants)
└────────────┬───────────────────────┘
             ↓
┌────────────────────────────────────┐
│ require_once 'includes/auth.php'   │ ← Can now use isLoggedIn(), requireLogin()
└────────────┬───────────────────────┘
             ↓
┌────────────────────────────────────┐
│ require_once 'includes/database.php│ ← Can now use getDBConnection()
└────────────┬───────────────────────┘
             ↓
┌────────────────────────────────────┐
│ require_once 'includes/functions.php│ ← Can now use calculateImpact(), etc
└────────────────────────────────────┘
```

### **Function Dependency Example**

When user logs an entry in **log_entry.php**:

```
log_entry.php calls calculateImpact()
    ↓
calculateImpact() (in functions.php) calls getEnvironmentalFactors()
    ↓
getEnvironmentalFactors() (in functions.php) calls getDBConnection()
    ↓
getDBConnection() (in database.php) reads DB_HOST, DB_USER, etc.
    ↓
Database credentials come from config.php
    ↓
SUCCESS: Log entry created with proper impact values
```

### **Authentication Example**

When user logs in in **login.php**:

```
User submits login form
    ↓
login.php calls getDBConnection() (from database.php)
    ↓
Query users table for password_hash
    ↓
Call password_verify() (built-in PHP)
    ↓
If password matches, call loginUser() (from auth.php)
    ↓
loginUser() sets $_SESSION variables
    ↓
User can now access protected pages
    ↓
Protected pages call requireLogin() (from auth.php)
    ↓
requireLogin() checks if $_SESSION['user_id'] exists
    ↓
SUCCESS: Access granted to dashboard.php
```

---

## 📊 File Relationship Matrix

```
                  config.php
                      │
      ┌───────────────┼───────────────┐
      │               │               │
   auth.php      database.php    functions.php
      │               │               │
      ├───────────────┼───────────────┘
      │               │
      └───────────┬───┘
                  │
      ┌───────────┴───────────┬──────────────┐
      │                       │              │
    css/             js/              Protected Pages
  style.css       script.js         (login, register, dashboard, etc)
                                    & Admin Pages
                                    (users, logs, certificates, etc)
```

---

## 🔍 Dependency Resolution Flowchart

**Use this to understand why something might not work:**

```
App crashes/doesn't work
    ↓
Is the database accessible?
    ├─ NO  → Check config.php (DB_HOST, DB_USER, DB_PASS, DB_NAME)
    └─ YES → Continue
    ↓
Can you see the login page?
    ├─ NO  → Check index.php, login.php files exist
    │        Check style.css and js/script.js
    └─ YES → Continue
    ↓
Can you log in?
    ├─ NO  → Check auth.php functions (loginUser, getCurrentUser)
    │        Check database.php connection
    │        Check users table exists in database
    └─ YES → Continue
    ↓
Can you see your stats?
    ├─ NO  → Check functions.php getUserStats()
    │        Check logs table exists with data
    │        Check environmental_factors table exists
    └─ YES → Continue
    ↓
Can you log a plastic entry?
    ├─ NO  → Check calculateImpact() in functions.php
    │        Check environmental_factors has active entries
    │        Check logs table is writable
    └─ YES → Continue
    ↓
Can you see certificates?
    ├─ NO  → Check getUserCertificates() in functions.php
    │        Check certificates table exists
    │        Check user_certificates table is populated
    └─ YES → All working!
```

---

## 💡 Key Concepts Explained Simply

### **Why Config.php Must Be First**
- Sets database credentials
- Starts session (allows $_SESSION to work)
- Defines constants like SITE_NAME
- If not included first, everything else fails

### **Why Database.php Needs Config**
- Uses DB_HOST, DB_USER, etc. from config
- Gets connection object needed by all queries
- If config not loaded first, no credentials available

### **Why Auth.php Works With Database**
- `loginUser()` needs `getDBConnection()` to update last_login
- `requireLogin()` checks $_SESSION (set by config)
- Protects pages by checking if user is logged in

### **Why Functions.php Has Most Code**
- Centralizes all business logic
- `calculateImpact()` multiplies quantity × factor
- `getUserStats()` sums all user's logs
- `checkAndAwardCertificates()` checks and auto-awards
- Used by almost every page

### **Why CSS and JS Load Last**
- Styling and scripts enhance the page
- App still works without them (but looks bad)
- Can be loaded asynchronously

---

## 🎓 Understanding the Flow: Three Scenarios

### **Scenario 1: New User Registration**
```
register.php
├─ User fills form
├─ Call sanitizeInput() from functions.php
├─ Call isValidEmail() from functions.php
├─ Call getDBConnection() from database.php
│  └─ Uses config.php for credentials
├─ Check username/email uniqueness
├─ Hash password with password_hash()
└─ INSERT into users table

Result: User created, must log in via login.php
```

### **Scenario 2: Logging Plastic Reduction**
```
log_entry.php
├─ requireLogin() from auth.php ← User must be logged in
├─ getCurrentUser() from auth.php ← Get user_id from session
├─ getEnvironmentalFactors() from functions.php
│  └─ Queries environmental_factors table
├─ calculateImpact() from functions.php ← Multiply factor × quantity
├─ INSERT into logs table with calculated co2_saved, water_saved
├─ checkAndAwardCertificates() from functions.php
│  ├─ Get user's total items
│  ├─ Find auto certificates they qualify for
│  └─ INSERT new certificates into user_certificates
└─ Redirect to dashboard.php with success message

Result: Entry logged, impact calculated, potential certificates awarded
```

### **Scenario 3: Admin Awards Certificate**
```
admin/certificates.php
├─ requireRole('admin') from auth.php ← Only admins can access
├─ Get admin's user_id from getCurrentUser()
├─ Get list of users and certificates
├─ Admin selects user and certificate
├─ INSERT into user_certificates (user_id, certificate_id, awarded_by=admin_id)
└─ Redirect back to dashboard

Later when user views certificates.php:
├─ Call getUserCertificates(user_id)
├─ JOINs user_certificates + certificates + users tables
└─ Display new certificate to user

Result: Admin-awarded certificate visible to user
```

---

## 📋 File Checklist: What You Need to Exist

### **Absolutely Required**
- ✅ `includes/config.php` - Database credentials
- ✅ `includes/database.php` - Connection functions
- ✅ `includes/auth.php` - Login/logout system
- ✅ `includes/functions.php` - Business logic
- ✅ `login.php` - User login page
- ✅ `dashboard.php` - User home page

### **Highly Recommended**
- ✅ `register.php` - User registration
- ✅ `index.php` - Public home page
- ✅ `log_entry.php` - Log plastic entries
- ✅ `certificates.php` - View achievements
- ✅ `profile.php` - Account management
- ✅ `css/style.css` - Styling
- ✅ `js/script.js` - JavaScript

### **For Admin Features**
- ✅ `admin/dashboard.php` - Admin overview
- ✅ `admin/users.php` - Manage users
- ✅ `admin/logs.php` - Manage logs
- ✅ `admin/certificates.php` - Create/award certs

### **Database Tables Required**
- ✅ `users` - User accounts
- ✅ `logs` - Reduction entries
- ✅ `environmental_factors` - Impact data
- ✅ `certificates` - Certificate definitions
- ✅ `user_certificates` - User awards

---

## 🚀 How to Test if Everything Works

1. **Test Config**: Go to login.php, can you see the form?
2. **Test Database**: Register new account, can you create one?
3. **Test Auth**: Can you log in with the new account?
4. **Test Functions**: Can you log an entry on log_entry.php?
5. **Test Stats**: Do your stats show on dashboard.php?
6. **Test Certs**: Do certificates appear on certificates.php?
7. **Test Admin**: Can admins see admin/dashboard.php?

If all tests pass, the entire system is working correctly!

---

## 📞 Troubleshooting Guide

| Problem | Check |
|---------|-------|
| White blank page | Check error log, missing `require` statements |
| "Database connection failed" | Verify DB credentials in config.php |
| "Login failed" | Check users table exists, password hashing works |
| "No stats showing" | Check logs table has entries, calculateImpact works |
| "Certificate not awarded" | Check certificates table, user's total items |
| "Admin page shows 404" | Check requireRole('admin') at top of admin file |
| "Styling looks broken" | Verify css/style.css path in HTML <link> tag |
| "Menu not working" | Check js/script.js and CSS hamburger styles |

---

## 📖 Where to Learn More

- **ARCHITECTURE_GUIDE.md**: Complete system architecture
- **FILE_DEPENDENCIES.md**: Dependency chains and relationships  
- **DATABASE_SCHEMA.md**: Table structures and SQL examples
- **Code Comments**: Read inline comments in actual PHP files

---

## 🎯 One-Sentence Summary of Each File

| File | Summary |
|------|---------|
| config.php | "Define database credentials and start session" |
| database.php | "Create and close database connections" |
| auth.php | "Control who can access what pages" |
| functions.php | "Calculate impacts, get statistics, check certifications" |
| login.php | "Let users prove who they are with password" |
| register.php | "Let new people create accounts" |
| dashboard.php | "Show users their personal stats and recent activity" |
| log_entry.php | "Let users record plastic items they saved" |
| certificates.php | "Show users their earned achievements" |
| profile.php | "Let users change their email and password" |
| index.php | "Welcome page showing community stats" |
| admin/users.php | "Show admins all users and let them deactivate accounts" |
| admin/logs.php | "Show admins all logs and let them delete bad entries" |
| admin/certificates.php | "Let admins create certificates and award them to users" |
| css/style.css | "Make everything look nice and work on mobile" |
| js/script.js | "Validate forms and hide alerts automatically" |

---

**For any questions, refer to the detailed documentation files included in this directory.**
