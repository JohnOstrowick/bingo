<?php
header("Content-Type: text/html");

// Grid configuration
define("GRID_SIZE", 20);
define("CELL_SIZE", 1);
define("MAX_WORDS", 14);
define("MIN_WORDS", 2);

// Get parameters
if (!isset($_GET['questionType']) || !is_numeric($_GET['questionType'])) {
    die("Invalid question type.");
}
$questionType = (int) $_GET['questionType'];
$numWords = isset($_GET['numWords']) && is_numeric($_GET['numWords']) ? max(MIN_WORDS, min(MAX_WORDS, (int)$_GET['numWords'])) : 10;

// Fetch words and clues from database
$mysqli = new mysqli("localhost", "bingo_user", "securepassword", "bingo");
if ($mysqli->connect_error) {
    die("Database connection failed.");
}

$stmt = $mysqli->prepare("SELECT clue_string, answer_string FROM clues WHERE question_type = ? ORDER BY RAND() LIMIT ?");
$stmt->bind_param("ii", $questionType, $numWords);
$stmt->execute();
$result = $stmt->get_result();

$clues = [];
$words = [];

while ($row = $result->fetch_assoc()) {
    $clue = $row['clue_string'];
    $answer = strtoupper(str_replace(" ", "", $row['answer_string'])); // Remove spaces

    $clues[] = ['clue' => $clue, 'answer' => $answer];
    $words[] = $answer;
}

$stmt->close();
$mysqli->close();

if (count($words) < 2) {
    die("Not enough words to generate crossword.");
}

// Shuffle words to get different layouts each reload
shuffle($clues);

// Initialize grid
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

    $hasIntersection = false;

    for ($i = 0; $i < $length; $i++) {
        $r = $across ? $row : $row + $i;
        $c = $across ? $col + $i : $col;

        // Reject if occupied by a different letter
        if ($grid[$r][$c] !== null && $grid[$r][$c]['letter'] !== $word[$i]) {
            return false;
        }

        // Ensure crossing happens (if grid is not empty)
        if ($grid[$r][$c] !== null && $grid[$r][$c]['letter'] === $word[$i]) {
            $hasIntersection = true;
        }

        // Prevent parallel adjacent words (left/right for across, top/bottom for down)
        if ($across) {
            if ($r > 0 && $grid[$r-1][$c] !== null) return false;
            if ($r < GRID_SIZE-1 && $grid[$r+1][$c] !== null) return false;
        } else {
            if ($c > 0 && $grid[$r][$c-1] !== null) return false;
            if ($c < GRID_SIZE-1 && $grid[$r][$c+1] !== null) return false;
        }
    }

    return $hasIntersection;
}

// Function to place a word on the grid
function placeWord(&$grid, $word, $row, $col, $across) {
    $length = strlen($word);
    for ($i = 0; $i < $length; $i++) {
        $r = $across ? $row : $row + $i;
        $c = $across ? $col + $i : $col;
        $grid[$r][$c] = ['letter' => $word[$i], 'visible' => false]; // Hide by default
    }
}

// Place first word in the center (random orientation)
$firstEntry = array_shift($clues);
$firstWord = $firstEntry['answer'];
$startRow = rand(5, GRID_SIZE - 10);
$startCol = rand(5, GRID_SIZE - strlen($firstWord) - 5);
$firstAcross = rand(0, 1) === 1;
placeWord($grid, $firstWord, $startRow, $startCol, $firstAcross);
$placedWords[] = [$firstEntry['clue'], $firstWord, $startRow, $startCol, $firstAcross];

// Place remaining words
foreach ($clues as $entry) {
    $word = $entry['answer'];
    $placed = false;

    foreach ($placedWords as [$existingClue, $existingWord, $erow, $ecol, $eacross]) {
        for ($i = 0; $i < strlen($word); $i++) {
            for ($j = 0; $j < strlen($existingWord); $j++) {
                if ($word[$i] === $existingWord[$j]) {
                    $newRow = $eacross ? $erow + $j - $i : $erow;
                    $newCol = $eacross ? $ecol : $ecol + $j - $i;
                    $newAcross = !$eacross;

                    if (canPlaceWord($grid, $word, $newRow, $newCol, $newAcross)) {
                        placeWord($grid, $word, $newRow, $newCol, $newAcross);
                        $placedWords[] = [$entry['clue'], $word, $newRow, $newCol, $newAcross];
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
    <title>Crossword Generator</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        .grid-container { display: grid; grid-template-columns: repeat(<?= GRID_SIZE ?>, <?= CELL_SIZE ?>cm); width: <?= GRID_SIZE ?>cm; margin: auto; }
        .cell { width: <?= CELL_SIZE ?>cm; height: <?= CELL_SIZE ?>cm; border: 1px solid black; display: flex; justify-content: center; align-items: center; font-size: 16px; font-weight: bold; background-color: white; }
        .black-cell { background-color: black; }
    </style>
</head>
<body>

<h1>Crossword Generator</h1>

<div class="grid-container">
    <?php
    for ($r = 0; $r < GRID_SIZE; $r++) {
        for ($c = 0; $c < GRID_SIZE; $c++) {
            $cell = $grid[$r][$c];

            if ($cell === null) {
                echo "<div class='cell black-cell'></div>";
            } else {
                echo "<div class='cell' data-letter='{$cell['letter']}' onclick='this.innerHTML=this.getAttribute(\"data-letter\")'>&nbsp;</div>";
            }
        }
    }
    ?>
</div>

<h2>Clues</h2>
<ul>
    <?php foreach ($placedWords as [$clue, $word]) {
        echo "<li>$clue</li>";
    } ?>
</ul>

<button onclick="location.reload()">Randomise</button>
<button onclick="window.print()">Print</button>
<button onclick="showSolution()">Show Solution</button>

<script>
    function showSolution() {
        document.querySelectorAll(".cell").forEach(cell => {
            if (cell.classList.contains("black-cell")) return;
            cell.innerHTML = cell.getAttribute("data-letter");
        });
    }
</script>

</body>
</html>