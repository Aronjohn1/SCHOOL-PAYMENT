================================================================================
  SCHOOL PAYMENT SYSTEM — README
  Built with PHP + MySQL + Tailwind CSS + FontAwesome
================================================================================

OVERVIEW
--------
A web-based school payment  system with two roles:
  - Admin    : Manage students, cashiers, payment types, transactions & reports
  - Cashier  : Process payments, view payment history, print receipts


--------------------------------------------------------------------------------
REQUIREMENTS
--------------------------------------------------------------------------------
  - XAMPP (Apache + MySQL) or any PHP 7.4+ / MySQL 5.7+ server
  - Web browser (Chrome, Firefox, Edge recommended)
  - Internet connection (for Tailwind CSS & FontAwesome CDN)


--------------------------------------------------------------------------------
INSTALLATION / SETUP
--------------------------------------------------------------------------------

  1. COPY PROJECT FOLDER
     Place the entire CASHIER_SYSTEM folder into your XAMPP htdocs directory:
       C:\xampp\htdocs\CASHIER_SYSTEM\

  2. START XAMPP
     Open XAMPP Control Panel and start:
       - Apache
       - MySQL

  3. CREATE DATABASE
     - Open your browser and go to: http://localhost/phpmyadmin
     - Click "New" and create a database named:  school_payment_db
     - Select the new database, click "Import"
     - Choose the file:  database.sql  (found in the root folder)
     - Click "Go" to import

  4. OPEN THE SYSTEM
     Visit: http://localhost/CASHIER_SYSTEM/login.php


--------------------------------------------------------------------------------
DEFAULT LOGIN ACCOUNTS
--------------------------------------------------------------------------------

  Role       Username     Password
  ---------  -----------  -----------
  Admin      admin        admin123
  Cashier    cashier1     cashier123

  NOTE: Passwords are stored as plain text in this version.
        Change them in phpMyAdmin > school_payment_db > users table
        before deploying to a live environment.


--------------------------------------------------------------------------------
FILE STRUCTURE
--------------------------------------------------------------------------------

  CASHIER_SYSTEM/
  │
  ├── login.php                   Login page (centered card, no left panel)
  ├── logout.php                  Destroys session and redirects to login
  ├── database.sql                Full database schema + sample data
  ├── README.txt                  This file
  │
  ├── includes/
  │   ├── db.php                  Database connection (MySQLi)
  │   └── auth.php                Session & login helper functions
  │
  ├── admin/
  │   ├── layout_header.php       Admin sidebar + topbar (responsive, hamburger)
  │   ├── layout_footer.php       Closes main content tags
  │   ├── dashboard.php           Overview stats and recent transactions
  │   ├── students.php            Add/Edit/Delete students (HS & College)
  │   ├── cashiers.php            Manage cashier accounts
  │   ├── payment_types.php       Manage payment type categories
  │   ├── transactions.php        View all transactions with filters & print
  │   └── reports.php             Daily & monthly collection reports
  │
  └── cashier/
      ├── layout_header.php       Cashier sidebar + topbar (responsive, hamburger)
      ├── layout_footer.php       Closes main content tags
      ├── dashboard.php           Cashier overview (today's collections)
      ├── process_payment.php     Search student + select multiple payment types
      ├── history.php             Cashier's own payment history with print
      └── receipt.php             Printable receipt after payment


--------------------------------------------------------------------------------
KEY FEATURES
--------------------------------------------------------------------------------

  STUDENT MANAGEMENT
  - Separate tabs for High School and College students
  - Grade 7–10 : Grade Level + Section
  - Grade 11–12: Grade Level + Strand (ABM, STEM, HUMSS, GAS, TVL, etc.) + Section
  - College    : Year Level + Course + Major
  - Bulk delete support
  - Balance auto-calculated (total_fee - total_paid)

  PAYMENT PROCESSING
  - Search student by ID or name
  - Select MULTIPLE payment types at once (e.g. Tuition + Miscellaneous)
  - Each payment type has its own amount field
  - Live total preview before confirming
  - Each payment type creates a separate transaction record
  - Auto-generated receipt number (RCP-YYYYMMDD-XXXXX)

  REPORTS
  - Daily Report  : All transactions for a selected date
  - Monthly Report: All transactions for a selected month
  - Breakdown by payment type
  - Printable report layout (sidebar hidden on print)

  TRANSACTIONS
  - Filter by date range, cashier, payment type
  - Print-friendly table (no sidebar on print)
  - Summary cards: total collected, count, today's total

  RESPONSIVE SIDEBAR
  - Desktop : Sidebar always visible
  - Mobile  : Sidebar hidden, hamburger (☰) button in topbar
  - Tap hamburger to open, tap overlay or ✕ to close
  - ESC key also closes the sidebar
  - Admin theme  : Dark navy  (#020538) with blue accents
  - Cashier theme: Dark green (#064e3b) with emerald accents


--------------------------------------------------------------------------------
DATABASE TABLES
--------------------------------------------------------------------------------

  users
    id, username, password, full_name, email, role (admin/cashier), status

  students
    id, student_id, full_name, student_level (highschool/college),
    grade_level, section, major, school_year,
    total_fee, total_paid, balance (GENERATED STORED), status

  payment_types
    id, type_name, description, status

  transactions
    id, receipt_no, student_id, cashier_id, payment_type_id,
    amount, payment_date, payment_time, notes


--------------------------------------------------------------------------------
DESIGN & TECH STACK
--------------------------------------------------------------------------------

  Frontend  : Tailwind CSS (CDN), FontAwesome 6.5.0 (CDN)
  Font      : Plus Jakarta Sans (Google Fonts)
  Charts    : Chart.js (dashboard)
  Backend   : PHP 7.4+ (no framework)
  Database  : MySQL / MariaDB (MySQLi)
  Server    : Apache (XAMPP recommended)

  Design system:
    - Rounded cards (rounded-2xl), subtle shadows, fade-up animations
    - Admin  → Indigo/Blue palette
    - Cashier → Emerald/Green palette
    - Print layout: sidebar hidden, clean table format


--------------------------------------------------------------------------------
NOTES & REMINDERS
--------------------------------------------------------------------------------

  - Tailwind CSS is loaded ONLY in layout_header.php — do NOT reload it in
    child pages as it causes style conflicts.
  - FontAwesome is loaded per-page AFTER the layout_header include.
  - Passwords are plain text — suitable for school demo/internal use only.
    For production, use PHP password_hash() / password_verify().
  - The balance column in students is a MySQL GENERATED STORED column:
      balance = total_fee - total_paid
    Do not try to INSERT/UPDATE it directly.


--------------------------------------------------------------------------------
SUPPORT / CUSTOMIZATION
--------------------------------------------------------------------------------

  To add a new payment type:
    Admin Panel > Payment Types > Add New

  To add a new cashier:
    Admin Panel > Cashier Accounts > Add New

  To change school name/logo:
    Edit the sidebar-logo section in:
      admin/layout_header.php
      cashier/layout_header.php

  To change the database connection:
    Edit:  includes/db.php
    Default: host=localhost, user=root, pass=(empty), db=school_payment_db


================================================================================
  End of README
================================================================================