-- makes the database, run it with: mysql -u root < schema.sql
-- careful this deletes the old tables and makes them again

CREATE DATABASE IF NOT EXISTS fullstack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fullstack;

DROP TABLE IF EXISTS couches;
DROP TABLE IF EXISTS categories;

CREATE TABLE categories (
    name      VARCHAR(50) NOT NULL PRIMARY KEY,
    width_cm  INT NOT NULL,
    depth_cm  INT NOT NULL,
    height_cm INT NOT NULL
);

CREATE TABLE couches (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    category   VARCHAR(50) NOT NULL,
    leather_kg DECIMAL(6,2) NOT NULL DEFAULT 0,
    sold       TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (category) REFERENCES categories(name)
);

INSERT INTO categories (name, width_cm, depth_cm, height_cm) VALUES
    ('A', 200, 100, 80),
    ('B', 250, 110, 85),
    ('C', 300, 120, 90);

INSERT INTO couches (name, category, leather_kg, sold) VALUES
    ('Amber Two-Seater',   'A', 5.50, 0),
    ('Chestnut Loveseat',  'A', 5.00, 0),
    ('Walnut Compact',     'A', 6.00, 1),
    ('Saddle Classic',     'A', 5.75, 0),
    ('Cognac Three-Seater','B', 7.50, 0),
    ('Tobacco Lounge',     'B', 8.00, 1),
    ('Russet Family',      'B', 7.25, 0),
    ('Umber Corner',       'C', 9.50, 0),
    ('Mahogany Grand',     'C', 10.00, 1),
    ('Sienna Sectional',   'C', 9.00, 0);
