<?php
require_once __DIR__ . '/config.php';

echo "Adding kategori_id column to menus table...\n";

// Check if column already exists
$checkColumn = $conn->query("SHOW COLUMNS FROM menus LIKE 'kategori_id'");

if ($checkColumn && $checkColumn->num_rows > 0) {
    echo "Column 'kategori_id' already exists.\n";
} else {
    // Add the column
    $alterQuery = "ALTER TABLE menus ADD COLUMN kategori_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER menu_type";
    
    if ($conn->query($alterQuery)) {
        echo "Successfully added 'kategori_id' column to menus table.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
}

echo "\nDone!\n";
