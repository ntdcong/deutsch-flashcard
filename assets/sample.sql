-- Tạo bảng users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng notebooks
CREATE TABLE notebooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tạo bảng vocabularies
CREATE TABLE vocabularies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notebook_id INT NOT NULL,
    word VARCHAR(255) NOT NULL,
    phonetic VARCHAR(255),
    meaning TEXT,
    note TEXT,
    plural VARCHAR(255),
    genus VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notebook_id) REFERENCES notebooks(id)
);

-- Tạo bảng learning_status
CREATE TABLE learning_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vocab_id INT NOT NULL,
    status ENUM('known', 'unknown') DEFAULT 'unknown',
    last_reviewed DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (vocab_id) REFERENCES vocabularies(id)
);

   CREATE TABLE IF NOT EXISTS notebook_groups (
       id INT AUTO_INCREMENT PRIMARY KEY,
       user_id INT NOT NULL,
       name VARCHAR(255) NOT NULL,
       created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (user_id) REFERENCES users(id)
   );

   ALTER TABLE notebooks ADD COLUMN group_id INT NULL, ADD FOREIGN KEY (group_id) REFERENCES notebook_groups(id);

-- Dữ liệu mẫu
INSERT INTO users (email, password) VALUES ('demo@email.com', '$2y$10$QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiop');
INSERT INTO notebooks (user_id, title, description) VALUES (1, 'Sổ tay mẫu', 'Từ vựng cơ bản');
INSERT INTO vocabularies (notebook_id, word, phonetic, meaning, note, plural, genus) VALUES
  (1, 'Haus', 'haʊs', 'nhà', '', 'Häuser', 'das'),
  (1, 'Baum', 'baʊm', 'cây', '', 'Bäume', 'der');
INSERT INTO learning_status (user_id, vocab_id, status, last_reviewed) VALUES
  (1, 1, 'known', NOW()),
  (1, 2, 'unknown', NOW()); 