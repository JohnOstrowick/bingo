CREATE DATABASE bingo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE bingo;

CREATE TABLE question_type (
    questiontype_id INT AUTO_INCREMENT PRIMARY KEY,
    questiontype_string VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE clues (
    answer_id INT AUTO_INCREMENT PRIMARY KEY,
    clue_string TEXT NOT NULL,
    answer_string VARCHAR(255) NOT NULL,
    question_type INT NOT NULL,
    FOREIGN KEY (question_type) REFERENCES question_type(questiontype_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE USER 'bingo_user'@'localhost' IDENTIFIED WITH mysql_native_password BY 'securepassword';

GRANT ALL PRIVILEGES ON bingo.* TO 'bingo_user'@'localhost';

FLUSH PRIVILEGES;

INSERT INTO question_type (questiontype_string) VALUES
('Famous People'),
('Famous Buildings / Landmarks'),
('History'),
('Animals'),
('Plants'),
('Minerals'),
('Planets / Astronomy'),
('Integers (1-99)'),
('Music and Art'),
('Geography'),
('Sports'),
('Movies & TV'),
('Science & Technology'),
('Mythology & Folklore'),
('Literature'),
('Brands & Logos'),
('Food & Drinks');

