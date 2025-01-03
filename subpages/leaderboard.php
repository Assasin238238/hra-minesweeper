<?php
session_start();
require_once "database.php";

// Ověření, zda je uživatel přihlášen
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

// Načtení dat z databáze
$sql = "SELECT users.nick_name, leaderboard.difficulty, leaderboard.time 
        FROM leaderboard 
        JOIN users ON leaderboard.user_id = users.id 
        ORDER BY leaderboard.difficulty, leaderboard.time ASC 
        LIMIT 10";
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Query failed: " . mysqli_error($conn)); // Pokud dotaz selže
}

echo "<pre>";
while ($row = mysqli_fetch_assoc($result)) {
    print_r($row); // Vypíše načtené řádky
}
echo "</pre>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minesweeper Leaderboard</title>
    <link rel="stylesheet" href="../css/leaderboard.css">
</head>
<body>
    <header>
        <div class="navbar">
            <div class="logo"><a href="../index.php">Minesweeper Game</a></div>
        </div>
    </header>

    <main>
        <h1>Leaderboard</h1>
        <table>
            <thead>
                <tr>
                    <th>Player</th>
                    <th>Difficulty</th>
                    <th>Time (s)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row["nick_name"]) ?></td>
                            <td><?= htmlspecialchars(ucfirst($row["difficulty"])) ?></td>
                            <td><?= htmlspecialchars($row["time"]) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No results yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <footer>
        <?php include "../php/footer.php"; ?>
    </footer>
</body>
</html>
