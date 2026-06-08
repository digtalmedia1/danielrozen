# Daniel Rozen Booking System - Installation Guide

This document provides instructions for installing and configuring the private Admin Panel and the public Booking System.

## Created Files
- `admin/`: Admin panel pages.
- `booking/`: Public booking page.
- `api/`: Backend API endpoints.
- `config/`: Configuration files.
- `includes/`: Core logic.
- `install.sql`: Database schema.
- `.env.example`: Template for environment variables.

## Installation Steps

### 1. Database Setup in Hostinger
1. Go to **MySQL Databases** in hPanel and create a new database.
2. Open **phpMyAdmin** and import `install.sql`.

### 2. Configure Environment Variables
1. Rename `.env.example` to `.env`.
2. Fill in database credentials and a secure `SESSION_SECRET`.

### 3. Create First Admin User
Run the following SQL in phpMyAdmin (replacing with your details and a hashed password):
```sql
INSERT INTO admin_users (name, email, password_hash) VALUES ("Daniel", "email@example.com", "HASH");
```

### 4. Upload
Upload all files to `public_html` via FTP or File Manager.

## Verification
- Admin: `yourdomain.com/admin/login.php`
- Public: `yourdomain.com/booking/`
