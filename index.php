<?php
// Database connection
$mysqli = new mysqli("localhost", "bingo_user", "securepassword", "bingo");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Fetch question types
$result = $mysqli->query("SELECT questiontype_id, questiontype_string FROM question_type ORDER BY questiontype_string");
$questionTypes = [];
while ($row = $result->fetch_assoc()) {
    $questionTypes[] = $row;
}
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Welcome to the Game Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 20px;
        }
        select, button, input[type=radio] {
            font-size: 18px;
            padding: 5px;
        }
    </style>
    <script>
        function handleQuestionTypeChange() {
            const dropdown       = document.getElementById("questionType");
            const chosenValue    = dropdown.value;
            const crosswordRadio = document.getElementById("crossword");
            const bingoRadio     = document.getElementById("bingo");

            if (chosenValue === "8") {
                crosswordRadio.disabled = true;
                bingoRadio.checked = true;
            } else {
                crosswordRadio.disabled = false;
            }
        }

        function setFormAction() {
            const bingoRadio = document.getElementById("bingo");
            const form       = document.getElementById("gameForm");
            
            // If Bingo is selected, go to generate.php
            // Otherwise, go to crossword.php
            form.action = bingoRadio.checked ? "generate.php" : "crossword.php";
        }

        document.addEventListener("DOMContentLoaded", function(){
            handleQuestionTypeChange(); // Ensure correct initial state
        });
    </script>
</head>
<body>

<h1>Welcome to the Game Generator</h1>

<form id="gameForm" method="GET" onsubmit="setFormAction()">
    <div>
        <label>
            <input type="radio" name="gameMode" id="bingo" value="bingo" checked />
            Bingo
        </label>
        <label>
            <input type="radio" name="gameMode" id="crossword" value="crossword" />
            Crossword
        </label>
    </div>
    <br />

    <label for="questionType">Question type:</label>
    <select id="questionType" name="questionType" onchange="handleQuestionTypeChange()">
        <?php
        // Default to Animals (ID=4) if not chosen
        foreach ($questionTypes as $type) {
            $selected = ($type['questiontype_id'] == 4) ? 'selected' : '';
            echo "<option value=\"{$type['questiontype_id']}\" $selected>{$type['questiontype_string']}</option>";
        }
        ?>
    </select>

    <br /><br />
    <button type="submit">GO</button>
</form>

</body>
</html>
