<?php
require 'includes/database-connection.php';

$idiom_id = $_GET['id'];

function get_idiom_by_id(PDO $pdo, string $id) {
    // Get main idiom info
    $sql = "
        SELECT 
            i.IdiomID,
            i.Text AS Idiom,
            m.Text AS Meaning,
            o.Text AS Origin
        FROM Idiom i
        LEFT JOIN Meaning m ON i.IdiomID = m.IdiomID
        LEFT JOIN Origin o ON i.OriginID = o.OriginID
        WHERE i.IdiomID = :id;
    ";
    $idiom = pdo($pdo, $sql, ['id' => $id])->fetch();

    if (!$idiom) return null;

    // Get examples
    $example_sql = "SELECT Text FROM Example WHERE IdiomID = :id;";
    $examples = pdo($pdo, $example_sql, ['id' => $id])->fetchAll(PDO::FETCH_COLUMN);
    $idiom['Examples'] = $examples;

    // Get translations
    $translation_sql = "
        SELECT 
            t.Text AS Text, 
            l.FLanguage AS Language
        FROM Translation t
        LEFT JOIN Language l on t.LanguageID = l.LanguageID
        WHERE IdiomID = :id;
     ";
    $translations = pdo($pdo, $translation_sql, ['id' => $id])->fetchAll(PDO::FETCH_ASSOC);
    $idiom['Translations'] = $translations;

    // Get contributors using multiple joins
    $contributor_sql = "SELECT User.Username FROM User WHERE UserID = :id;";
    $contributors = pdo($pdo, $contributor_sql, ['id' => $id])->fetchAll();
    $idiom['Contributors'] = $contributors;

    return $idiom;
}


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid idiom ID.");
}

$idiom = get_idiom_by_id($pdo, $idiom_id);
if (!$idiom) {
    die("Idiom not found.");
}

// Placeholder idiom content
// $idiom = [
//     'idiom' => 'Break the ice',
//     'origin' => 'Derived from ships breaking the ice in frozen waters to allow passage.'
// ];

// $meanings = [
//     'To start a conversation in a social setting.',
//     'To ease tension in a new or awkward situation.'
// ];

// $examples = [
//     'She told a funny joke to break the ice at the party.',
//     'Games helped break the ice at orientation.'
// ];

// $translations = [
//     ['translation' => '打破冷场', 'language' => 'Mandarin Chinese'],
//     ['translation' => 'rompre la glace', 'language' => 'French']
// ];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($idiom['Text']) ?> - Idiom Dictionary</title>
    <link rel="stylesheet" href="css/style.css">
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
        <div class="idiom-card">
            <h2><?= $idiom['Idiom'] ?></h2>
            <div class="vote-section">
                <button type="button" disabled>👍 Upvote</button>
                <span><?= $idiom['votes_up'] ?? 0 ?></span>
                <button type="button" disabled>👎 Downvote</button>
                <span><?= $idiom['votes_down'] ?? 0 ?></span>
            </div>

            <div class="idiom-section">
                <h3>Meaning</h3>
                <div class="idiom-block"><?= htmlspecialchars($idiom['Meaning']) ?></div>
                
            </div>

            <div class="idiom-section">
                <h3>Examples</h3>
                <?php foreach ($idiom['Examples'] as $example): ?>
                    <div class="idiom-block"><em><?= htmlspecialchars($example) ?></em></div>
                <?php endforeach; ?>
            </div>

            <div class="idiom-section">
                <h3>Origin</h3>
                <div class="idiom-block"><?= htmlspecialchars($idiom['Origin']) ?></div>
            </div>

            <div class="idiom-section">
                <h3>Translations</h3>
                <?php foreach ($idiom['Translations'] as $t): ?>
                    <div class="idiom-block"><?= htmlspecialchars($t['Text']) ?> <small>(<?= htmlspecialchars($t['Language']) ?>)</small></div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; CSC 436 Idiom Database</p>
    </footer>
</body>
</html>
