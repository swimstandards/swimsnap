<?php
// Show all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check extension
if (!extension_loaded("mongodb")) {
  die("❌ MongoDB extension not loaded");
}

require '../vendor/autoload.php'; // if you're using Composer with mongodb/mongodb

try {
  $client = new MongoDB\Client("mongodb://localhost:27017");
  $dbs = $client->listDatabases();

  echo "✅ Connected to MongoDB<br>";
  echo "<strong>Databases:</strong><ul>";
  foreach ($dbs as $db) {
    echo "<li>" . $db->getName() . "</li>";
  }
  echo "</ul>";
} catch (Exception $e) {
  echo "❌ Connection failed: " . $e->getMessage();
}
