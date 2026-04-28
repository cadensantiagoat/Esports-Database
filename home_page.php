<?php
  session_start();
  require_once('db_connect.php');

  // Get all Teams
  $teams_sql = "SELECT TeamID, TeamName FROM Teams ORDER BY TeamName";
  $teams_result = mysqli_query($db, $teams_sql);

  // Get all Matches (Joining Teams table to get actual names instead of IDs)
  $matches_sql = "SELECT 
                    m.MatchID, 
                    m.MatchDate, 
                    t1.TeamName AS Team1_Name, 
                    t2.TeamName AS Team2_Name 
                  FROM Matches m
                  JOIN Teams t1 ON m.Team1_ID = t1.TeamID
                  JOIN Teams t2 ON m.Team2_ID = t2.TeamID
                  ORDER BY m.MatchDate DESC";
  $matches_result = mysqli_query($db, $matches_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>LCK Esports Database</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div align="left">
        <h1>LCK Esports League</h1>
        
        <p>
            <?php if(isset($_SESSION['userID'])): ?>
                Welcome! <a href="logout.php">Logout</a>
            <?php else: ?>
                Viewing as Observer. <a href="login.php">Login</a> or <a href="register.php">Register</a>
            <?php endif; ?>
        </p>

        <hr>

        <h2>Active Teams</h2>
        <table style="border-collapse: collapse; width: 50%;">
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid black; padding: 5px;">Team ID</th>
                <th style="border: 1px solid black; padding: 5px;">Team Name</th>
            </tr>
            <?php while($row = mysqli_fetch_assoc($teams_result)): ?>
            <tr>
                <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo $row['TeamID']; ?></td>
                <td style="border: 1px solid black; padding: 5px;"><?php echo htmlspecialchars($row['TeamName']); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>

        <br><br>

        <h2>Matches</h2>
        <table style="border-collapse: collapse; width: 80%;">
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid black; padding: 5px;">Match Date</th>
                <th style="border: 1px solid black; padding: 5px;">Home</th>
                <th style="border: 1px solid black; padding: 5px;">vs</th>
                <th style="border: 1px solid black; padding: 5px;">Away</th>
                <th style="border: 1px solid black; padding: 5px;">Action</th>
            </tr>
            <?php while($row = mysqli_fetch_assoc($matches_result)): ?>
            <tr>
                <td style="border: 1px solid black; padding: 5px;"><?php echo $row['MatchDate']; ?></td>
                <td style="border: 1px solid black; padding: 5px; text-align: right;"><?php echo htmlspecialchars($row['Team1_Name']); ?></td>
                <td style="border: 1px solid black; padding: 5px; text-align: center;">vs</td>
                <td style="border: 1px solid black; padding: 5px;"><?php echo htmlspecialchars($row['Team2_Name']); ?></td>
                <td style="border: 1px solid black; padding: 5px; text-align: center;">
                    <a href="match_stats.php?match_id=<?php echo $row['MatchID']; ?>"><strong>View Stats</strong></a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>