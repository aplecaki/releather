<?php

// Database settings. Set the DB_* environment variables on a real server;
// the defaults below match a local XAMPP/Herd install.
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'fullstack';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // Log the details, but do not show them to visitors.
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('The database is unavailable right now. Please try again later.');
}
