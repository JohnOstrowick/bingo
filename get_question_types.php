<?php
header("Content-Type: application/json");

// MySQLi Connection
$mysqli = new mysqli("localhost", "bingo_user", "securepassword", "bingo");

// Check connection
if ($mysqli->connect_error) {
    echo json_encode([]);
    exit;
}

// Query the database
$result = $mysqli->query("SELECT questiontype_id, questiontype_string FROM question_type ORDER BY questiontype_string");

// Fetch results
$questionTypes = [];
while ($row = $result->fetch_assoc()) {
    $questionTypes[] = $row;
}

// Close connection
$mysqli->close();

// Output JSON response
echo json_encode($questionTypes);
?>