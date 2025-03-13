<?php
/************************************************
 * crossword.php
 * Merged code that:
 * 1) Shows a form with wordCount (2..14).
 * 2) Defaults to 10 if not provided.
 * 3) Fetches up to 24 clues from DB for questionType.
 * 4) Slices them to wordCount.
 * 5) Uses backtracking logic to place words with mandatory intersections, etc.
 ************************************************/

// Constants
const GRID_SIZE = 20;
const MIN_WORDS = 2;
const MAX_WORDS = 14;
const DEFAULT_WORDS = 10;

// Simple data structure for placed words
class PlacedWord {
    public $word;
    public $row;
    public $col;
    public $isAcross;

    public function __construct($word, $row, $col, $isAcross) {
        $this->word     = $word;
        $this->row      = $row;
        $this->col      = $col;
        $this->isAcross = $isAcross;
    }
}

/*******************************
 * Display a form at the top
 *******************************/
function displayWordCountForm($qType, $selectedCount) {
    echo '<form method="GET" style="margin-bottom:20px;">';
    echo '<input type="hidden" name="questionType" value="' . htmlspecialchars($qType) . '">';
    echo 'Number of words: ';
    echo '<select name="wordCount">';
    for ($i = MIN_WORDS; $i <= MAX_WORDS; $i++) {
        $sel = ($i == $selectedCount) ? 'selected' : '';
        echo "<option value=\"$i\" $sel>$i</option>";
    }
    echo '</select> ';
    echo '<button type="submit">Set</button>';
    echo '</form>';
}

/*******************************
 * Grid initialization
 *******************************/
function initGrid($size = GRID_SIZE) {
    return array_fill(0, $size, array_fill(0, $size, null));
}

/*******************************
 * Checking + placing words
 *******************************/

// Checks if a word can be placed with mandatory intersection and no flush lines
function canPlaceWord(&$grid, $placedWords, $word, $row, $col, $across) {
    $length = strlen($word);

    // Bounds check
    if ($across && ($col + $length > GRID_SIZE))  return false;
    if (!$across && ($row + $length > GRID_SIZE)) return false;

    $hasIntersection = false;

    for ($i = 0; $i < $length; $i++) {
        $r = $across ? $row : $row + $i;
        $c = $across ? $col + $i : $col;

        $cellVal = $grid[$r][$c];

        // If cell is occupied by different letter, can't place
        if ($cellVal !== null && $cellVal !== $word[$i]) {
            return false;
        }
        // Check adjacency for no flush lines
        if ($across) {
            // above
            if ($r > 0 && $grid[$r - 1][$c] !== null && $grid[$r - 1][$c] !== $word[$i]) {
                return false;
            }
            // below
            if ($r < GRID_SIZE - 1 && $grid[$r + 1][$c] !== null && $grid[$r + 1][$c] !== $word[$i]) {
                return false;
            }
        } else {
            // left
            if ($c > 0 && $grid[$r][$c - 1] !== null && $grid[$r][$c - 1] !== $word[$i]) {
                return false;
            }
            // right
            if ($c < GRID_SIZE - 1 && $grid[$r][$c + 1] !== null && $grid[$r][$c + 1] !== $word[$i]) {
                return false;
            }
        }
        // Intersection check
        if ($cellVal === $word[$i]) {
            $hasIntersection = true;
        }
    }

    // If there are already placed words, require at least one intersection
    if (!empty($placedWords) && !$hasIntersection) {
        return false;
    }

    return true;
}

function placeWord(&$grid, $word, $row, $col, $across) {
    $length = strlen($word);
    for ($i = 0; $i < $length; $i++) {
        $r = $across ? $row : $row + $i;
        $c = $across ? $col + $i : $col;
        $grid[$r][$c] = $word[$i];
    }
}

function removeWord(&$grid, $word, $row, $col, $across) {
    $length = strlen($word);
    for ($i = 0; $i < $length; $i++) {
        $r = $across ? $row : $row + $i;
        $c = $across ? $col + $i : $col;
        $grid[$r][$c] = null;
    }
}

/*******************************
 * Backtracking logic
 *******************************/
function backtrack(&$grid, $words, $index, &$placed) {
    if ($index >= count($words)) {
        return true;
    }
    $word = $words[$index];
    // Try across or down
    foreach ([true, false] as $across) {
        for ($row = 0; $row < GRID_SIZE; $row++) {
            for ($col = 0; $col < GRID_SIZE; $col++) {
                if (canPlaceWord($grid, $placed, $word, $row, $col, $across)) {
                    placeWord($grid, $word, $row, $col, $across);
                    $placed[] = new PlacedWord($word, $row, $col, $across);

                    if (backtrack($grid, $words, $index + 1, $placed)) {
                        return true;
                    }
                    // revert
                    array_pop($placed);
                    removeWord($grid, $word, $row, $col, $across);
                }
            }
        }
    }
    return false;
}

/*******************************
 * Main
 *******************************/

