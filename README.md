Simple PHP URL Shortener

A lightweight and self-contained URL shortener built with PHP and SQLite. Supports custom aliases, URL expiration, click tracking, and a basic admin panel. Designed for easy deployment with XAMPP or any PHP-enabled web server.

Features

Shorten any valid URL

Optional custom alias (letters, numbers, _, and - only)

Optional expiration date for URLs

Click tracking for all URLs

Basic admin panel to view recent URLs and stats

No external dependencies required (uses built-in SQLite)

Automatic database creation

Requirements

PHP 7.4 or higher

SQLite3

Apache or any web server that supports .htaccess rewrites

XAMPP recommended for local development

Installation

Download or clone the repository.

Copy the project folder into your web server root (e.g., htdocs in XAMPP):

C:\xampp\htdocs\url_shortener\


Ensure the data/ directory is writable by the web server. SQLite database will be created automatically.

Open config.php and set your desired admin_password and other settings:

return [
    'db_path' => __DIR__ . '/data/urls.db',
    'code_length' => 6,
    'alphabet' => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
    'admin_password' => 'change_this_admin_password',
    'max_alias_length' => 32,
    'max_url_length' => 2048,
];

Usage

Start your web server (e.g., Apache via XAMPP).

Open the application in your browser:

http://localhost/url_shortener/index.php


Enter a URL to shorten and optionally provide a custom alias or expiration date.

After submission, the shortened URL will be displayed.

Access the admin panel by entering the admin_password to view recent URLs and click stats.

File Structure
url_shortener/
│-- index.php          # Main page for creating URLs and admin panel
│-- redirect.php       # Handles short URL redirection
│-- helpers.php        # Common functions and database handling
│-- config.php         # Configuration settings
│-- .htaccess          # URL rewrite rules
└-- data/              # SQLite database storage (writable)

URL Redirection

Any short code like /abc123 or /my-link is automatically redirected to the original URL using redirect.php and .htaccess rewrite rules.

Notes

Database (data/urls.db) is automatically created on first run.

Custom aliases must be unique and can only contain letters, numbers, _ and -.

Expired URLs will no longer redirect.

Admin password is stored in plain text for simplicity. Consider securing it in production environments.
