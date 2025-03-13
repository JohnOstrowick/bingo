<?php
header("Content-Type: application/json");

if (!isset($_GET['questionType']) || !is_numeric($_GET['questionType'])) {
    echo json_encode([]);
    exit;
}

$questionType = (int) $_GET['questionType'];

// MySQLi Connection
$mysqli = new mysqli("localhost", "bingo_user", "securepassword", "bingo");

// Check connection
if ($mysqli->connect_error) {
    echo json_encode([]);
    exit;
}

// Prepare and execute the query
$stmt = $mysqli->prepare("SELECT clue_string, answer_string FROM clues WHERE question_type = ?");
$stmt->bind_param("i", $questionType);
$stmt->execute();
$result = $stmt->get_result();

$clues = [];
while ($row = $result->fetch_assoc()) {
    $clues[] = $row;
}

// Close connections
$stmt->close();
$mysqli->close();

// Output JSON response
echo json_encode($clues);
?>