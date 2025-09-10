<?php
// author Lycradiata
// Configuration for the URL shortener

return [
    // Path to SQLite database file (writable by the webserver)
    'db_path' => __DIR__ . '/data/urls.db',

    // Minimum and maximum auto-generated code length
    'code_length' => 6,

    // Allowed characters for generated codes (base62)
    'alphabet' => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',

    // Admin password (change this to something strong)
    'admin_password' => 'change_this_admin_password',

    // Maximum custom alias length
    'max_alias_length' => 32,

    // Maximum URL length allowed
    'max_url_length' => 2048,
];
