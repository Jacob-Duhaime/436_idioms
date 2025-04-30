<?php 

require 'includes/database-connection.php';
session_start(); 

function get_random_idioms(PDO $pdo) {
    $sql = "
        SELECT 
            i.IdiomID AS id,
            i.Text AS Idiom,
            (
                SELECT m.Text 
                FROM Meaning m 
                WHERE m.IdiomID = i.IdiomID 
                LIMIT 1
            ) AS Meaning
        FROM Idiom i
        ORDER BY RAND()
        LIMIT 5;
    ";

    $idioms = pdo($pdo, $sql)->fetchAll(PDO::FETCH_ASSOC);
    return $idioms;
}

$idioms = get_random_idioms($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Idiom Index</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/main.js" defer></script>
</head>
<body>
    <header>
        <div>
            <h1>Idiom Index</h1>
            <nav>
                <a href="index.php">Home</a>
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="submit.php">Submit Idiom</a>
                <?php endif; ?>
                <a href="search.php">Search</a>
            </nav>
        </div>
        <div class="profile-container" id="profileIcon">
            <div class="profile-icon"></div>
            <div class="snackbar" id="profileMenu">
                <?php if (isset($_SESSION['flash_error'])): ?>
                    <h4 class="error-message"><?= htmlspecialchars($_SESSION['flash_error']) ?></h4>
                    <?php unset($_SESSION['flash_error']); ?>
                <?php endif; ?>

                <?php if (!isset($_SESSION['user'])): ?>
                    <a href="#" onclick="showForm('signup')">Sign Up</a>
                    <a href="#" onclick="showForm('login')">Log In</a>
                <?php else: ?>
                    <a href="#" onclick="showForm('edit')">Edit Account</a>
                    <a href="logout.php">Log Out</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <h2>Welcome to the Idiom Dictionary</h2>
        <p>Browse, submit, and search idioms from around the world.</p>
        
        <h2>Random Idioms</h2>
        <?php if (!empty($idioms)): ?>
            <ul class="idiom-list">
                <?php foreach ($idioms as $idiom): ?>
                    <li>
                        <a href="idiom.php?id=<?= $idiom['id'] ?>">
                            <strong><?= $idiom['Idiom'] ?></strong>
                        </a>
                        <p><?= $idiom['Meaning'] ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No idioms found.</p>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['user'])): ?>
            <p>Logged in as: <strong><?= htmlspecialchars($_SESSION['user']) ?></strong></p>
        <?php else: ?>
            <p>Not logged in :)</p>
            <?php endif; ?>
    </main>

    <footer>
        <p>&copy; CSC 436 Idiom Database</p>
    </footer>
</body>
</html>