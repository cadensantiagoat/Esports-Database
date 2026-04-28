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
('chovy_player', 'chovy@geng.gg', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('tuffy_player', 'top@fullerton.edu', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Player'),
('kkoma_coach', 'kkoma@t1.gg', '$2y$10$hPi5VIvv9jZOF0zvkxegF.wTSJ0bwb2ulx92oLWjRu4hR.1vnizuO', 'Coach'),
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
SET @user_chovy = (SELECT userID FROM Users WHERE Username = 'chovy_player');
SET @user_tuffy = (SELECT userID FROM Users WHERE Username = 'tuffy_player');

-- Upsert Players (safe to rerun because Players.userID is PRIMARY KEY)
INSERT INTO Players (userID, TeamID, GameTag, Rank) VALUES
(@user_faker, @team_t1, 'Faker', 'Challenger'),
(@user_chovy, @team_geng, 'Chovy', 'Challenger'),
(@user_tuffy, @team_csuf, 'Titan', 'Diamond')
ON DUPLICATE KEY UPDATE
    TeamID = VALUES(TeamID),
    GameTag = VALUES(GameTag),
    Rank = VALUES(Rank);

-- Rebuild only this dummy script's match/stat fixtures to prevent duplicates.
--    (Matches/Stats tables do not have uniqueness constraints for these rows.)
DELETE s
FROM Stats s
JOIN Matches m ON s.MatchID = m.MatchID
WHERE (m.Team1_ID = @team_t1 AND m.Team2_ID = @team_geng)
   OR (m.Team1_ID = @team_t1 AND m.Team2_ID = @team_csuf);

DELETE FROM Matches
WHERE (Team1_ID = @team_t1 AND Team2_ID = @team_geng)
   OR (Team1_ID = @team_t1 AND Team2_ID = @team_csuf);

-- Insert Matches and capture generated MatchIDs
INSERT INTO Matches (Team1_ID, Team2_ID, MatchDate)
VALUES (@team_t1, @team_geng, '2026-04-15 18:00:00');
SET @match_t1_geng = LAST_INSERT_ID();

INSERT INTO Matches (Team1_ID, Team2_ID, MatchDate)
VALUES (@team_t1, @team_csuf, '2026-04-20 19:30:00');
SET @match_t1_csuf = LAST_INSERT_ID();

-- Insert Stats linked to the newly inserted matches
INSERT INTO Stats (MatchID, PlayerID, Kills, Deaths, Assists, GoldEarned) VALUES
(@match_t1_geng, @user_faker, 8, 2, 10, 15400),   -- Faker (T1)
(@match_t1_geng, @user_chovy, 5, 4, 3, 14200),    -- Chovy (Gen.G)
(@match_t1_csuf, @user_faker, 12, 0, 5, 18000),   -- Faker (T1)
(@match_t1_csuf, @user_tuffy, 1, 10, 2, 8000);    -- CSUF Titan

COMMIT;