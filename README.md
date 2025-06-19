# Bingo Grid Generator with Clues

A simple PHP + JavaScript application to generate Bingo game cards and print clue sheets for various question types.

---

## 📋 Features

- 🔢 **Numeric Bingo Grid Mode** (B1–O99)
  - Click "Show Clues" to view a printable numeric clue grid (`numeric_clues.html`)
  - Columns: B(1–20), I(21–40), N(41–60), G(61–80), O(81–99)
- 🧠 **Thematic Clues Mode**
  - Select question categories (e.g. Animals, Landmarks)
  - Clues are dynamically pulled from a database and rendered under the grid
  - Option to print clues with or without answers
- 🖨️ **Print-Ready Design**
  - Clean layout optimized for printing on A4
- 📄 **Database Setup Scripts**
  - `setup.sql`: creates tables and inserts default data
  - `bingo.sql`, `clues.sql`: sample data inserts

---

## 🚫 Known Limitations

- ❌ **Crossword Mode**
  - Present in `crossword.php` but not yet functional — currently buggy and untested
- 🧩 **No User Auth**
  - This is a local-use educational/printing tool. No login system is included.

---

## 🔧 Requirements

- PHP 7.4 or later
- MySQL (or MariaDB)
- Web server (Apache or built-in PHP server)
- Browser with JavaScript enabled

---

## 🚀 Setup Instructions

1. Clone or download the project:
   ```bash
   git clone https://github.com/yourusername/bingo.git
   cd bingo

