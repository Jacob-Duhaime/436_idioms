<?php 

require 'includes/database-connection.php';
session_start(); 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Idioms</title>
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
        <h2>Search for an Idiom</h2>
        <form method="get" action="search.php">
            <div class="form-section">
                <label for="query">Idiom or Translation:</label>
                <input type="text" id="query" name="query" value="<?= htmlspecialchars($_GET['query'] ?? '') ?>" required>
            </div>
            <button type="submit">Search</button>
        </form>

        <?php
        require_once 'includes/database-connection.php';

        if (isset($_GET['query']) && trim($_GET['query']) !== '') {
            $query = trim($_GET['query']);

            $sql = "
                SELECT DISTINCT
                    i.IdiomID,
                    i.Text AS Idiom,
                    (SELECT m.Text FROM Meaning m WHERE m.IdiomID = i.IdiomID LIMIT 1) AS Meaning,
                    (SELECT e.Text FROM Example e WHERE e.IdiomID = i.IdiomID LIMIT 1) AS Example,
                    CASE 
                        WHEN t.Text LIKE :q1 THEN t.Text
                        ELSE NULL
                    END AS Translation
                FROM Idiom i
                LEFT JOIN Translation t ON i.IdiomID = t.IdiomID 
                WHERE i.Text LIKE :q2 OR t.Text LIKE :q3;
                ";

            $results = pdo($pdo, $sql, [
                'q1' => '%' . $query . '%',
                'q2' => '%' . $query . '%',
                'q3' => '%' . $query . '%'
            ])->fetchAll();

            echo "<h3>Search Results for '<em>" . htmlspecialchars($query) . "</em>':</h3>";

            if ($results) {
                foreach ($results as $row) {
                    echo "<div class='search-result'>";
                    echo "<a href='idiom.php?id=" . htmlspecialchars($row['IdiomID']) . "'>" . htmlspecialchars($row['Idiom']) . "</a>";
                    echo "<div>" . htmlspecialchars($row['Meaning'] ?? 'No meaning available.') . "</div>";
                    echo "<em>" . htmlspecialchars($row['Example'] ?? 'No example available.') . "</em>";
                    echo "<div>" . htmlspecialchars($row['Translation'] ?? '') . "</div>";
                    echo "</div>";
                }
            } else {
                echo "<p>No results found.</p>";
            }
        }
        ?>

    </main>

    <footer>
        <p>&copy; CSC 436 Idiom Database</p>
    </footer>
</body>
</html>
