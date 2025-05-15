<?php
/**
 * Database Connection
 * 
 * Establishes connection to the MySQL database
 */

// Include configuration file if not already included
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config.php';
}

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    if (DEBUG_MODE) {
        die("Database Connection Failed: " . $conn->connect_error);
    } else {
        die("An error occurred. Please try again later.");
    }
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

/**
 * Execute a query and return the result
 * 
 * @param string $sql SQL query to execute
 * @param array $params Parameters to bind (optional)
 * @param string $types Types of parameters (optional, default: 's' for all)
 * @return mixed Returns mysqli_result object or boolean
 */
function executeQuery($sql, $params = [], $types = '') {
    global $conn;
    
    if (empty($params)) {
        return $conn->query($sql);
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }
    
    // If types not specified, assume all strings
    if (empty($types)) {
        $types = str_repeat('s', count($params));
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}

/**
 * Get a single row from the database
 * 
 * @param string $sql SQL query to execute
 * @param array $params Parameters to bind (optional)
 * @param string $types Types of parameters (optional)
 * @return array|null Returns associative array or null if not found
 */
function getRow($sql, $params = [], $types = '') {
    $result = executeQuery($sql, $params, $types);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Get multiple rows from the database
 * 
 * @param string $sql SQL query to execute
 * @param array $params Parameters to bind (optional)
 * @param string $types Types of parameters (optional)
 * @return array Returns array of associative arrays
 */
function getRows($sql, $params = [], $types = '') {
    $result = executeQuery($sql, $params, $types);
    $rows = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}

/**
 * Insert data into the database
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return int|false Returns the inserted ID or false on failure
 */
function insertData($table, $data) {
    global $conn;
    
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    
    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }
    
    // Assume all values are strings for simplicity
    $types = str_repeat('s', count($data));
    $stmt->bind_param($types, ...array_values($data));
    
    $success = $stmt->execute();
    $id = $success ? $conn->insert_id : false;
    $stmt->close();
    
    return $id;
}

/**
 * Update data in the database
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value to update
 * @param string $where WHERE clause
 * @param array $whereParams Parameters for WHERE clause
 * @return bool Returns true on success, false on failure
 */
function updateData($table, $data, $where, $whereParams = []) {
    global $conn;
    
    $set = [];
    foreach (array_keys($data) as $column) {
        $set[] = "{$column} = ?";
    }
    
    $setStr = implode(', ', $set);
    $sql = "UPDATE {$table} SET {$setStr} WHERE {$where}";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }
    
    // Combine data values and where params
    $params = array_merge(array_values($data), $whereParams);
    
    // Assume all values are strings for simplicity
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Delete data from the database
 * 
 * @param string $table Table name
 * @param string $where WHERE clause
 * @param array $params Parameters for WHERE clause
 * @return bool Returns true on success, false on failure
 */
function deleteData($table, $where, $params = []) {
    global $conn;
    
    $sql = "DELETE FROM {$table} WHERE {$where}";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }
    
    if (!empty($params)) {
        // Assume all values are strings for simplicity
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}
?>