if (!isset($_GET['questionType']) || !is_numeric($_GET['questionType'])) {
    die("Invalid question type.");
}
$questionType = (int) $_GET['questionType'];

// Decide how many words
$wordCount = DEFAULT_WORDS; // default 10
if (isset($_GET['wordCount']) && ctype_digit($_GET['wordCount'])) {
    $wc = (int)$_GET['wordCount'];
    if ($wc >= MIN_WORDS && $wc <= MAX_WORDS) {
        $wordCount = $wc;
    }
}

// Display top half of HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Crossword Puzzle</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      text-align: center;
      margin: 20px;
    }
    .grid-container {
      margin: 0 auto;
      width: 20cm;
    }
    table.crossword {
      border-collapse: collapse;
      margin: 0 auto;
    }
    table.crossword td {
      width: 1cm; height: 1cm;
      text-align: center; vertical-align: middle;
      border: 1px solid #000;
      font-weight: bold;
    }
    .black-cell {
      background-color: black;
    }
    .white-cell {
      background-color: white;
    }
    .clues-container {
      width: 70%;
      margin: 20px auto;
      text-align: left;
      columns: 2;
      -webkit-columns: 2;
      -moz-columns: 2;
    }
    @page {
      size: A4 portrait;
      margin: 1cm;
    }
    @media print {
      .no-print {
        display: none;
      }
    }
  </style>
  <script>
    function randomisePage() { location.reload(); }
    function printPuzzle() { window.print(); }
    function chooseDifferentSet() { window.location.href = 'index.php'; }
    function showSolution() {
      let cells = document.querySelectorAll('td.white-cell');
      cells.forEach(cell => {
        let letter = cell.getAttribute('data-letter');
        if (letter) cell.textContent = letter;
      });
    }
  </script>
</head>
<body>

<h1>Crossword Puzzle</h1>

<?php
// Display the form to choose wordCount
displayWordCountForm($questionType, $wordCount);

// Now connect DB and fetch clues
$mysqli = new mysqli("localhost", "bingo_user", "securepassword", "bingo");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$stmt = $mysqli->prepare("SELECT clue_string, answer_string FROM clues WHERE question_type = ? ORDER BY RAND() LIMIT 24");
$stmt->bind_param("i", $questionType);
$stmt->execute();
$res = $stmt->get_result();

$allClues = [];
while ($row = $res->fetch_assoc()) {
    $ans = strtoupper(preg_replace('/\s+/', '', $row['answer_string']));
    // skip 1-letter or empty
    if (strlen($ans) >= 2) {
        $allClues[] = [
            'clue'   => $row['clue_string'],
            'answer' => $ans
        ];
    }
}
$stmt->close();
$mysqli->close();

// Check we have enough
if (count($allClues) < $wordCount) {
    die("Not enough valid clues. Found " . count($allClues) . " but need $wordCount.");
}

// Shuffle and slice
shuffle($allClues);
$selectedClues = array_slice($allClues, 0, $wordCount);

// Build puzzle with backtracking
$grid = initGrid();
$placed = [];
$justWords = array_map(fn($c) => $c['answer'], $selectedClues);

if (!backtrack($grid, $justWords, 0, $placed)) {
    die("Could not create a valid crossword with mandatory crossings. Try fewer words or reload.");
}

// Group them into across vs down for clue listing
$acrossData = [];
$downData = [];
foreach ($placed as $pw) {
    // find matching clue
    foreach ($selectedClues as $sc) {
        if ($sc['answer'] === $pw->word) {
            if ($pw->isAcross) {
                $acrossData[] = $sc;
            } else {
                $downData[] = $sc;
            }
            break;
        }
    }
}
?>

<div class="no-print">
  <button onclick="randomisePage()">Randomise</button>
  <button onclick="printPuzzle()">Print</button>
  <button onclick="chooseDifferentSet()">Choose Different Set</button>
  <button onclick="showSolution()">Show Solution</button>
</div>

<div class="grid-container">
  <table class="crossword">
    <?php for ($r = 0; $r < GRID_SIZE; $r++): ?>
      <tr>
      <?php for ($c = 0; $c < GRID_SIZE; $c++):
        $val = $grid[$r][$c];
        if ($val === null): ?>
          <td class="black-cell"></td>
        <?php else: ?>
          <td class="white-cell" data-letter="<?= htmlspecialchars($val) ?>">&nbsp;</td>
        <?php endif; ?>
      <?php endfor; ?>
      </tr>
    <?php endfor; ?>
  </table>
</div>

<div class="clues-container">
  <h2>Across Words</h2>
  <ol>
  <?php foreach ($acrossData as $cl): ?>
    <li><?= htmlspecialchars($cl['clue']) ?></li>
  <?php endforeach; ?>
  </ol>

  <h2>Down Words</h2>
  <ol>
  <?php foreach ($downData as $cl): ?>
    <li><?= htmlspecialchars($cl['clue']) ?></li>
  <?php endforeach; ?>
  </ol>
</div>

</body>
</html>
