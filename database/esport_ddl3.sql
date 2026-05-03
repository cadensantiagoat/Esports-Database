-- Notes:
-- runs with @ localhost since im using a VM
--
-- Important: might have to change it to @'%' instead if on docker container
-- 
-- Tried to match this up to ERD as much as possible
-- Matches = match event, MatchTeams = Teams involved in it.
--
-- ERD might need to reflect new foreign Key in Matches for 'teamWon'

DROP DATABASE IF EXISTS EsportLeagueDB;
CREATE DATABASE EsportLeagueDB;
USE EsportLeagueDB;


DROP USER IF EXISTS 'visitor_role'@'localhost';
DROP USER IF EXISTS 'player_role'@'localhost';
DROP USER IF EXISTS 'coach_role'@'localhost';
DROP USER IF EXISTS 'referee_role'@'localhost';

CREATE USER 'visitor_role'@'localhost' IDENTIFIED BY '!visitor' PASSWORD EXPIRE NEVER;
CREATE USER 'player_role'@'localhost' IDENTIFIED BY '!player' PASSWORD EXPIRE NEVER;
CREATE USER 'coach_role'@'localhost' IDENTIFIED BY '!coach' PASSWORD EXPIRE NEVER;
CREATE USER 'referee_role'@'localhost' IDENTIFIED BY '!referee' PASSWORD EXPIRE NEVER;


CREATE TABLE Roles (
    roleID INT AUTO_INCREMENT PRIMARY KEY,
    roleName VARCHAR(30) NOT NULL UNIQUE,
    dbAccountName VARCHAR(30) NOT NULL UNIQUE
);



CREATE TABLE Users (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    firstName VARCHAR(50) NOT NULL,
    lastName VARCHAR(50) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    passwordHash VARCHAR(255) NOT NULL,
    roleID INT NOT NULL,

    FOREIGN KEY (roleID) REFERENCES Roles(roleID)
);



CREATE TABLE Players (
    userID INT PRIMARY KEY,
    gameTag VARCHAR(50) NOT NULL,
    playerRank VARCHAR(50),
    lp INT DEFAULT 0,

    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
);



CREATE TABLE Coaches (
    userID INT PRIMARY KEY,
    experienceYears INT DEFAULT 0,
    certification VARCHAR(100),

    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
);


CREATE TABLE Teams (
    teamID INT AUTO_INCREMENT PRIMARY KEY,
    teamName VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('Active', 'Inactive') DEFAULT 'Active'
);



CREATE TABLE TeamMembers (
    teamMemberID INT AUTO_INCREMENT PRIMARY KEY,
    teamID INT NOT NULL,
    userID INT NOT NULL,
    roleInTeam ENUM('Top', 'Jgl', 'Mid', 'Bot', 'Sup', 'Coach', 'Sub') NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',

    FOREIGN KEY (teamID) REFERENCES Teams(teamID) ON DELETE CASCADE,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE,

    UNIQUE KEY uq_team_user (teamID, userID)
);

CREATE TABLE Matches (
    matchID INT AUTO_INCREMENT PRIMARY KEY,
    matchDate DATETIME NOT NULL,
    teamWon INT NULL,
    FOREIGN KEY (teamWon) REFERENCES Teams(teamID) -- ensures winning team exists
);

CREATE TABLE MatchTeam (
    matchTeamID INT AUTO_INCREMENT PRIMARY KEY,
    matchID INT NOT NULL,
    teamID INT NOT NULL,
    matchDate DATETIME NULL,
    sidePlayed ENUM('Blue', 'Red') NULL,
    outcome ENUM('Win', 'Loss', 'Draw', 'Pending') DEFAULT 'Pending',

    FOREIGN KEY (matchID) REFERENCES Matches(matchID) ON DELETE CASCADE,
    FOREIGN KEY (teamID) REFERENCES Teams(teamID) ON DELETE CASCADE
);




