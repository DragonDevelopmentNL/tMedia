# TBerichten - Social Media Platform

TBerichten is a modern social media platform built with PHP, MySQL, and CSS. It provides a clean and user-friendly interface for users to connect, share thoughts, and interact with others.

## Features

- User Authentication
  - Registration with username, email, and password
  - Secure login system
  - Password recovery functionality

- Profile Management
  - Customizable profile pictures
  - Bio and personal information
  - Password change option
  - Profile settings

- Social Features
  - Create posts (up to 200 characters)
  - Like and share posts
  - Follow/unfollow other users
  - View user profiles
  - News feed with latest posts

- Modern Design
  - Responsive layout
  - Clean and intuitive interface
  - Mobile-friendly design

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/tberichten.git
```

2. Create a MySQL database and import the structure:
```bash
mysql -u yourusername -p yourdatabase < database.sql
```

3. Configure the database connection:
   - Open `config/database.php`
   - Update the database credentials:
     ```php
     define('DB_SERVER', 'localhost');
     define('DB_USERNAME', 'your_username');
     define('DB_PASSWORD', 'your_password');
     define('DB_NAME', 'your_database');
     ```

4. Set up the uploads directory:
```bash
mkdir uploads
chmod 777 uploads
```

5. Start your web server and visit the site:
```bash
php -S localhost:8000
```

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Security Features

- Password hashing using PHP's password_hash()
- SQL injection prevention with prepared statements
- XSS protection with htmlspecialchars()
- Secure session management
- Input validation and sanitization

## File Structure

```
tberichten/
├── api/
│   └── like.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── config/
│   └── database.php
├── uploads/
├── index.php
├── login.php
├── register.php
├── profile.php
├── create-post.php
├── logout.php
├── database.sql
└── README.md
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- PHP Documentation
- MySQL Documentation
- Modern CSS Techniques
- Web Security Best Practices
