<?php
/**
 * Database Setup Script
 * Run this file to create and populate the database
 */

require_once '../config/config.php';

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Setting up Mega School Plaza Database...</h2>";
    
    // Read and execute schema file
    $schema_sql = file_get_contents('01_create_database.sql');
    if ($schema_sql === false) {
        throw new Exception("Could not read schema file");
    }
    
    // Split SQL statements and execute them
    $statements = explode(';', $schema_sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<p>✅ Database schema created successfully!</p>";
    
    // Read and execute seed data file
    $seed_sql = file_get_contents('02_seed_data.sql');
    if ($seed_sql === false) {
        throw new Exception("Could not read seed data file");
    }
    
    // Split SQL statements and execute them
    $statements = explode(';', $seed_sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<p>✅ Sample data inserted successfully!</p>";
    
    echo "<h3>Database Setup Complete!</h3>";
    echo "<p><strong>Demo Accounts:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@megaschoolplaza.com / password</li>";
    echo "<li><strong>Manager:</strong> manager@megaschoolplaza.com / password</li>";
    echo "<li><strong>Tenant:</strong> tenant@megaschoolplaza.com / password</li>";
    echo "</ul>";
    echo "<p><a href='../index.php'>Go to Homepage</a> | <a href='../auth/login.php'>Login</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
}
?>
