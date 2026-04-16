DROP DATABASE IF EXISTS hw2;
CREATE DATABASE IF NOT EXISTS hw2;
DROP USER IF EXISTS 'feng'@'localhost';
GRANT SELECT, INSERT, DELETE, UPDATE, EXECUTE ON hw2.* TO 'root'@'localhost';

USE hw2;

CREATE TABLE TeamRoster (
    ID INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Name_First VARCHAR(100),
    Name_Last VARCHAR(150) NOT NULL,
    Street VARCHAR(250),
    City VARCHAR(100),
    State VARCHAR(100),
    Country VARCHAR(100),
    ZipCode CHAR(10),
    CHECK (ZipCode REGEXP '^(?!0{5})(?!9{5})\\d{5}(-(?!0{4})(?!9{4})\\d{4})?$'),
    UNIQUE KEY uniq_name (Name_Last, Name_First),
    INDEX idx_last (Name_Last)
);

INSERT INTO TeamRoster
VALUES (100, 'Donald', 'Duck', '1313 S. Harbor Blvd.', 'Anaheim', 'CA', 'USA', '92808-3232'),
       (101, 'Daisy',  'Duck', '1180 Seven Seas Dr.', 'Lake Buena Vista', 'FL', 'USA', '32830'),
       (107, 'Mickey', 'Mouse', '1313 S. Harbor Blvd.', 'Anaheim', 'CA', 'USA', '92808-3232'),
       (111, 'Pluto',  'Dog', '1313 S. Harbor Blvd.', 'Anaheim', 'CA', 'USA', '92808-3232'),
       (118, 'Scrooge', 'McDuck', '1180 Seven Seas Dr.', 'Lake Buena Vista', 'FL', 'USA', '32830'),
       (119, 'Huebert(Huey)', 'Duck', '1110 Seven Seas Dr.', 'Lake Buena Vista', 'FL', 'USA', '32830'),
       (123, 'Deuteronomy(Dewey)', 'Duck', '1110 Seven Seas Dr.', 'Lake Buena Vista', 'FL', 'USA', '32830'),
       (128, 'Louie', 'Duck', '1110 Seven Seas Dr.', 'Lake Buena Vista', 'FL', 'USA', '32830'),
       (129, 'Phooey', 'Duck', '101 Maihama Urayasu', 'Chiba Prefecture', 'Disney Tokyo', 'Japan', NULL),
       (131, 'Della', 'Duck', '77700 Boulevard du Parc', 'Coupvary', 'Disney Paris', 'France', NULL);


CREATE TABLE Statistics (
    ID INT(10) AUTO_INCREMENT PRIMARY KEY,
    Player INT(10) UNSIGNED NOT NULL,
    PlayerTimeMin TINYINT(2) UNSIGNED DEFAULT 0 CHECK (PlayerTimeMin BETWEEN 0 AND 40),
    PlayerTimeSec TINYINT(2) UNSIGNED DEFAULT 0 CHECK (PlayerTImeSec BETWEEN 0 AND 59),
    Points TINYINT(3) UNSIGNED DEFAULT 0,
    Assists TINYINT(3) UNSIGNED DEFAULT 0,
    Rebounds TINYINT(3) UNSIGNED DEFAULT 0,
    FOREIGN KEY (Player) REFERENCES TeamRoster(ID) ON DELETE CASCADE
);

INSERT INTO Statistics
VALUES (17, 100, 35, 12, 47, 11, 21),
       (18, 107, 13, 22, 13, 1, 3),
       (19, 111, 10, 0, 18, 2, 4),
       (20, 128, 2, 45, 9, 1, 2),
       (21, 107, 15, 39, 26, 3, 7),
       (22, 100, 29, 47, 27, 9, 8);    
