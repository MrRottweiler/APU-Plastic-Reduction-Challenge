# Database Schema & Relationships

## 📋 Database Overview

**Database Name**: `apu_plastic_challenge`

This document explains each table, its relationships, and how they work together.

---

## 🗄️ Table Structure

### **1. users** - User Accounts

#### Purpose
Stores all user accounts for the application (both participants and administrators).

#### Table Structure
```
+───────────────────┬──────────────┬─────────────────────────────────────────┐
│ Column Name       │ Type         │ Description                             │
├───────────────────┼──────────────┼─────────────────────────────────────────┤
│ user_id           │ INT (PK)     │ Unique user identifier                  │
│ username          │ VARCHAR(50)  │ Unique login username                   │
│ email             │ VARCHAR(100) │ Unique email address                    │
│ password_hash     │ VARCHAR(255) │ Bcrypt hashed password (not plaintext)  │
│ role              │ ENUM         │ 'admin' or 'participant'                │
│ status            │ ENUM         │ 'active' or 'inactive'                  │
│ created_at        │ TIMESTAMP    │ Account creation date/time              │
│ last_login        │ TIMESTAMP    │ Last time user logged in                │
└───────────────────┴──────────────┴─────────────────────────────────────────┘
```

#### Key Points
- **Uniqueness**: username and email must be unique (prevent duplicates)
- **Password Security**: Stored as bcrypt hash (can't be reversed)
- **Role-Based Access**: 
  - `admin`: Can manage users, logs, certificates, and view reports
  - `participant`: Regular user who can log entries and earn certificates
- **Status Control**: Admins can deactivate accounts without deleting data
- **Timestamps**: Track when user joined and last logged in

#### Relationships
- **Foreign Key Source** for: logs (user_id), user_certificates (user_id), user_certificates (awarded_by)
- **Referenced By**: 
  - logs.user_id → users.user_id (each log belongs to a user)
  - user_certificates.user_id → users.user_id (each certificate award is to a user)
  - user_certificates.awarded_by → users.user_id (who gave the award)

#### SQL Examples
```sql
-- Create admin account
INSERT INTO users (username, email, password_hash, role, status)
VALUES ('admin', 'admin@apu.edu.my', SHA2('admin123', 256), 'admin', 'active');

-- Find user by email or username (used in login)
SELECT * FROM users WHERE email = 'user@example.com' OR username = 'john';

-- Update last login timestamp
UPDATE users SET last_login = NOW() WHERE user_id = 5;

-- Deactivate user account
UPDATE users SET status = 'inactive' WHERE user_id = 3;
```

---

### **2. environmental_factors** - Impact Data

#### Purpose
Defines how much CO2 and water is saved by reducing each type of plastic item. This is the basis for all impact calculations.

#### Table Structure
```
+────────────────────┬──────────────┬──────────────────────────────────────────┐
│ Column Name        │ Type         │ Description                              │
├────────────────────┼──────────────┼──────────────────────────────────────────┤
│ factor_id          │ INT (PK)     │ Unique factor identifier                 │
│ item_type          │ VARCHAR(50)  │ Type of item ('bottle', 'bag', 'container')
│ co2_saved_grams    │ DECIMAL      │ CO2 saved per item (in grams)            │
│ water_saved_liters │ DECIMAL      │ Water saved per item (in liters)         │
│ data_source        │ VARCHAR(255) │ Where this data comes from (EPA, etc)    │
│ is_active          │ BOOLEAN      │ Is this factor currently usable?         │
└────────────────────┴──────────────┴──────────────────────────────────────────┘
```

#### Example Data
```
┌──────────┬────────────┬─────────────────┬──────────────────┐
│ factor_id│ item_type  │ co2_saved_grams │ water_saved_liters
├──────────┼────────────┼─────────────────┼──────────────────┤
│    1     │ bottle     │     10.5        │      25.0       │
│    2     │ bag        │      3.2        │       8.0       │
│    3     │ container  │      5.0        │      12.0       │
└──────────┴────────────┴─────────────────┴──────────────────┘
```

#### Key Points
- **Static Data**: These values rarely change (set by environmental research)
- **Calculation Basis**: Used by `calculateImpact()` function
- **Example Calculation**:
  - If bottle saves 10.5g CO2, and user saves 5 bottles
  - Total CO2 = 10.5 × 5 = 52.5g
- **is_active**: Allows disabling factors without deleting historical data

#### Relationships
- **Foreign Key Target** for: logs (factor_id)
- **Used By**: 
  - calculateImpact() function (functions.php)
  - getEnvironmentalFactors() function (returns all active factors)
  - dashboard.php, admin/dashboard.php (for JOIN queries)

#### SQL Examples
```sql
-- Get all active factors
SELECT * FROM environmental_factors WHERE is_active = 1;

-- Get specific factor for impact calculation
SELECT co2_saved_grams, water_saved_liters 
FROM environmental_factors 
WHERE item_type = 'bottle';

-- Calculate total impact for a user
SELECT SUM(l.quantity * e.co2_saved_grams) as total_co2
FROM logs l
JOIN environmental_factors e ON l.factor_id = e.factor_id
WHERE l.user_id = 5;
```

---

### **3. logs** - Plastic Reduction Entries

#### Purpose
Stores individual plastic reduction entries logged by users. Each row is one log entry.

#### Table Structure
```
+─────────────────┬──────────────┬──────────────────────────────────────────────┐
│ Column Name     │ Type         │ Description                                  │
├─────────────────┼──────────────┼──────────────────────────────────────────────┤
│ log_id          │ INT (PK)     │ Unique log entry identifier                  │
│ user_id         │ INT (FK)     │ User who created this log (→ users.user_id)  │
│ factor_id       │ INT (FK)     │ Item type (→ environmental_factors.factor_id)│
│ quantity        │ INT          │ How many items saved in this log             │
│ log_date        │ DATE         │ Date the items were saved (past or present)  │
│ co2_saved       │ DECIMAL      │ Total CO2 saved (quantity × factor)          │
│ water_saved     │ DECIMAL      │ Total water saved (quantity × factor)        │
│ created_at      │ TIMESTAMP    │ When this log entry was created              │
└─────────────────┴──────────────┴──────────────────────────────────────────────┘
```

#### Example Data
```
┌────────┬─────────┬───────────┬──────────┬────────────┬──────────┬──────────────┐
│ log_id │ user_id │ factor_id │ quantity │ log_date   │ co2_saved│ water_saved  │
├────────┼─────────┼───────────┼──────────┼────────────┼──────────┼──────────────┤
│   1    │    1    │     1     │    5     │ 2024-01-15 │  52.5    │   125.0      │
│   2    │    1    │     2     │    10    │ 2024-01-16 │  32.0    │    80.0      │
│   3    │    2    │     1     │    3     │ 2024-01-15 │  31.5    │    75.0      │
└────────┴─────────┴───────────┴──────────┴────────────┴──────────┴──────────────┘
```

#### Key Points
- **Pre-calculated Values**: co2_saved and water_saved are calculated when log is created
  - NOT calculated on-the-fly (improves performance)
- **Foreign Keys**: 
  - user_id references users (shows who logged this)
  - factor_id references environmental_factors (the item type)
- **Historical Data**: log_date can be past (user can backlog entries)
- **Audit Trail**: created_at shows when entry was logged

#### Relationships
- **Foreign Key Source** for: (none directly)
- **Referenced By**: 
  - All statistics queries (getUserStats, getCommunityStats, getLeaderboard)
- **Depends On**: 
  - users (must exist to create log)
  - environmental_factors (must exist to reference)

#### SQL Examples
```sql
-- Create a log entry (called by log_entry.php)
INSERT INTO logs (user_id, factor_id, quantity, log_date, co2_saved, water_saved)
VALUES (1, 1, 5, '2024-01-15', 52.5, 125.0);

-- Get user's total impact
SELECT 
    SUM(quantity) as total_items,
    SUM(co2_saved) as total_co2,
    SUM(water_saved) as total_water
FROM logs
WHERE user_id = 1;

-- Get recent logs with item names
SELECT l.*, e.item_type
FROM logs l
JOIN environmental_factors e ON l.factor_id = e.factor_id
WHERE l.user_id = 1
ORDER BY l.log_date DESC
LIMIT 10;

-- Delete a log (admin operation)
DELETE FROM logs WHERE log_id = 5;
```

---

### **4. certificates** - Certificate Definitions

#### Purpose
Defines available certificates that users can earn. Admins create certificates here.

#### Table Structure
```
+────────────────┬──────────────┬────────────────────────────────────────────────┐
│ Column Name    │ Type         │ Description                                    │
├────────────────┼──────────────┼────────────────────────────────────────────────┤
│ certificate_id │ INT (PK)     │ Unique certificate identifier                  │
│ name           │ VARCHAR(100) │ Certificate name (e.g., "Plastic Hero")       │
│ description    │ TEXT         │ What this certificate represents               │
│ criteria_type  │ ENUM         │ 'auto' (automatic) or 'manual' (admin-only)   │
│ criteria_value │ INT          │ For 'auto' type: number of items to reach     │
│ design_style   │ VARCHAR(50)  │ CSS class for styling (e.g., 'gold', 'silver')│
└────────────────┴──────────────┴────────────────────────────────────────────────┘
```

#### Example Data
```
┌────────────────┬──────────────┬───────────────┬──────────┬────────────────┐
│ certificate_id │ name         │ criteria_type │ criteria_│ design_style   │
│                │              │               │ value    │                │
├────────────────┼──────────────┼───────────────┼──────────┼────────────────┤
│       1        │ Starter      │ auto          │    10    │ bronze         │
│       2        │ Eco Warrior  │ auto          │    50    │ silver         │
│       3        │ Plastic Hero │ auto          │   100    │ gold           │
│       4        │ Special Award│ manual        │    0     │ diamond        │
└────────────────┴──────────────┴───────────────┴──────────┴────────────────┘
```

#### Certificate Types Explained

**Auto Certificates:**
- Awarded automatically when user reaches criteria_value items
- Example: When user saves 50 items, automatically get "Eco Warrior" certificate
- Process: `checkAndAwardCertificates()` runs after each log entry

**Manual Certificates:**
- Awarded only by admins through admin/certificates.php
- criteria_value is 0 (not used for auto-checking)
- Example: Special awards, achievements, recognition

#### Relationships
- **Foreign Key Target** for: user_certificates (certificate_id)
- **Used By**: 
  - checkAndAwardCertificates() function
  - admin/certificates.php (for creation/deletion)
  - getUserCertificates() (for display)

#### SQL Examples
```sql
-- Create auto certificate
INSERT INTO certificates (name, description, criteria_type, criteria_value, design_style)
VALUES ('Eco Warrior', 'You have saved 50 items!', 'auto', 50, 'silver');

-- Create manual certificate
INSERT INTO certificates (name, description, criteria_type, criteria_value, design_style)
VALUES ('Special Recognition', 'Special achievement award', 'manual', 0, 'diamond');

-- Get all auto certificates
SELECT * FROM certificates WHERE criteria_type = 'auto' ORDER BY criteria_value;

-- Find certificates user qualifies for
SELECT c.certificate_id
FROM certificates c
LEFT JOIN user_certificates uc ON c.certificate_id = uc.certificate_id AND uc.user_id = 1
WHERE c.criteria_type = 'auto'
AND c.criteria_value <= (SELECT SUM(quantity) FROM logs WHERE user_id = 1)
AND uc.user_certificate_id IS NULL;  -- User doesn't already have it
```

---

### **5. user_certificates** - User Achievement Records

#### Purpose
Junction table connecting users to certificates they've earned. Tracks which users have which certificates.

#### Table Structure
```
+──────────────────────┬──────────────┬───────────────────────────────────────────┐
│ Column Name          │ Type         │ Description                               │
├──────────────────────┼──────────────┼───────────────────────────────────────────┤
│ user_certificate_id  │ INT (PK)     │ Unique award record identifier            │
│ user_id              │ INT (FK)     │ User who earned cert (→ users.user_id)    │
│ certificate_id       │ INT (FK)     │ Certificate earned (→ certificates.id)    │
│ awarded_date         │ TIMESTAMP    │ When certificate was awarded              │
│ awarded_by           │ INT (FK)     │ Admin who awarded it (→ users.user_id)    │
│ personal_message     │ TEXT         │ Optional message from admin               │
│ UNIQUE (user_id, cert_id) │         │ Prevent duplicate awards                 │
└──────────────────────┴──────────────┴───────────────────────────────────────────┘
```

#### Example Data
```
┌──────────────────┬─────────┬────────────────┬──────────────────────┬────────────┐
│ user_cert_id     │ user_id │ certificate_id │ awarded_date         │ awarded_by │
├──────────────────┼─────────┼────────────────┼──────────────────────┼────────────┤
│        1         │    1    │        1       │ 2024-01-15 10:30:00  │     1      │
│        2         │    1    │        2       │ 2024-01-20 14:22:00  │     1      │
│        3         │    2    │        1       │ 2024-01-18 09:15:00  │     1      │
│        4         │    1    │        4       │ 2024-01-25 16:45:00  │     1      │
└──────────────────┴─────────┴────────────────┴──────────────────────┴────────────┘
```

#### Key Points
- **UNIQUE Constraint**: (user_id, certificate_id) prevents same user from getting same cert twice
- **Self-Reference**: awarded_by is also a user_id (admin who gave the award)
- **Timestamp**: awarded_date shows exactly when award was given
- **Message**: Personal message from admin can be optional/empty
- **Three-Way Join**: Connects users → certificates with additional metadata

#### Relationships
- **Foreign Key Sources**:
  - user_id → users.user_id (the recipient)
  - certificate_id → certificates.certificate_id (the award)
  - awarded_by → users.user_id (the admin who gave it)
- **Depends On**: Both users and certificates must exist before creating award

#### SQL Examples
```sql
-- Award certificate to user (auto-system)
INSERT INTO user_certificates (user_id, certificate_id, awarded_by)
VALUES (1, 1, 1);  -- User 1 gets cert 1, awarded by user 1 (system)

-- Award certificate manually (admin)
INSERT INTO user_certificates (user_id, certificate_id, awarded_by, personal_message)
VALUES (2, 4, 1, 'Great work on reducing plastic!');

-- Get all certificates for a user
SELECT c.name, c.description, uc.awarded_date, u.username as awarded_by_name
FROM user_certificates uc
JOIN certificates c ON uc.certificate_id = c.certificate_id
JOIN users u ON uc.awarded_by = u.user_id
WHERE uc.user_id = 1
ORDER BY uc.awarded_date DESC;

-- Check if user already has a certificate
SELECT COUNT(*) FROM user_certificates
WHERE user_id = 1 AND certificate_id = 2;

-- Delete certificate from user (admin revoke)
DELETE FROM user_certificates
WHERE user_id = 1 AND certificate_id = 2;
```

---

## 📊 Relationship Diagram

```
┌─────────────┐
│   users     │
│ ─────────── │
│ user_id(PK) │◄───────┐
│ username    │         │
│ email       │         │
│ password    │         │
│ role        │         │
│ status      │         │
│ created_at  │         │
│ last_login  │         │
└─────────────┘         │
       ▲                │
       │ ┌──────────────┴─────────────┐
       │ │                            │
   ┌───┴───────────────┐  ┌──────────┴──────────────┐
   │  logs             │  │  user_certificates      │
   │ ────────────────  │  │ ───────────────────────  │
   │ log_id (PK)       │  │ user_cert_id (PK)       │
   │ user_id (FK)  ────┼──│ user_id (FK)            │
   │ factor_id (FK)─┐  │  │ certificate_id (FK)  ───┼──┐
   │ quantity       │  │  │ awarded_date             │  │
   │ log_date       │  │  │ awarded_by (FK)  ───────┘  │
   │ co2_saved      │  │  │ personal_message         │
   │ water_saved    │  │  └──────────────────────────┘
   │ created_at     │  │            │
   └───────────────┘  │            │
        │             │            │
        │ ┌───────────┘            │
        │ │                        │
        └─┤ Factor_id             │
          │ (JOIN to env factors)  │
          │                        │
┌─────────┴─────────────┐  ┌───────┴─────────────┐
│ environmental_factors │  │  certificates       │
│ ────────────────────  │  │ ─────────────────── │
│ factor_id (PK)        │  │ certificate_id (PK)│
│ item_type             │  │ name                │
│ co2_saved_grams       │  │ description         │
│ water_saved_liters    │  │ criteria_type       │
│ data_source           │  │ criteria_value      │
│ is_active             │  │ design_style        │
└───────────────────────┘  └─────────────────────┘
```

---

## 🔄 Data Flow Examples

### **Example 1: User Creates Log Entry**
```
User visits log_entry.php
    ↓
SELECT from environmental_factors (to show options)
    ↓
User selects: bottle, quantity=5
    ↓
calculateImpact('bottle', 5) = [co2: 52.5, water: 125.0]
    ↓
INSERT INTO logs (user_id=1, factor_id=1, quantity=5, 
                  co2_saved=52.5, water_saved=125.0)
    ↓
checkAndAwardCertificates(user_id=1)
    ├─ SELECT SUM(quantity) FROM logs WHERE user_id=1
    ├─ Find certificates where criteria_value ≤ total_items
    ├─ Check if user doesn't already have it (LEFT JOIN)
    └─ INSERT into user_certificates if qualify
    ↓
Dashboard shows updated stats
    ├─ getUserStats(1) sums all logs for this user
    ├─ getUserCertificates(1) shows new award
    └─ Display updated dashboard
```

### **Example 2: Admin Awards Certificate**
```
Admin visits admin/certificates.php
    ↓
SELECT from users, certificates (for dropdowns)
    ↓
Admin selects: user_id=2, certificate_id=4
    ↓
Admin enters message: "Great work!"
    ↓
INSERT INTO user_certificates
    (user_id=2, certificate_id=4, awarded_by=1, 
     personal_message="Great work!")
    ↓
User visits certificates.php
    ↓
getUserCertificates(2) runs
    ├─ SELECT from user_certificates
    ├─ JOIN with certificates (for details)
    ├─ JOIN with users (for awarded_by_name)
    └─ Return complete certificate info
    ↓
[New certificate displayed to user]
```

### **Example 3: Dashboard Shows Stats**
```
User visits dashboard.php
    ↓
getUserStats(user_id) runs
    ├─ COUNT(*) FROM logs → total_entries
    ├─ SUM(quantity) → total_items
    ├─ SUM(co2_saved) → total_co2
    ├─ SUM(water_saved) → total_water
    └─ MIN(log_date) → first_entry
    ↓
[Stats displayed in cards]
    ↓
Query: SELECT from logs with JOIN to environmental_factors
    ├─ Gets last 10 entries
    ├─ Shows item type (via JOIN)
    ├─ Shows date, quantity, co2, water
    └─ Display in table
```

---

## 📈 Query Performance Notes

### **Important Indexes** (for fast queries)
```sql
-- These columns are frequently searched/joined
CREATE INDEX idx_user_logs ON logs(user_id);
CREATE INDEX idx_user_certs ON user_certificates(user_id);
CREATE INDEX idx_factor_logs ON logs(factor_id);
CREATE INDEX idx_cert_awards ON user_certificates(certificate_id);
```

### **Heavy Queries**
1. **getUserStats()** - Sums large logs table (optimize with index on user_id)
2. **getCommunityStats()** - Sums ALL logs (consider denormalization for reporting)
3. **getLeaderboard()** - GROUP BY with large dataset

### **Optimization Tips**
- Add indexes on foreign keys (user_id, factor_id, certificate_id)
- Consider caching frequent queries (community stats)
- Archive old logs if table grows huge
- Use LIMIT in queries to avoid returning too much data

---

## 🔐 Data Integrity Constraints

### **Primary Keys (PK)**
Ensure unique identification and prevent duplicates:
- users.user_id
- environmental_factors.factor_id
- logs.log_id
- certificates.certificate_id
- user_certificates.user_certificate_id

### **Foreign Keys (FK)**
Ensure referential integrity:
- logs.user_id must exist in users.user_id
- logs.factor_id must exist in environmental_factors.factor_id
- user_certificates.user_id must exist in users.user_id
- user_certificates.certificate_id must exist in certificates.certificate_id
- user_certificates.awarded_by must exist in users.user_id

### **Unique Constraints**
Prevent duplicate data:
- users.username (no two users with same username)
- users.email (no two users with same email)
- user_certificates(user_id, certificate_id) (user can't have same cert twice)

---

## 📝 Summary

| Table | Purpose | Rows | Growth | Key Relationship |
|-------|---------|------|--------|------------------|
| users | User accounts | Slow (100s) | 1-10/day | Center of auth |
| logs | Reduction entries | Fast (10,000s) | 100+/day | Records actions |
| environmental_factors | Impact data | Very slow (10s) | Static | Source of truth |
| certificates | Achievement definitions | Slow (100s) | 1-5/month | Goals |
| user_certificates | Award records | Fast (1,000s) | 10+/day | Achievements |

The database uses a **star-schema pattern** where:
- **users** and **certificates** are dimension tables (definitions)
- **logs** and **user_certificates** are fact tables (recorded actions/awards)
- **environmental_factors** is a reference table (constants)
