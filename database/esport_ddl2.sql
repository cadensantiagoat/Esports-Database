-- Creating the database if it doesn't exist and selecting it
CREATE DATABASE IF NOT EXISTS EsportLeagueDB;
USE EsportLeagueDB;

-- Drop tables in reverse order of dependencies to avoid foreign key constraint errors
DROP TABLE IF EXISTS Stats;
DROP TABLE IF EXISTS Matches;
DROP TABLE IF EXISTS Players;
DROP TABLE IF EXISTS Coaches;
DROP TABLE IF EXISTS Teams;
DROP TABLE IF EXISTS Users;

-- USERS TABLE (Supertype for all accounts)
CREATE TABLE Users (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Email VARCHAR(100) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL, -- Sized for PHP's password_hash()
    Role ENUM('Observer', 'Player', 'Coach', 'League Owner', 'DB Admin') DEFAULT 'Observer'
);

-- TEAMS TABLE
CREATE TABLE Teams (
    TeamID INT AUTO_INCREMENT PRIMARY KEY,
    TeamName VARCHAR(100) NOT NULL UNIQUE
);

-- PLAYERS TABLE (Subtype of Users)
CREATE TABLE Players (
    userID INT PRIMARY KEY,
    TeamID INT,
    GameTag VARCHAR(50) NOT NULL,
    Rank VARCHAR(50),
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE,
    FOREIGN KEY (TeamID) REFERENCES Teams(TeamID) ON DELETE SET NULL
);

-- COACHES TABLE (Subtype of Users)
CREATE TABLE Coaches (
    userID INT PRIMARY KEY,
    TeamID INT,
    ExperienceYears INT DEFAULT 0,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE,
    FOREIGN KEY (TeamID) REFERENCES Teams(TeamID) ON DELETE SET NULL
);

-- MATCHES TABLE
CREATE TABLE Matches (
    MatchID INT AUTO_INCREMENT PRIMARY KEY,
    Team1_ID INT NOT NULL,
    Team2_ID INT NOT NULL,
    MatchDate DATETIME NOT NULL,
    FOREIGN KEY (Team1_ID) REFERENCES Teams(TeamID) ON DELETE CASCADE,
    FOREIGN KEY (Team2_ID) REFERENCES Teams(TeamID) ON DELETE CASCADE
);

-- STATS TABLE
CREATE TABLE Stats (
    StatID INT AUTO_INCREMENT PRIMARY KEY,
    MatchID INT NOT NULL,
    PlayerID INT NOT NULL,
    Kills INT DEFAULT 0,
    Deaths INT DEFAULT 0,
    Assists INT DEFAULT 0,
    GoldEarned INT DEFAULT 0,
    FOREIGN KEY (MatchID) REFERENCES Matches(MatchID) ON DELETE CASCADE,
    FOREIGN KEY (PlayerID) REFERENCES Players(userID) ON DELETE CASCADE
);

-- Dummy account for testing Forgot Password
INSERT INTO Users (userID, Username, Email, PasswordHash, Role) 
VALUES (01, 'CadenTest', 'cadenb.santiago@gmail.com', 'placeholder_hash', 'Player');