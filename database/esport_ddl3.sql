-- Final DDL (ERD-aligned) for EsportLeagueDB
-- Notes:
-- - Requires CREATE USER (run as root for Docker): mariadb -uroot -prootpassword < esport_ddl3.sql
--   The app user `user` cannot run this file; use `user` only for dummy_insert.sql after schema exists.
-- - Creates MySQL role accounts + GRANTs (optional DB-level security).
-- - Also GRANTs the Docker app user (user/% from docker-compose) full access
--   so existing PHP (single mysqli user) continues to work.
-- - If using a VM, you may use 'localhost' instead of '%' for the role users.

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS EsportLeagueDB;
CREATE DATABASE EsportLeagueDB;
USE EsportLeagueDB;
SET FOREIGN_KEY_CHECKS = 1;

-- Clean up old demo accounts if re-running on same server
DROP USER IF EXISTS 'visitor_role'@'%';
DROP USER IF EXISTS 'player_role'@'%';
DROP USER IF EXISTS 'coach_role'@'%';
DROP USER IF EXISTS 'referee_role'@'%';
DROP USER IF EXISTS 'league_owner_role'@'%';

CREATE USER 'visitor_role'@'%' IDENTIFIED BY '!visitor' PASSWORD EXPIRE NEVER;
CREATE USER 'player_role'@'%' IDENTIFIED BY '!player' PASSWORD EXPIRE NEVER;
CREATE USER 'coach_role'@'%' IDENTIFIED BY '!coach' PASSWORD EXPIRE NEVER;
CREATE USER 'referee_role'@'%' IDENTIFIED BY '!referee' PASSWORD EXPIRE NEVER;
CREATE USER 'league_owner_role'@'%' IDENTIFIED BY '!league_owner' PASSWORD EXPIRE NEVER;

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
    FOREIGN KEY (teamWon) REFERENCES Teams(teamID) ON DELETE SET NULL
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
(4, 'Referee', 'referee_role'),
(5, 'League Owner', 'league_owner_role');

-- Docker Compose MariaDB app account (must match db_connect.php)
CREATE USER IF NOT EXISTS 'user'@'%' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON EsportLeagueDB.* TO 'user'@'%';
FLUSH PRIVILEGES;

-- Visitor: public info only
GRANT SELECT ON EsportLeagueDB.Teams TO 'visitor_role'@'%';
GRANT SELECT ON EsportLeagueDB.Matches TO 'visitor_role'@'%';
GRANT SELECT ON EsportLeagueDB.MatchTeam TO 'visitor_role'@'%';
GRANT SELECT ON EsportLeagueDB.Rounds TO 'visitor_role'@'%';
GRANT SELECT ON EsportLeagueDB.TeamStats TO 'visitor_role'@'%';
GRANT SELECT ON EsportLeagueDB.PlayerStats TO 'visitor_role'@'%';
GRANT SELECT ON EsportLeagueDB.Players TO 'visitor_role'@'%';

-- Player
GRANT SELECT ON EsportLeagueDB.Teams TO 'player_role'@'%';
GRANT SELECT ON EsportLeagueDB.TeamMembers TO 'player_role'@'%';
GRANT SELECT ON EsportLeagueDB.Matches TO 'player_role'@'%';
GRANT SELECT ON EsportLeagueDB.MatchTeam TO 'player_role'@'%';
GRANT SELECT ON EsportLeagueDB.Rounds TO 'player_role'@'%';
GRANT SELECT ON EsportLeagueDB.TeamStats TO 'player_role'@'%';
GRANT SELECT ON EsportLeagueDB.PlayerStats TO 'player_role'@'%';
GRANT SELECT, UPDATE ON EsportLeagueDB.Players TO 'player_role'@'%';
GRANT SELECT, UPDATE (firstName, lastName, email) ON EsportLeagueDB.Users TO 'player_role'@'%';

-- Coach
GRANT SELECT ON EsportLeagueDB.Users TO 'coach_role'@'%';
GRANT SELECT ON EsportLeagueDB.Players TO 'coach_role'@'%';
GRANT SELECT, UPDATE ON EsportLeagueDB.Coaches TO 'coach_role'@'%';
GRANT SELECT ON EsportLeagueDB.Teams TO 'coach_role'@'%';
GRANT SELECT ON EsportLeagueDB.TeamMembers TO 'coach_role'@'%';
GRANT SELECT ON EsportLeagueDB.Matches TO 'coach_role'@'%';
GRANT SELECT ON EsportLeagueDB.MatchTeam TO 'coach_role'@'%';
GRANT SELECT ON EsportLeagueDB.Rounds TO 'coach_role'@'%';
GRANT SELECT, UPDATE ON EsportLeagueDB.PlayerStats TO 'coach_role'@'%';
GRANT SELECT, UPDATE ON EsportLeagueDB.TeamStats TO 'coach_role'@'%';

-- Referee (broad write access)
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Users TO 'referee_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Players TO 'referee_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Coaches TO 'referee_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Teams TO 'referee_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.TeamMembers TO 'referee_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Matches TO 'referee_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.MatchTeam TO 'referee_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Rounds TO 'referee_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.PlayerStats TO 'referee_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.TeamStats TO 'referee_role'@'%';
GRANT SELECT ON EsportLeagueDB.Roles TO 'referee_role'@'%';

-- League Owner (full schema control within DB objects)
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Users TO 'league_owner_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Players TO 'league_owner_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Coaches TO 'league_owner_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Teams TO 'league_owner_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.TeamMembers TO 'league_owner_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Matches TO 'league_owner_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.MatchTeam TO 'league_owner_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.Rounds TO 'league_owner_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.PlayerStats TO 'league_owner_role'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON EsportLeagueDB.TeamStats TO 'league_owner_role'@'%';
GRANT SELECT ON EsportLeagueDB.Roles TO 'league_owner_role'@'%';

FLUSH PRIVILEGES;
