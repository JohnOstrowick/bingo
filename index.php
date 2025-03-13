<?php
// Database connection using MySQLi
$mysqli = new mysqli("localhost", "bingo_user", "securepassword", "bingo");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bingo Card Generator</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 20px; }
        select, button { font-size: 18px; padding: 5px; }
    </style>
</head>
<body>

    <h1>Welcome to Bingo Card Generator</h1>

    <form id="bingoForm" action="generate.php" method="GET">
        <label for="questionType">Question type:</label>
        <select id="questionType" name="questionType" required>
            <option value="">Loading...</option>
        </select>
        <button type="submit">GO</button>
    </form>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            fetch("get_question_types.php")
                .then(response => response.json())
                .then(data => {
                    let dropdown = document.getElementById("questionType");
                    dropdown.innerHTML = "<option value=''>Select...</option>";
                    data.forEach(type => {
                        dropdown.innerHTML += `<option value="${type.questiontype_id}">${type.questiontype_string}</option>`;
                    });
                })
                .catch(() => {
                    document.getElementById("questionType").innerHTML = "<option value=''>Error loading</option>";
                });
        });
    </script>

</body>
</html>
