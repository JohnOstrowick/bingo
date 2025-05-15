<?php
if (!isset($_GET['questionType']) || !is_numeric($_GET['questionType'])) {
    die("Invalid question type.");
}

$questionType = (int) $_GET['questionType'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bingo Card Generator</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin: 20px; }
        .bingo-container { width: 19cm; margin: 20px auto; page-break-after: always; }
        .bingo-title { font-size: 24px; font-weight: bold; margin-bottom: 10px; background-color: lightgrey; padding: 10px; }
        .bingo-table { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .bingo-table td { width: 3.8cm; height: 3.8cm; border: 2px solid black; text-align: center; font-size: 17pt; vertical-align: middle; font-weight: bold; }
        .free-space { background-color: lightgray; }
    </style>
</head>
<body>

    <h1>Bingo Card Generator</h1>
    
    <button onclick="printBingoCard()">Print Bingo Card</button>
	<button onclick="printClues(false)">Print Clues</button>
	<button onclick="printClues(true)">Print Clues with Answers</button>
    <button onclick="location.reload()">Randomise</button>
    <a href="index.php" style="text-decoration:none"><button>Choose a different set</button></a>

    <div class="bingo-container">
        <div class="bingo-title">Bingo Card</div>
        <table class="bingo-table" id="bingo-table">
            <!-- The Bingo grid will be inserted here by JavaScript -->
        </table>
    </div>

    <script>
        let questionType = <?= $questionType ?>;

        function shuffleArray(array) {
            let shuffled = array.slice();
            for (let i = shuffled.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
            }
            return shuffled;
        }

        function generateNumberBingoCard() {
            let numbers = new Set();
            while (numbers.size < 24) {
                numbers.add(Math.floor(Math.random() * 100)); // Random numbers [0,99]
            }
            let numberArray = [...numbers];
            numberArray.splice(12, 0, "FREE SPACE"); // Insert free space

            let bingoHTML = `<tr bgcolor="lightgrey">
                <td>B</td><td>I</td><td>N</td><td>G</td><td>O</td>
            </tr>`;

            for (let i = 0; i < 5; i++) {
                bingoHTML += "<tr>";
                for (let j = 0; j < 5; j++) {
                    let num = numberArray[i * 5 + j];
                    let extraClass = num === "FREE SPACE" ? "free-space" : "";
                    bingoHTML += `<td class="${extraClass}">${num}</td>`;
                }
                bingoHTML += "</tr>";
            }

            document.getElementById("bingo-table").innerHTML = bingoHTML;
        }

        function generateBingoCard(answers) {
            // Dynamically adjust font size if length exceeds 10 characters
            function getDynamicFontSize(str) {
                const baseSize = 17;       // Starting font size
                const maxShrink = 10;      // Minimum font size
                const excess = str.length - 10;
                if (excess <= 0) return baseSize + 'pt';

                // For each character above 10, reduce font size by 0.5pt
                let newSize = baseSize - (0.5 * excess);
                if (newSize < maxShrink) newSize = maxShrink;  // Prevent going too small

                return newSize + 'pt';
            }

            // Shuffle and select 24 unique answers
            let shuffled = shuffleArray(answers);
            let selected = new Set();
            let index = 0;
            while (selected.size < 24) {
                selected.add(shuffled[index]);
                index++;
            }

            // Insert FREE SPACE in the middle
            let arr = [...selected];
            arr.splice(12, 0, "FREE SPACE");

            // Build table HTML
            let bingoHTML = `
                <tr bgcolor="lightgrey">
                    <td>B</td><td>I</td><td>N</td><td>G</td><td>O</td>
                </tr>
            `;
            for (let i = 0; i < 5; i++) {
                bingoHTML += "<tr>";
                for (let j = 0; j < 5; j++) {
                    let val = arr[i * 5 + j];
                    let cellStyle = "";
                    if (val !== "FREE SPACE") {
                        // Dynamically calculate font size based on length
                        let fontSize = getDynamicFontSize(val);
                        cellStyle = `style="font-size:${fontSize};"`;
                    }
                    let extraClass = (val === "FREE SPACE") ? "free-space" : "";
                    bingoHTML += `<td class="${extraClass}" ${cellStyle}>${val}</td>`;
                }
                bingoHTML += "</tr>";
            }

            // Inject into the DOM
            document.getElementById("bingo-table").innerHTML = bingoHTML;
        }

		function printBingoCard() {
            let printContent = document.querySelector('.bingo-container').innerHTML;
            let printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print Bingo Card</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            text-align: center;
                            margin: 20px;
                        }
                        .bingo-container {
                            width: 19cm;
                            margin: 20px auto;
                        }
                        .bingo-table {
                            width: 100%;
                            table-layout: fixed;
                            border-collapse: collapse;
                        }
                        .bingo-table td {
                            width: 3.8cm;
                            height: 3.8cm;
                            border: 2px solid black;
                            text-align: center;
                            font-size: 17pt;
                            vertical-align: middle;
                            font-weight: bold;
                        }
                        .free-space {
                            background-color: lightgray;
                        }
                        @page {
                            size: A4 portrait;
                            margin: 1cm;
                        }
                        @media print {
                            .bingo-container {
                                width: 19cm;
                                margin: 0 auto;
                            }
                            .bingo-table td {
                                width: 3.8cm;
                                height: 3.8cm;
                            }
                        }
                    </style>
                </head>
                <body>${printContent}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }

        function printClues(withAnswers) {
            fetch(`get_clues.php?questionType=${questionType}`)
                .then(response => response.json())
                .then(data => {
                    if (!Array.isArray(data)) {
                        alert("Invalid data format.");
                        return;
                    }
                    let content = `<h1>Bingo Clue Sheet</h1><ol>`;
                    data.forEach(item => {
                        if (withAnswers) {
                            content += `<li>${item.clue_string} <b>(${item.answer_string})</b></li>`;
                        } else {
                            content += `<li>${item.clue_string}</li>`;
                        }
                    });
                    content += `</ol>`;

                    let printWindow = window.open('', '_blank');
                    printWindow.document.write(`
                        <html>
                        <head>
                            <title>Print Clues</title>
                            <style>
                                body {
                                    font-family: Arial, sans-serif;
                                    text-align: left;
                                    margin: 20px;
                                }
                                h1 {
                                    text-align: center;
                                }
                                ol {
                                    font-size: 16px;
                                    line-height:2;
                                }
                                @page {
                                    size: A4 portrait;
                                    margin: 1cm;
                                }
                                @media print {
                                    body {
                                        margin: 1cm;
                                    }
                                }
                            </style>
                        </head>
                        <body>${content}</body>
                        </html>
                    `);
                    printWindow.document.close();
                    printWindow.focus();
                    printWindow.print();
                    printWindow.close();
                })
                .catch(() => alert("Error fetching clues for printing."));
        }

document.addEventListener("DOMContentLoaded", function() {
    if (questionType === 8) {
        generateNumberBingoCard();
    } else {
        fetch(`get_clues.php?questionType=${questionType}`)
            .then(response => response.json())
            .then(data => {
                if (!Array.isArray(data)) return alert("Invalid data format.");
                let answers = data.map(item => item.answer_string?.trim() || "Unknown");
                if (answers.length < 24) {
                    alert("Not enough answers to generate a full bingo card.");
                    return;
                }
                generateBingoCard(answers);
            })
            .catch(() => alert("Error fetching answers for the Bingo card."));
    }
});

</script>

</body>
</html>