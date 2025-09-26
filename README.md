# Bill Splitter Application

A modern, full-stack web application for splitting bills among friends and family members with real-time data integration and beautiful user interface.

## 🚀 System Overview

This is a complete bill splitting system built with PHP backend and modern JavaScript frontend, featuring:

- **Real-time Dashboard** - Live data from database, no sample data
- **Beautiful UI/UX** - Modern design with toast notifications
- **Secure Authentication** - User registration, login, and guest access
- **Bill Management** - Create, edit, and manage expense groups
- **Responsive Design** - Works perfectly on desktop and mobile
- **Professional Notifications** - Toast system instead of browser alerts

## ✨ Key Features

### 🎯 Core Functionality
- **User Registration & Login** - Secure authentication system
- **Guest Access** - Join bills using bill codes without registration
- **Bill Creation** - Create new expense groups with multiple currencies
- **Real-time Dashboard** - Live data from database with user balances
- **Expense Tracking** - Add and manage expenses within groups
- **Balance Calculation** - Automatic calculation of who owes what

### 🎨 User Experience
- **Modern Toast Notifications** - Beautiful success/error messages
- **Responsive Design** - Mobile-first approach
- **Smooth Animations** - Professional slide-in/out effects
- **Intuitive Interface** - Clean, easy-to-use design
- **Real-time Updates** - Instant feedback on all actions

### 🔒 Security Features
- **Password Hashing** - Secure password storage
- **Session Management** - Proper user session handling
- **Input Validation** - Server-side validation for all inputs
- **SQL Injection Protection** - Prepared statements throughout
- **File Protection** - .htaccess security rules

## 🛠️ Technical Stack

### Backend
- **PHP 7.4+** - Server-side logic
- **MySQL** - Database management
- **PDO** - Database abstraction layer
- **MVC Architecture** - Clean separation of concerns

### Frontend
- **Vanilla JavaScript** - Modern ES6+ features
- **CSS3** - Advanced styling with animations
- **HTML5** - Semantic markup
- **Chart.js** - Data visualization
- **Fetch API** - Modern HTTP requests

### Architecture
- **MVC Pattern** - Model-View-Controller separation
- **RESTful API** - Clean API endpoints
- **Singleton Database** - Efficient connection management
- **Error Handling** - Comprehensive error management

## 📁 Project Structure

```
BS/
├── Controller/
│   ├── AuthController.php      # Authentication & user management
│   ├── BillController.php      # Bill creation & management
│   ├── DashboardController.php # Dashboard data & analytics
│   ├── GuestController.php     # Guest access functionality
│   └── PasswordController.php  # Password reset functionality
├── Model/
│   ├── Database.php            # Database connection (Singleton)
│   ├── UserModel.php           # User database operations
│   ├── BillModel.php           # Bill database operations
│   └── EmailService.php        # Email sending service
├── View/
│   ├── index.html              # Landing page
│   ├── login.html              # Login & guest access
│   ├── register.html           # User registration
│   └── dashboard.html          # Main application dashboard
├── Public/
│   ├── CSS/
│   │   ├── default-css.css     # Global styles & variables
│   │   ├── login.css           # Authentication pages
│   │   ├── register.css        # Registration page
│   │   ├── dashboard.css       # Dashboard & toast notifications
│   │   └── style.css           # Additional styles
│   ├── JS/
│   │   ├── form.js             # Form handling & validation
│   │   └── dashboard.js        # Dashboard functionality
│   └── Images/                 # Application assets
├── config.php                  # Application configuration
├── .htaccess                   # Apache security & routing
├── bill_splitter.sql           # Database schema
└── README.md                   # This file
```

## 🚀 Installation & Setup

### Prerequisites
- **XAMPP/WAMP** - Local development server
- **PHP 7.4+** - Server-side language
- **MySQL 5.7+** - Database server
- **Modern Browser** - Chrome, Firefox, Safari, Edge

### Installation Steps

1. **Clone/Download** the project to your web server directory
   ```bash
   # For XAMPP
   C:\xampp\htdocs\BS\
   ```

2. **Database Setup**
   ```sql
   -- Import the database schema
   mysql -u root -p < bill_splitter.sql
   ```

3. **Configuration**
   ```php
   // Update config.php with your database credentials
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'bill_splitter');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

4. **Start Services**
   - Start Apache and MySQL in XAMPP
   - Access: `http://localhost/BS/`

## 🎯 Usage Guide

### For Users
1. **Register** - Create a new account
2. **Login** - Access your dashboard
3. **Create Groups** - Start new bill splitting groups
4. **Add Expenses** - Track shared expenses
5. **View Balances** - See who owes what

### For Guests
1. **Get Bill Code** - From a group member
2. **Guest Access** - Enter code on login page
3. **View Group** - See expenses and your share
4. **No Registration** - Access without creating account

## 🔧 Recent Improvements

### ✅ Backend Integration
- **Real Database Connection** - No more sample data
- **Live User Authentication** - Secure login system
- **Dynamic Bill Creation** - Real bill management
- **Balance Calculations** - Automatic debt tracking

### ✅ User Interface
- **Toast Notifications** - Professional message system
- **Responsive Design** - Mobile-friendly interface
- **Smooth Animations** - Enhanced user experience
- **Error Handling** - User-friendly error messages

### ✅ Security Enhancements
- **Password Hashing** - Secure password storage
- **Input Validation** - Server-side validation
- **SQL Injection Protection** - Prepared statements
- **Session Security** - Proper session management

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check MySQL service is running
   - Verify credentials in config.php
   - Ensure database exists

2. **Permission Denied**
   - Check file permissions
   - Verify .htaccess is working
   - Check Apache error logs

3. **JavaScript Errors**
   - Check browser console
   - Verify all files are loaded
   - Check network requests

### Debug Mode
```php
// Enable debug mode in config.php
define('ENVIRONMENT', 'development');
```

## 📊 Database Schema

### Tables
- **users** - User accounts and profiles
- **bills** - Bill groups and metadata
- **bill_participants** - Group memberships
- **guest_access** - Temporary guest access
- **password_resets** - Password reset tokens

### Key Features
- **Foreign Key Constraints** - Data integrity
- **Unique Constraints** - Prevent duplicates
- **Auto-increment IDs** - Efficient indexing
- **Timestamp Tracking** - Audit trail

## 🔮 Future Enhancements

- **Real-time Notifications** - WebSocket integration
- **Mobile App** - React Native version
- **Payment Integration** - Stripe/PayPal support
- **Advanced Analytics** - Spending insights
- **Multi-language Support** - Internationalization

## 📝 License

This project is open source and available under the MIT License.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For issues and questions:
- Check the troubleshooting section
- Review error logs
- Test with debug mode enabled
- Verify all prerequisites are met

---

**Built with ❤️ for easy bill splitting among friends and family!**