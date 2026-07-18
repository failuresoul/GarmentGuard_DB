# GarmentGuard - Garment Factory Compliance & Audit Management System

**GarmentGuard** is a comprehensive Database Management System tailored for the RMG (Ready-Made Garments) sector in Bangladesh. This system ensures safety standards, compliance tracking, and fair worker treatment across multiple factories, bridging the gap between factory workers, inspectors, compliance officers, and international buyers.

---

## 🌟 Key Features

* **Multi-Role Authentication**: 5 distinct roles (Admin, Compliance Officer, Inspector, Buyer Representative, Worker) with strict Role-Based Access Control (RBAC).
* **Compliance Tracking**: Real-time compliance scoring based on inspector audits. Factories are categorized into *Compliant, Review Needed, At Risk,* or *Non-Compliant*.
* **Safety Equipment Monitoring**: Tracks expiry dates and physical conditions of safety gear (e.g., Fire Extinguishers, Smoke Detectors) with automated alerts for critical failures.
* **Worker & Salary Management**: Complete tracking of worker records, designations, and automated net salary calculations based on overtime and deductions.
* **Grievance Handling System**: Secure pipeline for workers to lodge complaints (Harassment, Salary Delay, Facility issues) and track their resolution status through an immutable audit log.
* **Certifications**: Monitors factory certifications (e.g., WRAP, OEKO-TEX, ISO) to ensure international buyers are working with legally sound and ethical factories.

---

## 🛠️ Technology Stack

* **Database**: Oracle Database (19c/21c) — utilizing advanced PL/SQL constructs (Cursors, Associative Arrays, Nested Tables, Object Types, Procedures, Functions, and Triggers).
* **Backend**: Core PHP 8+ with OCI8 extension for Oracle Database connectivity.
* **Frontend**: HTML5, CSS3, JavaScript (Vanilla), integrating a beautiful glassmorphism-inspired UI with modern typography.
* **Authentication**: Secure bcrypt password hashing.

---

## 🗄️ Database Architecture

The core of GarmentGuard runs on a robust relational Oracle database with the following structure:

### Advanced PL/SQL Implementation
The database is heavily automated and secured using Oracle PL/SQL features:
* **Triggers**: Automates total worker count, validates certification expiry dates, calculates net salaries dynamically, and maintains an immutable grievance audit log.
* **Functions (`function.sql`)**: Calculates factory compliance scores, processes YTD worker salaries, and flags safety equipment nearing expiry utilizing `Cursors`, `Associative Arrays`, and `VARRAYs`.
* **Procedures (`procedure.sql`)**: Handles complex multi-table mutations like hiring workers, submitting grievances, bulk-paying salaries, and generating complex factory reports using `Object Types`, `Table of Records`, and `Nested Tables`.

---

## 🔐 System Roles & Default Credentials

Passwords in the database are hashed using bcrypt. You can use the following default credentials to explore the system:

| Role | Username | Password | Access Level |
|------|----------|----------|--------------|
| **Admin** | `admin` | `Admin@2026` | Full system control, user management |
| **Compliance Officer** | `compliance_officer` | `Comply@2026` | Factory-level management, resolves grievances |
| **Inspector** | `inspector` | `Inspect@2026` | Conducts audits, inspects safety equipment |
| **Buyer Rep** | `buyer_rep` | `Buyer@2026` | Views factory compliance scores & certificates |
| **Worker** | `worker_user` | `Worker@2026` | Submits grievances, views personal salary |

---

## 🚀 Installation & Setup Guide

### 1. Database Setup (Oracle)
1. Open Oracle SQL Developer or SQL*Plus.
2. Connect to your desired schema/user.
3. Run the master setup script to create tables, constraints, sequences, and seed all data:
   ```sql
   @E:\GarmentGuard_DB\database\table and insert.sql
   ```
4. Compile the Triggers, Functions, and Procedures:
   ```sql
   @E:\GarmentGuard_DB\database\trigger.sql
   @E:\GarmentGuard_DB\database\function.sql
   @E:\GarmentGuard_DB\database\procedure.sql
   ```

### 2. Web Application Setup (PHP)
1. Ensure XAMPP or an equivalent server is installed with the **OCI8 extension** enabled in `php.ini`.
2. Place the `GarmentGuard_DB` folder into your `htdocs` directory (or run a standalone PHP server).
3. Update your database connection string in `backend/config/db.php`:
   ```php
   $db_user = 'your_oracle_username';
   $db_pass = 'your_oracle_password';
   $db_host = 'localhost/XEPDB1'; // Update to your Oracle SID/Service Name
   ```
4. Start the server and navigate to `http://localhost/GarmentGuard_DB/frontend/pages/auth/login.php`.

---

## 📜 Academic Integrity & License
This project was built as an academic Database Management Systems course project demonstrating advanced Oracle Database and PHP integration. 
