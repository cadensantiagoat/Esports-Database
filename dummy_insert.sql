USE EsportLeagueDB;

START TRANSACTION;

-- Password for all users: password123
SET @password123_hash = '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO';

-- Upsert Teams
INSERT INTO Teams (teamName, status) VALUES
('T1', 'Active'),
('Gen.G', 'Active'),
('CSUF Titans', 'Active')
ON DUPLICATE KEY UPDATE teamName = VALUES(teamName), status = VALUES(status);

-- Upsert Users (roleID: 2=Player, 3=Coach, 5=Exec. Manager)
INSERT INTO Users (firstName, lastName, username, email, passwordHash, roleID) VALUES
('Sang-hyeok', 'Lee', 'faker_player', 'faker@t1.gg', @password123_hash, 2),
('Hyeon-jun', 'Mun', 'oner_player', 'oner@t1.gg', @password123_hash, 2),
('Su-hwan', 'Kim', 'peyz_player', 'peyz@t1.gg', @password123_hash, 2),
('Min-seok', 'Ryu', 'keria_player', 'keria@t1.gg', @password123_hash, 2),
('Hyeon-joon', 'Choi', 'doran_player', 'doran@t1.gg', @password123_hash, 2),
('Ji-hoon', 'Jeong', 'chovy_player', 'chovy@geng.gg', @password123_hash, 2),
('Geon-bu', 'Kim', 'canyon_player', 'canyon@geng.gg', @password123_hash, 2),
('Jae-hyuk', 'Park', 'ruler_player', 'ruler@geng.gg', @password123_hash, 2),
('Min-kyu', 'Joo', 'duro_player', 'duro@geng.gg', @password123_hash, 2),
('Gi-in', 'Kim', 'kiin_player', 'kiin@geng.gg', @password123_hash, 2),
('Tuffy', 'Player', 'tuffy_player', 'top@fullerton.edu', @password123_hash, 2),
('Caden', 'Santiago', 'caden_player', 'cadenb.santiago@csu.fullerton.edu', @password123_hash, 2),
('Julian', 'Luo', 'julian_player', 'julolv365@csu.fullerton.edu', @password123_hash, 2),
('Xiaoju', 'Feng', 'xiaoju_player', 'xfeng3@csu.fullerton.edu', @password123_hash, 2),
('Evan', 'Booth', 'evan_player', 'evanbooth@csu.fullerton.edu', @password123_hash, 2),
('Jeong-gyun', 'Kim', 'kkoma_coach', 'kkoma@t1.gg', @password123_hash, 3),
('Sang-wook', 'Ryu', 'ryu_coach', 'ryu@geng.gg', @password123_hash, 3),
('Tim', 'Langsdorf', 'langsdorf_coach', 'langsdorf@csu.fullerton.edu', @password123_hash, 3),
('Ricky', 'Referee', 'referee_user', 'referee@lck.gg', @password123_hash, 4),
('Executive', 'Manager', 'executive_manager', 'exec@lck.gg', @password123_hash, 5),
('Caden', 'Test', 'CadenTest', 'cadenb.santiago@gmail.com', @password123_hash, 2)
ON DUPLICATE KEY UPDATE
    firstName = VALUES(firstName),
    lastName = VALUES(lastName),
    email = VALUES(email),
    passwordHash = VALUES(passwordHash),
    roleID = VALUES(roleID);

SET @team_t1 = (SELECT teamID FROM Teams WHERE teamName = 'T1');
SET @team_geng = (SELECT teamID FROM Teams WHERE teamName = 'Gen.G');
SET @team_csuf = (SELECT teamID FROM Teams WHERE teamName = 'CSUF Titans');

SET @user_faker = (SELECT userID FROM Users WHERE username = 'faker_player');
SET @user_oner = (SELECT userID FROM Users WHERE username = 'oner_player');
SET @user_peyz = (SELECT userID FROM Users WHERE username = 'peyz_player');
SET @user_keria = (SELECT userID FROM Users WHERE username = 'keria_player');
SET @user_doran = (SELECT userID FROM Users WHERE username = 'doran_player');
SET @user_chovy = (SELECT userID FROM Users WHERE username = 'chovy_player');
SET @user_canyon = (SELECT userID FROM Users WHERE username = 'canyon_player');
SET @user_ruler = (SELECT userID FROM Users WHERE username = 'ruler_player');
SET @user_duro = (SELECT userID FROM Users WHERE username = 'duro_player');
SET @user_kiin = (SELECT userID FROM Users WHERE username = 'kiin_player');
SET @user_tuffy = (SELECT userID FROM Users WHERE username = 'tuffy_player');
SET @user_caden = (SELECT userID FROM Users WHERE username = 'caden_player');
SET @user_julian = (SELECT userID FROM Users WHERE username = 'julian_player');
SET @user_xiaoju = (SELECT userID FROM Users WHERE username = 'xiaoju_player');
SET @user_evan = (SELECT userID FROM Users WHERE username = 'evan_player');
SET @user_kkoma = (SELECT userID FROM Users WHERE username = 'kkoma_coach');
SET @user_ryu = (SELECT userID FROM Users WHERE username = 'ryu_coach');
SET @user_langsdorf = (SELECT userID FROM Users WHERE username = 'langsdorf_coach');
SET @user_cadentest = (SELECT userID FROM Users WHERE username = 'CadenTest');

