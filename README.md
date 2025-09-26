# Bill Splitter Application

A web application for splitting bills among friends and family members.

## Recent Fixes Applied

### 🔧 Critical Issues Fixed

1. **PHP Security Issues**
   - Fixed password verification in UserModel login method
   - Added proper password field selection in SQL queries
   - Created missing BillController.php and BillModel.php files

2. **Database Schema Issues**
   - Updated users table to match PHP code expectations
   - Added missing `username` field
   - Changed field names from `name`/`surname` to `first_name`/`last_name`
   - Updated guest_access table structure
   - Fixed foreign key constraints

3. **HTML Issues**
   - Removed reference to non-existent `app.js` file in dashboard.html

4. **JavaScript Issues**
   - Added missing form switching functionality for guest access
   - Fixed login/guest form toggle functionality

5. **CSS Issues**
   - Added proper Google Fonts import for Poppins font
   - Added missing utility classes for dashboard forms
   - Improved responsive design

### 🛡️ Security Enhancements

1. **Configuration Management**
   - Created centralized config.php file
   - Added security headers
   - Implemented proper session configuration

2. **File Security**
   - Added .htaccess file with security rules
   - Protected sensitive files (config.php, *.sql, logs)
   - Added compression and caching rules

3. **Database Security**
   - Updated Database class to use configuration constants
   - Improved error handling and logging

## Installation

1. Import the `bill_splitter.sql` file into your MySQL database
2. Update database credentials in `config.php`
3. Ensure your web server has proper permissions
4. Access the application through your web browser

## File Structure

```
BS/
├── Controller/
│   ├── AuthController.php    # Authentication logic
│   └── BillController.php    # Bill management logic
├── Model/
│   ├── Database.php          # Database connection
│   ├── UserModel.php         # User operations
│   ├── BillModel.php         # Bill operations
│   └── EmailService.php      # Email functionality
├── View/
│   ├── index.html            # Landing page
│   ├── login.html            # Login page
│   ├── register.html         # Registration page
│   └── dashboard.html        # Main dashboard
├── Public/
│   ├── CSS/                  # Stylesheets
│   ├── JS/                   # JavaScript files
│   └── Images/               # Image assets
├── config.php                # Application configuration
├── .htaccess                 # Apache security rules
└── bill_splitter.sql         # Database schema
```

## Features

- User registration and authentication
- Guest access to bills via bill codes
- Bill creation and management
- Participant management
- Expense tracking and splitting
- Responsive design
- Security best practices

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser

## Security Notes

- Change default database credentials in production
- Enable HTTPS in production environment
- Regularly update dependencies
- Monitor error logs
- Use strong passwords for database access