-- this table needs playerStats and teamStats according to ERD
CREATE TABLE Rounds (
    roundID INT AUTO_INCREMENT PRIMARY KEY,
    matchID INT NOT NULL,
    roundNumber INT NOT NULL,
    duration TIME NULL,
    outcome ENUM('Team1 Win', 'Team2 Win', 'Draw', 'Pending') DEFAULT 'Pending',

    FOREIGN KEY (matchID) REFERENCES Matches(matchID) ON DELETE CASCADE,

    UNIQUE KEY uq_match_round (matchID, roundNumber),
    CHECK (roundNumber > 0)
);



CREATE TABLE PlayerStats (
    playerStatsID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    roundID INT NOT NULL,
    teamID INT NOT NULL,
    championPlayed VARCHAR(50),
    kills INT DEFAULT 0,
    deaths INT DEFAULT 0,
    assists INT DEFAULT 0,
    creepScore INT DEFAULT 0,
    goldEarned INT DEFAULT 0,

    FOREIGN KEY (userID) REFERENCES Players(userID) ON DELETE CASCADE,
    FOREIGN KEY (roundID) REFERENCES Rounds(roundID) ON DELETE CASCADE,
    FOREIGN KEY (teamID) REFERENCES Teams(teamID) ON DELETE CASCADE,

    UNIQUE KEY uq_player_round (userID, roundID),

    CHECK (creepScore >= 0),
    CHECK (goldEarned >= 0)
);


CREATE TABLE TeamStats (
    teamStatsID INT AUTO_INCREMENT PRIMARY KEY,
    teamID INT NOT NULL,
    roundID INT NOT NULL,
    totalKills INT DEFAULT 0,
    totalGold INT DEFAULT 0,
    roundOutcome ENUM('Win', 'Loss', 'Draw', 'Pending') DEFAULT 'Pending',
    totalPoints INT DEFAULT 0,

    FOREIGN KEY (teamID) REFERENCES Teams(teamID) ON DELETE CASCADE,
    FOREIGN KEY (roundID) REFERENCES Rounds(roundID) ON DELETE CASCADE,

    UNIQUE KEY uq_team_round (teamID, roundID),

    CHECK (totalGold >= 0),
    CHECK (totalPoints >= 0)
);


INSERT INTO Roles (roleID, roleName, dbAccountName) VALUES
(1, 'Visitor', 'visitor_role'),
(2, 'Player', 'player_role'),
(3, 'Coach', 'coach_role'),
(4, 'Referee', 'referee_role');


-- Password for all users: password123
SET @password123_hash = '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO';


INSERT INTO Users
(firstName, lastName, username, email, passwordHash, roleID)
VALUES
('Sang-hyeok', 'Lee', 'faker_player', 'faker@t1.gg', @password123_hash, 2),
('Ji-hoon', 'Jeong', 'chovy_player', 'chovy@geng.gg', @password123_hash, 2),
('Tuffy', 'Titan', 'tuffy_player', 'top@fullerton.edu', @password123_hash, 2),
('Jeong-gyun', 'Kim', 'kkoma_coach', 'kkoma@t1.gg', @password123_hash, 3),
('Main', 'Referee', 'main_referee', 'referee@lck.gg', @password123_hash, 4);


INSERT INTO Teams (teamName, status) VALUES
('T1', 'Active'),
('Gen.G', 'Active'),
('CSUF Titans', 'Active');

-- Resolve IDs.
SET @faker = (SELECT userID FROM Users WHERE username = 'faker_player');
SET @chovy = (SELECT userID FROM Users WHERE username = 'chovy_player');
SET @tuffy = (SELECT userID FROM Users WHERE username = 'tuffy_player');
SET @kkoma = (SELECT userID FROM Users WHERE username = 'kkoma_coach');

SET @t1 = (SELECT teamID FROM Teams WHERE teamName = 'T1');
SET @geng = (SELECT teamID FROM Teams WHERE teamName = 'Gen.G');
SET @csuf = (SELECT teamID FROM Teams WHERE teamName = 'CSUF Titans');



