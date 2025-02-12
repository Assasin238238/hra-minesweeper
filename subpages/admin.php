<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

require_once "database.php"; // Připojení k databázi

// Kontrola, zda je uživatel admin
$nickName = $_SESSION["user"];
$sql = "SELECT * FROM admins WHERE nick_name = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $nickName);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$isAdmin = mysqli_fetch_assoc($result) ? true : false;

// Zpracování přidání nového admina
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["new_admin"])) {
    $newAdmin = trim($_POST["new_admin"]);

    // Ověření, zda uživatel existuje
    $checkUserSql = "SELECT nick_name FROM users WHERE nick_name = ?";
    $stmt = mysqli_prepare($conn, $checkUserSql);
    mysqli_stmt_bind_param($stmt, "s", $newAdmin);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        // Přidání uživatele do adminů
        $addAdminSql = "INSERT INTO admins (nick_name) VALUES (?)";
        $addStmt = mysqli_prepare($conn, $addAdminSql);
        mysqli_stmt_bind_param($addStmt, "s", $newAdmin);
        mysqli_stmt_execute($addStmt);
        $message = "User is being set as admin.";
    } else {
        $message = "User doesn't exist.";
    }
}

// Načtení všech adminů
$adminsQuery = "SELECT u.nick_name, u.email FROM users u 
                INNER JOIN admins a ON LOWER(u.nick_name) = LOWER(a.nick_name)";
$adminsResult = mysqli_query($conn, $adminsQuery);

if (!$adminsResult) {
    die("Chyba při načítání adminů: " . mysqli_error($conn));
}

// Načtení všech guestů
$guestsQuery = "SELECT u.nick_name, u.email FROM users u 
                LEFT JOIN admins a ON LOWER(u.nick_name) = LOWER(a.nick_name)
                WHERE a.nick_name IS NULL";
$guestsResult = mysqli_query($conn, $guestsQuery);

if (!$guestsResult) {
    die("Chyba při načítání guestů: " . mysqli_error($conn));
}

// Zpracování odebrání admina
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remove_admin"])) {
    $adminToRemove = trim($_POST["remove_admin"]);
    
    // Získání ID uživatele na základě přezdívky
    $getUserIdSql = "SELECT id FROM users WHERE nick_name = ?";
    $stmt = mysqli_prepare($conn, $getUserIdSql);
    mysqli_stmt_bind_param($stmt, "s", $adminToRemove);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        $userId = $user['id'];

        // Smazání všech záznamů z tabulky leaderboard
        $deleteLeaderboardSql = "DELETE FROM leaderboard WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $deleteLeaderboardSql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);

        // Smazání admina z tabulky admins
        $removeAdminSql = "DELETE FROM admins WHERE nick_name = ?";
        $stmt = mysqli_prepare($conn, $removeAdminSql);
        mysqli_stmt_bind_param($stmt, "s", $adminToRemove);
        mysqli_stmt_execute($stmt);

        // Smazání uživatele z tabulky users
        $deleteUserSql = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $deleteUserSql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);

        $message = "Admin and their progress have been removed.";
    } else {
        $message = "Admin not found.";
    }

    // Obnovení seznamu adminů
    $adminsResult = mysqli_query($conn, $adminsQuery);
}

// Zpracování odebrání hosta
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remove_user"])) {
    $userToRemove = trim($_POST["remove_user"]);

    // Získání ID uživatele na základě přezdívky
    $getUserIdSql = "SELECT id FROM users WHERE nick_name = ?";
    $stmt = mysqli_prepare($conn, $getUserIdSql);
    mysqli_stmt_bind_param($stmt, "s", $userToRemove);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        $userId = $user['id'];

        // Smazání všech záznamů z tabulky leaderboard
        $deleteLeaderboardSql = "DELETE FROM leaderboard WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $deleteLeaderboardSql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);

        // Smazání uživatele z tabulky users
        $deleteUserSql = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $deleteUserSql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);

        $message = "User and their progress have been removed.";
    } else {
        $message = "User not found.";
    }

    // Obnovení seznamu hostů
    $guestsResult = mysqli_query($conn, $guestsQuery);
}
?>


<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minesweeper Game - Administration</title>
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<header>
    <div class="navbar">
        <div class="logo"><a href="../index.php" id="title">Minesweeper Game</a></div>
        <a href="logout.php" class="action_btn" id="logout-btn">Logout</a>
    </div>
</header>
<main>
    <!-- Sekce pro administrátory -->
    <section id="admins">
        <h1>Administrators</h1>
        <?php while ($admin = mysqli_fetch_assoc($adminsResult)): ?>
            <div class="user-card">
                <div class="user-info">
                    <p><strong>Nickname:</strong> <?= htmlspecialchars($admin["nick_name"]) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($admin["email"]) ?></p>
                </div>
                <form method="POST" class="remove-form">
                    <input type="hidden" name="remove_admin" value="<?= htmlspecialchars($admin["nick_name"]) ?>">
                    <button class="action-btn delete-btn" type="submit">Remove</button>
                </form>
            </div>
        <?php endwhile; ?>
    </section>

    <!-- Sekce pro hosty -->
    <section id="guests">
        <h1>Hosts</h1>
        <?php while ($guest = mysqli_fetch_assoc($guestsResult)): ?>
            <div class="user-card">
                <div class="user-info">
                    <p><strong>Nickname:</strong> <?= htmlspecialchars($guest["nick_name"]) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($guest["email"]) ?></p>
                </div>
                <form method="POST" class="remove-form">
                    <input type="hidden" name="remove_user" value="<?= htmlspecialchars($guest["nick_name"]) ?>">
                    <button class="action-btn delete-btn" type="submit">Remove</button>
                </form>
            </div>
        <?php endwhile; ?>
    </section>

    <!-- Sekce pro přidání nového administrátora -->
    <section id="add-admin">
        <h2>Add new administrator</h2>
        <form method="POST">
            <input type="text" name="new_admin" placeholder="Type user nickname" required>
            <button class="action_btn" type="submit">Add</button>
        </form>
        <?php if (isset($message)): ?>
            <p id="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
    </section>
</main>

<?php include "../footer/footer.php" ?>

<script>
    // Počkej 3 sekundy a pak zprávu skryj
    setTimeout(() => {
        const message = document.getElementById('message');
        if (message) {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0'; // Plynulé zmizení
            setTimeout(() => message.remove(), 500);
        }
    }, 3000); // Zpráva zmizí po 3 sekundách
</script>

</body>
</html>
