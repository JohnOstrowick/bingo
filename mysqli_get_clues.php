<?php
header("Content-Type: application/json");

if (!isset($_GET['questionType']) || !is_numeric($_GET['questionType'])) {
    echo json_encode([]);
    exit;
}

$questionType = (int) $_GET['questionType'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=bingo;charset=utf8mb4", "bingo_user", "securepassword", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("SELECT clue_string FROM clues WHERE question_type = ? ORDER BY RAND() LIMIT 30");
    $stmt->execute([$questionType]);
    
    $clues = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($clues);

} catch (Exception $e) {
    echo json_encode([]);
}
?>
