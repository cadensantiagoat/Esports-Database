DROP DATABASE IF EXISTS esport;
CREATE DATABASE IF NOT EXISTS esport;
DROP USER IF EXISTS 'feng'@'localhost';
GRANT SELECT, INSERT, DELETE, UPDATE, EXECUTE ON hw2.* TO 'root'@'localhost';

USE esport;

CREATE TABLE Users (
    userID INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(100),
    lastname VARCHAR(150) NOT NULL,
    Email VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL,
    role ENUM('player', 'coach', 'admin'),
    UNIQUE KEY uniq_name (Name_Last, Name_First),
    INDEX idx_last (Name_Last)
);

INSERT INTO Users
VALUES (1000, 'Donald', 'Duck', 'donaldduck@gmail.com','123456','player'),
       (1001, 'Daisy',  'Duck',  'daisyduck@gmail.com', '234567''coach'),
       (1002, 'Mickey', 'Mouse', 'mickeymouse@gmail.com', '345678','admin'),
       (1003, 'Louie', 'Duck', 'louieduck@gmail.com', '012345','player')


CREATE TABLE Players (
    userID INT(10) PRIMARY KEY,
    gameTag VARCHAR(100),
    rank VARCHAR(150) NOT NULL,
    mmr INT,
    FOREIGN KEY (userID) REFERENCES Users(userID)
);

INSERT INTO Players
VALUES 
(1000, 'ShadowReaper', 'Gold', 1450),
(1003, 'NovaStrike', 'Platinum', 1820)

CREATE TABLE Coachs (
    userID INT PRIMARY KEY,
    experience_years INT,
    certification VARCHAR(150),
    FOREIGN KEY (userID) REFERENCES Users(userID)
);

INSERT INTO Coachs 
VALUES 
(1001, 10, 'Level A Coaching License');

CREATE TABLE Teams (
    userID INT,
    teamID INT,
    teamname VARCHAR(100),
    role_in_team ENUM('Captain', 'Leader', 'Support', 'Sniper', 'Member'，'Coach','Sub')
    status VARCHAR(50),
    scheduleID INT,
    PRIMARY KEY (userID, teamID),
    FOREIGN KEY (userID) REFERENCES Users(userID),
    FOREIGN KEY (teamID) REFERENCES TeamInfo(teamID)
);

INSERT INTO Teams 
VALUES
(1001, T101, 'DragonSlayers', 'Captain', 'Active', S1001),
(1003, T101, 'DragonSlayers', 'Support', 'Active', S1001),
(1002, T101, 'DragonSlayers', 'Coach', 'active', S1001),

CREATE TABLE Games (
    gameID INT PRIMARY KEY,
    gameType VARCHAR(100),
    gameName VARCHAR(150),
    gameSeason VARCHAR(50),
    gameMatch VARCHAR(100),
    gameRound VARCHAR(50),
    game_start_date DATE,
    game_end_date DATE
);

INSERT INTO Games (gameID, gameType, gameName, gameSeason, gameMatch, gameRound, game_start_date, game_end_date)
VALUES 
(G00001, 'FPS', 'Valorant Championship', '2025', 'Match 1', 'Round 1', '2025-06-01', '2025-06-01');

CREATE TABLE Schedule (
    scheduleID INT PRIMARY KEY,
    gameID INT NOT NULL,
    gameDate DATETIME NOT NULL,
    teamID INT NOT NULL,
    status ENUM('Scheduled', 'Completed', 'Cancelled', 'Postponed') NOT NULL,
    description VARCHAR(255)
);

INSERT INTO Schedule (scheduleID, gameID, gameDate, teamID, status, description)
VALUES 
(S1010, G00010, '2026-05-01 15:00:00', 10, 'Scheduled', 'Opening match'),
(s1011, G00012, '2026-05-02 18:30:00', 12, 'Scheduled', 'Evening game')

CREATE TABLE Records (
    gameID INT NOT NULL,
    playerID INT NOT NULL,
    points INT DEFAULT 0,
    PRIMARY KEY (gameID, playerID),
    CONSTRAINT fk_records_game
        FOREIGN KEY (gameID) REFERENCES Game(gameID),
    CONSTRAINT fk_records_player
        FOREIGN KEY (playerID) REFERENCES Player(playerID)
);

INSERT INTO Records (gameID, playerID, points)
VALUES
(G10001, 10001, 25),

