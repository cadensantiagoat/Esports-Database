USE EsportLeagueDB;

START TRANSACTION;

-- Upsert Teams (safe to rerun because TeamName is UNIQUE)
INSERT INTO Teams (TeamName) VALUES
('T1'),
('Gen.G'),
('CSUF Titans')
ON DUPLICATE KEY UPDATE TeamName = VALUES(TeamName);

-- Upsert Users (safe to rerun because Username/Email are UNIQUE)
-- Password hash is bcrypt for 'password123'
INSERT INTO Users (Username, Email, PasswordHash, Role) VALUES
('faker_player', 'faker@t1.gg', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('oner_player', 'oner@t1.gg', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('peyz_player', 'peyz@t1.gg', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('keria_player', 'keria@t1.gg', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('doran_player', 'doran@t1.gg', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('chovy_player', 'chovy@geng.gg', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('canyon_player', 'canyon@geng.gg', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('ruler_player', 'ruler@geng.gg', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('duro_player', 'duro@geng.gg', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('kiin_player', 'kiin@geng.gg', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('tuffy_player', 'top@fullerton.edu', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('caden_player', 'cadenb.santiago@csu.fullerton.edu', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('julian_player', 'julolv365@csu.fullerton.edu', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('xiaoju_player', 'xfeng3@csu.fullerton.edu', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('evan_player', 'evanbooth@csu.fullerton.edu', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('kkoma_coach', 'kkoma@t1.gg', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Coach'),
('ryu_coach', 'ryu@geng.gg', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Coach'),
('langsdorf_coach', 'langsdorf@csu.fullerton.edu', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Coach'),
('league_owner', 'admin@lck.gg', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'League Owner')
ON DUPLICATE KEY UPDATE
    Email = VALUES(Email),
    PasswordHash = VALUES(PasswordHash),
    Role = VALUES(Role);

-- Resolve IDs by stable keys (avoid hardcoded AUTO_INCREMENT ids)
SET @team_t1 = (SELECT TeamID FROM Teams WHERE TeamName = 'T1');
SET @team_geng = (SELECT TeamID FROM Teams WHERE TeamName = 'Gen.G');
SET @team_csuf = (SELECT TeamID FROM Teams WHERE TeamName = 'CSUF Titans');

SET @user_faker = (SELECT userID FROM Users WHERE Username = 'faker_player');
SET @user_oner = (SELECT userID FROM Users WHERE Username = 'oner_player');
SET @user_peyz = (SELECT userID FROM Users WHERE Username = 'peyz_player');
SET @user_keria = (SELECT userID FROM Users WHERE Username = 'keria_player');
SET @user_doran = (SELECT userID FROM Users WHERE Username = 'doran_player');
SET @user_chovy = (SELECT userID FROM Users WHERE Username = 'chovy_player');
SET @user_canyon = (SELECT userID FROM Users WHERE Username = 'canyon_player');
SET @user_ruler = (SELECT userID FROM Users WHERE Username = 'ruler_player');
SET @user_duro = (SELECT userID FROM Users WHERE Username = 'duro_player');
SET @user_kiin = (SELECT userID FROM Users WHERE Username = 'kiin_player');
SET @user_tuffy = (SELECT userID FROM Users WHERE Username = 'tuffy_player');
SET @user_caden = (SELECT userID FROM Users WHERE Username = 'caden_player');
SET @user_julian = (SELECT userID FROM Users WHERE Username = 'julian_player');
SET @user_xiaoju = (SELECT userID FROM Users WHERE Username = 'xiaoju_player');
SET @user_evan = (SELECT userID FROM Users WHERE Username = 'evan_player');
SET @user_kkoma = (SELECT userID FROM Users WHERE Username = 'kkoma_coach');
SET @user_ryu = (SELECT userID FROM Users WHERE Username = 'ryu_coach');
SET @user_langsdorf = (SELECT userID FROM Users WHERE Username = 'langsdorf_coach');

-- Upsert Players (safe to rerun because Players.userID is PRIMARY KEY)
INSERT INTO Players (userID, TeamID, GameTag, Rank) VALUES
(@user_faker, @team_t1, 'Faker', 'Challenger'),
(@user_oner, @team_t1, 'Oner', 'Challenger'),
(@user_peyz, @team_t1, 'Peyz', 'Challenger'),
(@user_keria, @team_t1, 'Keria', 'Challenger'),
(@user_doran, @team_t1, 'Doran', 'Grandmaster'),
(@user_chovy, @team_geng, 'Chovy', 'Challenger'),
(@user_canyon, @team_geng, 'Canyon', 'Challenger'),
(@user_ruler, @team_geng, 'Ruler', 'Challenger'),
(@user_duro, @team_geng, 'Duro', 'Grandmaster'),
(@user_kiin, @team_geng, 'Kiin', 'Grandmaster'),
(@user_tuffy, @team_csuf, 'Tuffy', 'Diamond'),
(@user_caden, @team_csuf, 'Caden', 'Emerald'),
(@user_julian, @team_csuf, 'Julian', 'Platinum'),
(@user_xiaoju, @team_csuf, 'Xiaoju', 'Platinum'),
(@user_evan, @team_csuf, 'Evan', 'Emerald')
ON DUPLICATE KEY UPDATE
    TeamID = VALUES(TeamID),
    GameTag = VALUES(GameTag),
    Rank = VALUES(Rank);

-- Upsert coaches and assign kkoma to T1.
INSERT INTO Coaches (userID, TeamID, ExperienceYears) VALUES
(@user_kkoma, @team_t1, 12),
(@user_ryu, @team_geng, 8),
(@user_langsdorf, @team_csuf, 10)
ON DUPLICATE KEY UPDATE
    TeamID = VALUES(TeamID),
    ExperienceYears = VALUES(ExperienceYears);

-- Insert 3 new matches and capture generated MatchIDs.
INSERT INTO Matches (Team1_ID, Team2_ID, MatchDate)
VALUES (@team_t1, @team_geng, '2026-04-15 18:00:00');
SET @match_t1_geng = LAST_INSERT_ID();

INSERT INTO Matches (Team1_ID, Team2_ID, MatchDate)
VALUES (@team_t1, @team_csuf, '2026-04-20 19:30:00');
SET @match_t1_csuf = LAST_INSERT_ID();

INSERT INTO Matches (Team1_ID, Team2_ID, MatchDate)
VALUES (@team_geng, @team_csuf, '2026-04-27 20:00:00');
SET @match_geng_csuf = LAST_INSERT_ID();

-- Insert stats for all starters in each matchup.
INSERT INTO Stats (MatchID, PlayerID, Kills, Deaths, Assists, GoldEarned) VALUES
-- Match 1: T1 vs Gen.G
(@match_t1_geng, @user_faker, 9, 2, 8, 16200),
(@match_t1_geng, @user_oner, 3, 3, 11, 12800),
(@match_t1_geng, @user_peyz, 7, 2, 6, 14900),
(@match_t1_geng, @user_keria, 1, 4, 14, 9700),
(@match_t1_geng, @user_doran, 4, 3, 7, 13300),
(@match_t1_geng, @user_chovy, 6, 4, 5, 14500),
(@match_t1_geng, @user_canyon, 2, 5, 9, 12100),
(@match_t1_geng, @user_ruler, 5, 3, 4, 14100),
(@match_t1_geng, @user_duro, 1, 5, 12, 9300),
(@match_t1_geng, @user_kiin, 3, 4, 6, 12600),

-- Match 2: T1 vs CSUF Titans
(@match_t1_csuf, @user_faker, 11, 1, 9, 17800),
(@match_t1_csuf, @user_oner, 4, 2, 13, 13600),
(@match_t1_csuf, @user_peyz, 8, 2, 7, 16000),
(@match_t1_csuf, @user_keria, 1, 3, 16, 9800),
(@match_t1_csuf, @user_doran, 5, 2, 8, 14100),
(@match_t1_csuf, @user_tuffy, 9, 2, 8, 16900),
(@match_t1_csuf, @user_caden, 4, 3, 12, 13200),
(@match_t1_csuf, @user_julian, 8, 3, 7, 15700),
(@match_t1_csuf, @user_xiaoju, 5, 4, 10, 13900),
(@match_t1_csuf, @user_evan, 2, 3, 15, 10100),

-- Match 3: Gen.G vs CSUF Titans
(@match_geng_csuf, @user_chovy, 10, 2, 8, 17100),
(@match_geng_csuf, @user_canyon, 3, 3, 10, 13200),
(@match_geng_csuf, @user_ruler, 7, 2, 6, 15500),
(@match_geng_csuf, @user_duro, 1, 4, 13, 9900),
(@match_geng_csuf, @user_kiin, 4, 3, 7, 13700),
(@match_geng_csuf, @user_tuffy, 8, 3, 6, 16000),
(@match_geng_csuf, @user_caden, 4, 4, 10, 12800),
(@match_geng_csuf, @user_julian, 7, 3, 7, 15100),
(@match_geng_csuf, @user_xiaoju, 3, 5, 9, 12300),
(@match_geng_csuf, @user_evan, 2, 4, 13, 9700);

COMMIT;