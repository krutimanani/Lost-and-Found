# Rajkot E Milaap - Lost & Found Portal

A comprehensive web-based platform designed to help reunite lost items with their owners in Rajkot, Gujarat. The system facilitates communication between citizens, police officers, and administrators to efficiently manage lost and found items.

## 🌟 Features

### For Citizens
- **Report Lost Items**: Submit detailed reports about lost items with photos, descriptions, and location information
- **Report Found Items**: Report items you've found to help reunite them with owners
- **Search Functionality**: Search for lost or found items by category, location, keywords, and date
- **Claim Items**: Claim found items with evidence and proof of ownership
- **Track Claims**: Monitor the status of your claims (pending, approved, rejected, ready for collection)
- **Notifications**: Receive real-time notifications about matches, claim status, and report approvals
- **Dashboard**: View your reports, claims, and recent activity in one place
- **Multi-language Support**: Access the platform in English, Hindi, or Gujarati

### For Police Officers
- **Review Reports**: View and manage all lost and found item reports
- **Review Claims**: Verify and approve/reject citizen claims on found items
- **Match Items**: Create matches between lost and found items
- **Upload Custody Items**: Upload items currently in police custody
- **Manage Custody Items**: Track and manage items in police custody
- **Notifications**: Stay updated on new reports and claims requiring attention

### For Administrators
- **Approve Reports**: Review and approve/reject citizen-submitted reports before they go public
- **User Management**: Manage citizen accounts (activate/deactivate users)
- **Police Management**: Add, edit, and manage police officer accounts
- **Category Management**: Create and manage item categories with multilingual support
- **System Settings**: Configure site settings and preferences
- **Activity Logs**: Monitor system activity and user actions
- **Dashboard Analytics**: View statistics on lost items, found items, matches, and users

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: 
  - HTML5
  - CSS3 (Bootstrap 5.3)
  - JavaScript
  - Font Awesome Icons
- **Internationalization**: Custom i18n system supporting multiple languages
- **File Upload**: Image upload with validation (JPG, JPEG, PNG, GIF, max 5MB)

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx web server
- mod_rewrite enabled (for clean URLs)
- GD Library (for image processing)
- PDO MySQL extension

## 🚀 Installation

### 1. Clone or Download the Project

```bash
git clone <repository-url>
cd lostfound
```

### 2. Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE rajkot_emilaap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u root -p rajkot_emilaap < database/rajkot_emilaap.sql
```

Or use phpMyAdmin to import the `database/rajkot_emilaap.sql` file.

### 3. Configuration

1. **Database Configuration** (`config/database.php`):
   - Update database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'rajkot_emilaap');
   ```

2. **Site Configuration** (`config/config.php`):
   - Update the site URL:
   ```php
   define('SITE_URL', 'http://localhost/lostfound/');
   ```
   - Adjust file upload settings if needed:
   ```php
   define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
   ```

### 4. File Permissions

Ensure the `uploads/` directory and its subdirectories are writable:

```bash
chmod -R 755 uploads/
chmod -R 755 uploads/lost/
chmod -R 755 uploads/found/
```

### 5. Web Server Configuration

#### Apache (.htaccess)
Ensure `.htaccess` is enabled and configured properly.

#### Nginx
Configure Nginx to point to the project directory and enable PHP processing.

### 6. Access the Application

Open your browser and navigate to:
```
http://localhost/lostfound/
```

## 👥 User Roles

### Citizen
- Register and create an account
- Report lost/found items
- Search and claim items
- Manage their reports and claims

### Police Officer
- Review and manage reports
- Verify and approve/reject claims
- Create matches between items
- Upload custody items

### Administrator
- Approve/reject reports
- Manage all users and police officers
- Manage categories and system settings
- View system analytics

## 🌐 Language Support

The platform supports three languages:
- **English** (en)
- **Hindi** (हिंदी)
- **Gujarati** (ગુજરાતી)

Users can switch languages using the language selector in the navigation bar. Language preferences are saved per user.