INSERT INTO Players (userID, gameTag, playerRank, lp) VALUES
(@faker, 'Faker', 'Challenger', 1200),
(@chovy, 'Chovy', 'Challenger', 1150),
(@tuffy, 'Titan', 'Diamond', 50);

INSERT INTO Coaches (userID, experienceYears, certification) VALUES
(@kkoma, 10, 'World Championship Coach');



INSERT INTO TeamMembers (teamID, userID, roleInTeam, status) VALUES
(@t1, @faker, 'Mid', 'Active'),
(@geng, @chovy, 'Mid', 'Active'),
(@csuf, @tuffy, 'Top', 'Active'),
(@t1, @kkoma, 'Coach', 'Active');


INSERT INTO Matches (matchDate, teamWon)
VALUES ('2026-04-15 18:00:00', @t1);

SET @match1 = LAST_INSERT_ID();

INSERT INTO MatchTeam
(matchID, teamID, matchDate, sidePlayed, outcome)
VALUES
(@match1, @t1, '2026-04-15 18:00:00', 'Blue', 'Win'),
(@match1, @geng, '2026-04-15 18:00:00', 'Red', 'Loss');


INSERT INTO Rounds (matchID, roundNumber, duration, outcome)
VALUES (@match1, 1, '00:32:10', 'Team1 Win');

SET @round1 = LAST_INSERT_ID();

INSERT INTO PlayerStats
(userID, roundID, teamID, championPlayed, kills, deaths, assists, creepScore, goldEarned)
VALUES
(@faker, @round1, @t1, 'Azir', 8, 2, 10, 285, 15400),
(@chovy, @round1, @geng, 'Orianna', 5, 4, 3, 270, 14200);

INSERT INTO TeamStats
(teamID, roundID, totalKills, totalGold, roundOutcome, totalPoints)
VALUES
(@t1, @round1, 18, 62000, 'Win', 1),
(@geng, @round1, 12, 57000, 'Loss', 0);




-- Visitor: public info only
GRANT SELECT ON EsportLeagueDB.Teams TO 'visitor_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.Matches TO 'visitor_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.MatchTeam TO 'visitor_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.Rounds TO 'visitor_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.TeamStats TO 'visitor_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.PlayerStats TO 'visitor_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.Players TO 'visitor_role'@'localhost';

-- Player: public info + own info
GRANT SELECT ON EsportLeagueDB.Teams TO 'player_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.TeamMembers TO 'player_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.Matches TO 'player_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.MatchTeam TO 'player_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.Rounds TO 'player_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.TeamStats TO 'player_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.PlayerStats TO 'player_role'@'localhost';
GRANT SELECT, UPDATE ON EsportLeagueDB.Players TO 'player_role'@'localhost';
GRANT SELECT, UPDATE (firstName, lastName, email) ON EsportLeagueDB.Users TO 'player_role'@'localhost';

-- Coach: view team/personal/match info and edit stats
GRANT SELECT ON EsportLeagueDB.Users TO 'coach_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.Players TO 'coach_role'@'localhost';
GRANT SELECT, UPDATE ON EsportLeagueDB.Coaches TO 'coach_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.Teams TO 'coach_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.TeamMembers TO 'coach_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.Matches TO 'coach_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.MatchTeam TO 'coach_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.Rounds TO 'coach_role'@'localhost';
GRANT SELECT, UPDATE ON EsportLeagueDB.PlayerStats TO 'coach_role'@'localhost';
GRANT SELECT, UPDATE ON EsportLeagueDB.TeamStats TO 'coach_role'@'localhost';

-- Ref has all perms
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Users TO 'referee_role'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Players TO 'referee_role'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Coaches TO 'referee_role'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Teams TO 'referee_role'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.TeamMembers TO 'referee_role'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Matches TO 'referee_role'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.MatchTeam TO 'referee_role'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Rounds TO 'referee_role'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.PlayerStats TO 'referee_role'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.TeamStats TO 'referee_role'@'localhost';
GRANT SELECT ON EsportLeagueDB.Roles TO 'referee_role'@'localhost';

FLUSH PRIVILEGES;