INSERT INTO Players (userID, gameTag, playerRank, lp) VALUES
(@user_faker, 'Faker', 'Challenger', 1200),
(@user_oner, 'Oner', 'Challenger', 1090),
(@user_peyz, 'Peyz', 'Challenger', 1110),
(@user_keria, 'Keria', 'Challenger', 980),
(@user_doran, 'Doran', 'Grandmaster', 860),
(@user_chovy, 'Chovy', 'Challenger', 1190),
(@user_canyon, 'Canyon', 'Challenger', 1080),
(@user_ruler, 'Ruler', 'Challenger', 1040),
(@user_duro, 'Duro', 'Grandmaster', 820),
(@user_kiin, 'Kiin', 'Grandmaster', 900),
(@user_tuffy, 'Tuffy', 'Diamond', 730),
(@user_caden, 'Caden', 'Emerald', 520),
(@user_julian, 'Julian', 'Platinum', 450),
(@user_xiaoju, 'Xiaoju', 'Platinum', 460),
(@user_evan, 'Evan', 'Emerald', 530),
(@user_cadentest, 'Hide on bush', 'Gold', 800)
ON DUPLICATE KEY UPDATE
    gameTag = VALUES(gameTag),
    playerRank = VALUES(playerRank),
    lp = VALUES(lp);

INSERT INTO Coaches (userID, experienceYears, certification) VALUES
(@user_kkoma, 12, 'LCK Head Coach Certified'),
(@user_ryu, 8, 'LCK Head Coach Certified'),
(@user_langsdorf, 10, 'CSUF Program Certified')
ON DUPLICATE KEY UPDATE
    experienceYears = VALUES(experienceYears),
    certification = VALUES(certification);

-- TeamMembers: lane roles per ddl3 ENUM (Top, Jgl, Mid, Bot, Sup, Coach, Sub)
INSERT INTO TeamMembers (teamID, userID, roleInTeam, status) VALUES
(@team_t1, @user_faker, 'Mid', 'Active'),
(@team_t1, @user_oner, 'Jgl', 'Active'),
(@team_t1, @user_peyz, 'Bot', 'Active'),
(@team_t1, @user_keria, 'Sup', 'Active'),
(@team_t1, @user_doran, 'Top', 'Active'),
(@team_t1, @user_kkoma, 'Coach', 'Active'),
(@team_geng, @user_chovy, 'Mid', 'Active'),
(@team_geng, @user_canyon, 'Jgl', 'Active'),
(@team_geng, @user_ruler, 'Bot', 'Active'),
(@team_geng, @user_duro, 'Sup', 'Active'),
(@team_geng, @user_kiin, 'Top', 'Active'),
(@team_geng, @user_ryu, 'Coach', 'Active'),
(@team_csuf, @user_tuffy, 'Top', 'Active'),
(@team_csuf, @user_caden, 'Jgl', 'Active'),
(@team_csuf, @user_julian, 'Bot', 'Active'),
(@team_csuf, @user_xiaoju, 'Mid', 'Active'),
(@team_csuf, @user_evan, 'Sup', 'Active'),
(@team_csuf, @user_langsdorf, 'Coach', 'Active')
ON DUPLICATE KEY UPDATE
    roleInTeam = VALUES(roleInTeam),
    status = VALUES(status);

DELETE FROM TeamStats;
DELETE FROM PlayerStats;
DELETE FROM Rounds;
DELETE FROM MatchTeam;
DELETE FROM Matches;

INSERT INTO Matches (matchDate, teamWon) VALUES ('2026-04-15 18:00:00', @team_t1);
SET @match_t1_geng = LAST_INSERT_ID();

INSERT INTO Matches (matchDate, teamWon) VALUES ('2026-04-20 19:30:00', @team_t1);
SET @match_t1_csuf = LAST_INSERT_ID();

INSERT INTO Matches (matchDate, teamWon) VALUES ('2026-04-27 20:00:00', @team_geng);
SET @match_geng_csuf = LAST_INSERT_ID();

INSERT INTO MatchTeam (matchID, teamID, matchDate, sidePlayed, outcome) VALUES
(@match_t1_geng, @team_t1, '2026-04-15 18:00:00', 'Blue', 'Win'),
(@match_t1_geng, @team_geng, '2026-04-15 18:00:00', 'Red', 'Loss'),
(@match_t1_csuf, @team_t1, '2026-04-20 19:30:00', 'Blue', 'Win'),
(@match_t1_csuf, @team_csuf, '2026-04-20 19:30:00', 'Red', 'Loss'),
(@match_geng_csuf, @team_geng, '2026-04-27 20:00:00', 'Blue', 'Win'),
(@match_geng_csuf, @team_csuf, '2026-04-27 20:00:00', 'Red', 'Loss');

INSERT INTO Rounds (matchID, roundNumber, duration, outcome) VALUES
(@match_t1_geng, 1, '00:35:00', 'Team1 Win');
SET @round_t1_geng = LAST_INSERT_ID();

