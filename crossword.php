<?php
header("Content-Type: text/html");

// Grid configuration
define("GRID_SIZE", 20); // Maximum crossword size
define("CELL_SIZE", 1); // cm size per cell
define("MAX_WORDS", 14);
define("MIN_WORDS", 2);

// Get parameters
if (!isset($_GET['questionType']) || !is_numeric($_GET['questionType'])) {
    die("Invalid question type.");
}
$questionType = (int) $_GET['questionType'];
$numWords = isset($_GET['numWords']) && is_numeric($_GET['numWords']) ? max(MIN_WORDS, min(MAX_WORDS, (int)$_GET['numWords'])) : 10;

// Fetch words from database
$mysqli = new mysqli("localhost", "bingo_user", "securepassword", "bingo");
if ($mysqli->connect_error) {
    die("Database connection failed.");
}

$stmt = $mysqli->prepare("SELECT answer_string FROM clues WHERE question_type = ? ORDER BY CHAR_LENGTH(answer_string) DESC LIMIT ?");
$stmt->bind_param("ii", $questionType, $numWords);
$stmt->execute();
$result = $stmt->get_result();

$words = [];
while ($row = $result->fetch_assoc()) {
    $words[] = strtoupper(str_replace(" ", "", $row['answer_string'])); // Remove spaces for crossword
}

$stmt->close();
$mysqli->close();

if (count($words) < 2) {
    die("Not enough words to generate crossword.");
}

// Initialize empty grid
$grid = array_fill(0, GRID_SIZE, array_fill(0, GRID_SIZE, null));
$placedWords = [];

// Function to check word placement validity
function canPlaceWord($grid, $word, $row, $col, $across) {
    $length = strlen($word);

    if ($across) {
        if ($col + $length > GRID_SIZE) return false;
    } else {
        if ($row + $length > GRID_SIZE) return false;
    }

    for ($i = 0; $i < $length; $i++) {
        $r = $across ? $row : $row + $i;
        $c = $across ? $col + $i : $col;

        if ($grid[$r][$c] !== null && $grid[$r][$c] !== $word[$i]) {
            return false;
        }
    }

    return true;
}

// Function to place a word on the grid
function placeWord(&$grid, $word, $row, $col, $across) {
    $length = strlen($word);
    for ($i = 0; $i < $length; $i++) {
        $r = $across ? $row : $row + $i;
        $c = $across ? $col + $i : $col;
        $grid[$r][$c] = $word[$i];
    }
}

// Place first word in the center
$firstWord = array_shift($words);
$startRow = GRID_SIZE / 2;
$startCol = (GRID_SIZE - strlen($firstWord)) / 2;
placeWord($grid, $firstWord, $startRow, $startCol, true);
$placedWords[] = [$firstWord, $startRow, $startCol, true];

// Place remaining words with intersections
foreach ($words as $word) {
    $placed = false;

    foreach ($placedWords as [$existingWord, $erow, $ecol, $eacross]) {
        for ($i = 0; $i < strlen($word); $i++) {
            for ($j = 0; $j < strlen($existingWord); $j++) {
                if ($word[$i] === $existingWord[$j]) {
                    $newRow = $eacross ? $erow + $j - $i : $erow;
                    $newCol = $eacross ? $ecol : $ecol + $j - $i;
                    $newAcross = !$eacross;

                    if (canPlaceWord($grid, $word, $newRow, $newCol, $newAcross)) {
                        placeWord($grid, $word, $newRow, $newCol, $newAcross);
                        $placedWords[] = [$word, $newRow, $newCol, $newAcross];
                        $placed = true;
                        break 3;
                    }
                }
            }
        }
    }
}

// HTML Output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crossword Generator</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        .grid-container { display: grid; grid-template-columns: repeat(<?= GRID_SIZE ?>, <?= CELL_SIZE ?>cm); width: <?= GRID_SIZE ?>cm; margin: auto; }
        .cell { width: <?= CELL_SIZE ?>cm; height: <?= CELL_SIZE ?>cm; border: 1px solid black; display: flex; justify-content: center; align-items: center; font-size: 16px; font-weight: bold; }
        .black-cell { background-color: black; }
    </style>
</head>
<body>

<h1>Crossword Generator</h1>

<form method="GET" action="crossword.php">
    <input type="hidden" name="questionType" value="<?= $questionType ?>">
    <label for="numWords">Number of Words:</label>
    <select name="numWords" id="numWords" onchange="this.form.submit()">
        <?php for ($i = MIN_WORDS; $i <= MAX_WORDS; $i++): ?>
            <option value="<?= $i ?>" <?= $i == $numWords ? 'selected' : '' ?>><?= $i ?></option>
        <?php endfor; ?>
    </select>
</form>

<div class="grid-container">
    <?php
    for ($r = 0; $r < GRID_SIZE; $r++) {
        for ($c = 0; $c < GRID_SIZE; $c++) {
            $char = $grid[$r][$c];
            if ($char !== null) {
                echo "<div class='cell'>$char</div>";
            } else {
                echo "<div class='cell black-cell'></div>";
            }
        }
    }
    ?>
</div>

<h2>Clues</h2>
<table>
    <tr>
        <th>Across</th>
        <th>Down</th>
    </tr>
    <tr>
        <td>
            <ul>
                <?php foreach ($placedWords as [$word, $row, $col, $across]) {
                    if ($across) echo "<li>$word</li>";
                } ?>
            </ul>
        </td>
        <td>
            <ul>
                <?php foreach ($placedWords as [$word, $row, $col, $across]) {
                    if (!$across) echo "<li>$word</li>";
                } ?>
            </ul>
        </td>
    </tr>
</table>

<button onclick="location.reload()">Randomise</button>
<button onclick="window.print()">Print</button>
<button onclick="window.location.href='index.php'">Choose Different Set</button>
<button onclick="showSolution()">Show Solution</button>

<script>
    function showSolution() {
        const cells = document.querySelectorAll(".cell");
        cells.forEach(cell => {
            if (cell.classList.contains("black-cell")) return;
            cell.style.color = "black";
        });
    }
</script>

</body>
</html>
