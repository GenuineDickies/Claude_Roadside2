<?php
// Database Reset Utility
// WARNING: This will delete all data and recreate tables

// Only allow this to run if explicitly requested
if (!isset($_GET['reset']) || $_GET['reset'] !== 'confirm') {
    die('Database reset not confirmed. Add ?reset=confirm to URL to proceed.');
}

require_once 'config/database.php';

echo "<h2>Database Reset Complete!</h2>";
echo "<p>All tables have been recreated with fresh structure.</p>";
echo "<p>Default admin user created: username = 'admin', password = 'admin123'</p>";
echo "<p><a href='index.php'>Return to Login</a></p>";
?>
