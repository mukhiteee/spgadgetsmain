<?php

// ===================================================
// 1. DATABASE CONFIGURATION
// ===================================================

// Defined constants using your provided credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Set to your user
define('DB_PASS', '');     // Set to no password (empty string)
define('DB_NAME', 'spgadgets'); // Set to your database name

// ===================================================
// 2. CONNECTION FUNCTION
// ===================================================

/**
 * Establishes a PDO database connection.
 * @return PDO|null The PDO connection object or null on failure.
 */
function connectDB() {
    // MySQL DSN (Data Source Name) string
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    // PDO options for security and error handling
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Default to associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                    // Use native prepared statements for security
    ];

    try {
        // Attempt to create the PDO instance
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (\PDOException $e) {
        // Log the error and stop execution in a clean way
        die("Database connection failed: " . $e->getMessage());
    }
}

// ===================================================
// 3. CONNECTION TEST (FOR DEVELOPMENT ONLY)
// ===================================================

// This block executes if the file is accessed directly to test the connection.
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "<!DOCTYPE html><html><head><title>DB Test</title><style>body{font-family:sans-serif;background:#f0f4f8;padding:2rem;} .success{color:#155724;background:#d4edda;border:1px solid #c3e6cb;padding:15px;border-radius:8px;font-weight:bold;} .fail{color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;padding:15px;border-radius:8px;}</style></head><body>";

    try {
        $test_pdo = connectDB();

        // If the function returns a PDO object without throwing an error
        echo "<div class='success'>✅ **SUCCESS!** Connection established to Database: **" . DB_NAME . "** on **" . DB_HOST . "**.</div>";

    } catch (Exception $e) {
        // Fallback for any non-PDO related error (unlikely)
        echo "<div class='fail'>❌ Connection failed. Check your database server status.</div>";
    }

    echo "</body></html>";
}