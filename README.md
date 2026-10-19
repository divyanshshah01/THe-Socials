# The Socials

A modern, club-based collaborative social and media platform with real-time text chat, media feed, voice room synchronization, and interactive 3D web experience.

## Features
- **3D Interactive Landing Page**: Built with Three.js and GSAP.
- **Club Management**: Create custom clubs, unique join codes, and role-based permissions.
- **Real-Time Communication**:
  - General club channel chat
  - Direct messaging (DMs) between club members
  - Voice channel user status sync
- **Media Wall**: Upload photos and videos (with size & extension verification).
- **Secure Authentication**: Bcrypt password hashing (`PASSWORD_BCRYPT`, cost 12), parameterized PDO queries, and strict session validation.

## Tech Stack
- **Frontend**: HTML5, CSS3, Vanilla JavaScript, Three.js, GSAP.
- **Backend**: PHP 7.4+ / PHP 8.x (PDO MySQL).
- **Database**: MySQL 5.7+ / MariaDB.

## Installation & Setup

1. **Clone repository**:
   ```bash
   git clone https://github.com/divyanshshah01/THe-Socials.git
   cd THe-Socials
   ```

2. **Database Setup**:
   - Create a database in MySQL (e.g. `the_socials`).
   - Import the schema from [`schema.sql`](schema.sql):
     ```bash
     mysql -u root -p the_socials < schema.sql
     ```

3. **Configure Database Connection**:
   - Copy `config.example.php` to `config.php`:
     ```bash
     cp config.example.php config.php
     ```
   - Update `config.php` with your database credentials.

4. **Web Server**:
   - Place inside your web server document root (e.g., `xampp/htdocs/the-socials`).
   - Start Apache and MySQL in XAMPP.
   - Access via `http://localhost/the-socials/index.html` or `http://localhost/the-socials/login.html`.

## Security Measures
- **Ignored Secrets**: `config.php` is strictly ignored by `.gitignore` to prevent secret leaks.
- **Prepared Statements**: All database operations use PDO prepared queries to mitigate SQL Injection.
- **XSS Protection**: HTML entities escaping across DOM updates.
- **Upload Restrictions**: Upload directory restricts execution of executable scripts via `.htaccess` and whitelists media types.
