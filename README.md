# 🎮 GameZone Blog — Mini CMS

A gaming blog CMS built with PHP and MySQL, featuring a dark cyberpunk aesthetic. Built as a portfolio project to demonstrate full-stack web development with vanilla PHP.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?style=flat&logo=mysql&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-Custom-1572B6?style=flat&logo=css3&logoColor=white)

---

## ✨ Features

### Public Blog
- Article listing with grid layout and pagination (6 per page)
- Category filter and keyword search
- Individual article page with rich HTML content
- Comment section (pending approval before visible)
- Responsive dark cyberpunk UI

### Admin Panel
- Secure login with session management
- Dashboard with stats (total articles, my articles, drafts, posted today)
- Create / edit / delete articles with **TinyMCE** rich text editor
- Draft & Published system
- Cover image upload
- Auto-generated URL slugs with real-time preview
- Category management
- Comment moderation (approve / reject / delete)
- Multi-user management

---

## 🔒 Security

- **Prepared Statements** — all database queries use `mysqli_prepare()`, no raw SQL injection possible
- **CSRF Protection** — every admin form protected with token verification via `config/csrf.php`
- **Password Hashing** — `password_hash()` with automatic plain-text migration on first login
- **XSS Prevention** — all output sanitized with `htmlspecialchars()`
- **Input Validation** — all user inputs validated and sanitized before processing
- **Session Protection** — all admin pages verify active session before rendering

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.x (vanilla, no framework) |
| Database | MySQL 8.x via MySQLi |
| Frontend | Vanilla CSS + JavaScript |
| Rich Text | TinyMCE 7 (CDN) |
| Fonts | Orbitron, Rajdhani, Share Tech Mono (Google Fonts) |
| Server | Apache (XAMPP for local) |

---

## 📁 Project Structure

```
mini-cms/
├── index.php               # Blog homepage with filter & pagination
├── post.php                # Single article page + comments
│
├── config/
│   ├── database.php        # DB connection (excluded from repo)
│   ├── csrf.php            # CSRF token helper
│   └── slug.php            # Slug generator + uniqueness checker
│
├── css/
│   └── style.css           # Full cyberpunk dark theme
│
├── uploads/                # User-uploaded images (excluded from repo)
│
└── admin/
    ├── login.php           # Admin authentication
    ├── logout.php
    ├── dashboard.php       # Article list + stats + filters
    ├── add_post.php        # Create article (TinyMCE)
    ├── edit_post.php       # Edit article (TinyMCE)
    ├── delete_post.php     # Delete article + image cleanup
    ├── categories.php      # Category management
    ├── comments.php        # Comment moderation
    └── users.php           # User management
```

---

## 🗄️ Database Schema

```sql
-- Users
CREATE TABLE users (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Categories
CREATE TABLE categories (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
);

-- Posts
CREATE TABLE posts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) DEFAULT NULL UNIQUE,
    content     TEXT,
    image       VARCHAR(255),
    author_id   INT DEFAULT NULL,
    category_id INT DEFAULT NULL,
    status      ENUM('draft','published') NOT NULL DEFAULT 'published',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id)   REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Comments
CREATE TABLE comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    post_id    INT NOT NULL,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    comment    TEXT NOT NULL,
    status     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);
```

---

## 🚀 Local Setup

### Requirements
- XAMPP (PHP 8.x + MySQL + Apache)
- Browser

### Steps

**1. Clone the repository**
```bash
git clone https://github.com/yourusername/mini-cms.git
```

**2. Move to XAMPP htdocs**
```
C:/xampp/htdocs/mini-cms
```

**3. Create the database**

Open `phpMyAdmin` → create a new database named `mini_cms` → run the SQL schema above.

**4. Configure database connection**

Create `config/database.php` (this file is excluded from the repo):
```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mini_cms');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if(!$conn){ die('Connection failed'); }
mysqli_set_charset($conn, 'utf8mb4');
```

**5. Open in browser**
```
http://localhost/mini-cms
```

Admin panel:
```
http://localhost/mini-cms/admin/login.php
```

---

## 📸 Screenshots

<img width="1919" height="940" alt="Mini-cms(2)" src="https://github.com/user-attachments/assets/a6340dee-cc74-4d60-83fe-5e78e66ad3e7" />
<img width="1918" height="942" alt="Mini-cms(1)" src="https://github.com/user-attachments/assets/7b853c87-494b-488a-90ec-b675a503af59" />


---

## 📝 License

This project is open source and available for portfolio and learning purposes.
