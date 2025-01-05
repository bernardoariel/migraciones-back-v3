<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=portal_api4', 'root', '#Bg135790@');
    echo "Connected to the database successfully!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}