INSERT INTO Rounds (matchID, roundNumber, duration, outcome) VALUES
(@match_t1_csuf, 1, '00:33:00', 'Team1 Win');
SET @round_t1_csuf = LAST_INSERT_ID();

INSERT INTO Rounds (matchID, roundNumber, duration, outcome) VALUES
(@match_geng_csuf, 1, '00:36:00', 'Team1 Win');
SET @round_geng_csuf = LAST_INSERT_ID();

INSERT INTO PlayerStats (userID, roundID, teamID, championPlayed, kills, deaths, assists, creepScore, goldEarned) VALUES
(@user_faker, @round_t1_geng, @team_t1, 'Azir', 9, 2, 8, 318, 16200),
(@user_oner, @round_t1_geng, @team_t1, 'Lee Sin', 3, 3, 11, 214, 12800),
(@user_peyz, @round_t1_geng, @team_t1, 'Jinx', 7, 2, 6, 332, 14900),
(@user_keria, @round_t1_geng, @team_t1, 'Nautilus', 1, 4, 14, 48, 9700),
(@user_doran, @round_t1_geng, @team_t1, 'Gnar', 4, 3, 7, 279, 13300),
(@user_chovy, @round_t1_geng, @team_geng, 'Orianna', 6, 4, 5, 301, 14500),
(@user_canyon, @round_t1_geng, @team_geng, 'Wukong', 2, 5, 9, 196, 12100),
(@user_ruler, @round_t1_geng, @team_geng, 'Aphelios', 5, 3, 4, 320, 14100),
(@user_duro, @round_t1_geng, @team_geng, 'Rakan', 1, 5, 12, 44, 9300),
(@user_kiin, @round_t1_geng, @team_geng, 'Renekton', 3, 4, 6, 267, 12600),
(@user_faker, @round_t1_csuf, @team_t1, 'Sylas', 11, 1, 9, 327, 17800),
(@user_oner, @round_t1_csuf, @team_t1, 'Xin Zhao', 4, 2, 13, 205, 13600),
(@user_peyz, @round_t1_csuf, @team_t1, 'Zeri', 8, 2, 7, 340, 16000),
(@user_keria, @round_t1_csuf, @team_t1, 'Thresh', 1, 3, 16, 39, 9800),
(@user_doran, @round_t1_csuf, @team_t1, 'Jax', 5, 2, 8, 288, 14100),
(@user_tuffy, @round_t1_csuf, @team_csuf, 'Aatrox', 9, 2, 8, 309, 16900),
(@user_caden, @round_t1_csuf, @team_csuf, 'Viego', 4, 3, 12, 199, 13200),
(@user_julian, @round_t1_csuf, @team_csuf, 'Kai''Sa', 8, 3, 7, 331, 15700),
(@user_xiaoju, @round_t1_csuf, @team_csuf, 'Ahri', 5, 4, 10, 281, 13900),
(@user_evan, @round_t1_csuf, @team_csuf, 'Braum', 2, 3, 15, 42, 10100),
(@user_chovy, @round_geng_csuf, @team_geng, 'Azir', 10, 2, 8, 322, 17100),
(@user_canyon, @round_geng_csuf, @team_geng, 'Poppy', 3, 3, 10, 201, 13200),
(@user_ruler, @round_geng_csuf, @team_geng, 'Jinx', 7, 2, 6, 334, 15500),
(@user_duro, @round_geng_csuf, @team_geng, 'Alistar', 1, 4, 13, 35, 9900),
(@user_kiin, @round_geng_csuf, @team_geng, 'K''Sante', 4, 3, 7, 274, 13700),
(@user_tuffy, @round_geng_csuf, @team_csuf, 'Camille', 8, 3, 6, 315, 16000),
(@user_caden, @round_geng_csuf, @team_csuf, 'Jarvan IV', 4, 4, 10, 188, 12800),
(@user_julian, @round_geng_csuf, @team_csuf, 'Xayah', 7, 3, 7, 326, 15100),
(@user_xiaoju, @round_geng_csuf, @team_csuf, 'LeBlanc', 3, 5, 9, 268, 12300),
(@user_evan, @round_geng_csuf, @team_csuf, 'Leona', 2, 4, 13, 31, 9700);

INSERT INTO TeamStats (teamID, roundID, totalKills, totalGold, roundOutcome, totalPoints) VALUES
(@team_t1, @round_t1_geng, 24, 66900, 'Win', 1),
(@team_geng, @round_t1_geng, 17, 62600, 'Loss', 0),
(@team_t1, @round_t1_csuf, 29, 71300, 'Win', 1),
(@team_csuf, @round_t1_csuf, 28, 69800, 'Loss', 0),
(@team_geng, @round_geng_csuf, 25, 69400, 'Win', 1),
(@team_csuf, @round_geng_csuf, 24, 67600, 'Loss', 0)
ON DUPLICATE KEY UPDATE
    totalKills = VALUES(totalKills),
    totalGold = VALUES(totalGold),
    roundOutcome = VALUES(roundOutcome),
    totalPoints = VALUES(totalPoints);

COMMIT;