## 📁 Project Structure

```
lostfound/
├── admin/              # Administrator modules
│   ├── approve-lost.php
│   ├── approve-found.php
│   ├── categories.php
│   ├── dashboard.php
│   ├── police-management.php
│   ├── settings.php
│   └── users.php
├── assets/             # Static assets
│   ├── css/
│   ├── js/
│   └── images/
├── auth/               # Authentication pages
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── forgotpassword.php
│   └── resetpassword.php
├── citizen/            # Citizen user modules
│   ├── dashboard.php
│   ├── report-lost.php
│   ├── report-found.php
│   ├── search.php
│   ├── claim-item.php
│   ├── my-reports.php
│   ├── my-claims.php
│   ├── notifications.php
│   └── view-report.php
├── config/              # Configuration files
│   ├── config.php
│   ├── database.php
│   └── i18n/           # Translation files
│       ├── i18n.php
│       ├── en.php
│       ├── hi.php
│       └── gu.php
├── database/            # Database files
│   └── rajkot_emilaap.sql
├── includes/            # Shared includes
│   ├── header.php
│   ├── footer.php
│   └── functions.php
├── police/              # Police officer modules
│   ├── dashboard.php
│   ├── view-reports.php
│   ├── review-claims.php
│   ├── upload-custody-item.php
│   ├── custody-items.php
│   ├── notifications.php
│   └── report-details.php
├── uploads/             # Uploaded files
│   ├── lost/
│   └── found/
├── index.php            # Landing page
└── README.md
```

## 🔐 Default Credentials

After installation, you may need to create admin accounts through the database or registration system. Check the database for default admin credentials or create new ones.

## 🔒 Security Features

- Password hashing using PHP's password_hash()
- SQL injection prevention using PDO prepared statements
- XSS protection with htmlspecialchars()
- File upload validation (type and size)
- Session-based authentication
- Role-based access control
- CSRF protection (recommended to add)

## 📝 Database Schema

Key tables:
- `users` - Citizen accounts
- `police` - Police officer accounts
- `admins` - Administrator accounts
- `lost_items` - Lost item reports
- `found_items` - Found item reports
- `item_claims` - Claims on found items
- `matched_reports` - Matches between lost and found items
- `categories` - Item categories
- `locations` - Location data
- `notifications` - User notifications
- `activity_log` - System activity logs

## 🎨 Customization

### Adding New Languages

1. Create a new translation file in `config/i18n/` (e.g., `fr.php`)
2. Add the language code to `config/i18n/i18n.php`:
   - Update `loadTranslations()` function
   - Update `setLanguage()` function
   - Update `getSupportedLanguages()` function
3. Add the language option to `includes/header.php`

### Styling

Customize the appearance by modifying:
- `assets/css/style.css` - Main stylesheet
- Bootstrap classes in PHP files
- Color scheme in `config/config.php` (if applicable)

## 🐛 Troubleshooting

### Database Connection Issues
- Verify database credentials in `config/database.php`
- Ensure MySQL service is running
- Check database name matches

### File Upload Issues
- Check `uploads/` directory permissions
- Verify `MAX_FILE_SIZE` in `config/config.php`
- Check PHP `upload_max_filesize` in php.ini

### Language Not Switching
- Clear browser cache
- Check session is enabled
- Verify translation files exist

## 📄 License

This project is developed for Rajkot E Milaap. All rights reserved.

## 👨‍💻 Developers

Developed by:
- **Kriti Manani** (90)
- **Dhanvi Amarseda** (65)

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📞 Support

For support, email: contact@rajkotemilaap.com

## 🔄 Version History

- **v1.0** - Initial release with multi-language support (English, Hindi, Gujarati)
  - Citizen reporting and claiming system
  - Police review and matching system
  - Admin management panel
  - Notification system
  - Multi-language i18n support

---

**Note**: This is a community-driven project to help reunite lost items with their owners in Rajkot. Use responsibly and help make the community a better place!

