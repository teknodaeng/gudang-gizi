<?php
/**
 * Database Configuration
 * Gudang Gizi - Sistem Manajemen Stok
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'CDP17s1850913#^_^');
define('DB_NAME', 'gudang_gizi');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db(DB_NAME);

// Set charset
$conn->set_charset("utf8mb4");

/**
 * Execute query with prepared statement
 */
function query($sql, $params = [], $types = "")
{
    global $conn;

    if (empty($params)) {
        $result = $conn->query($sql);
        return $result;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    return $result;
}

/**
 * Get single row
 */
function fetchOne($sql, $params = [], $types = "")
{
    $result = query($sql, $params, $types);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Get all rows
 */
function fetchAll($sql, $params = [], $types = "")
{
    $result = query($sql, $params, $types);
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

/**
 * Insert and get last ID
 */
function insertGetId($sql, $params = [], $types = "")
{
    global $conn;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    return false;
}

/**
 * Escape string
 */
function escape($string)
{
    global $conn;
    return $conn->real_escape_string($string);
}
?>