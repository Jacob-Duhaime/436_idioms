<?php
require 'includes/database-connection.php';
session_start(); 

$idiom_id = $_GET['id'];

function get_idiom_by_id(PDO $pdo, string $id) {
    // Get main idiom info
    $sql = "
        SELECT 
            i.IdiomID,
            i.Text AS Idiom,
            i.ContID,
            m.Text AS Meaning,
            o.Text AS Origin
        FROM Idiom i
        LEFT JOIN Meaning m ON i.IdiomID = m.IdiomID
        LEFT JOIN Origin o ON i.OriginID = o.OriginID
        WHERE i.IdiomID = :id;
    ";
    $idiom = pdo($pdo, $sql, ['id' => $id])->fetch();
    if (!$idiom) return null;

    // get contributor
    $contributor_sql = "
        SELECT 
            u.Username
        FROM User u
        LEFT JOIN Contribution c ON u.UserID = c.UserID
        WHERE c.ContID = :id;
    ";
    $contributor = pdo($pdo, $contributor_sql, ['id' => $idiom['ContID']])->fetch();
    $idiom['Contributors'] = $contributor;

    // Get vote counts
    $votes_up_stmt = $pdo->prepare("SELECT COUNT(*) FROM Rating WHERE IdiomID = :idiomID AND VoteType = 1");
    $votes_up_stmt->execute(['idiomID' => $id]);
    $votes_up = $votes_up_stmt->fetchColumn();
    
    $votes_down_stmt = $pdo->prepare("SELECT COUNT(*) FROM Rating WHERE IdiomID = :idiomID AND VoteType = 0");
    $votes_down_stmt->execute(['idiomID' => $id]);
    $votes_down = $votes_down_stmt->fetchColumn();
    
    $idiom['VotesUp'] = $votes_up;
    $idiom['VotesDown'] = $votes_down;

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

    return $idiom;
}


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid idiom ID.");
}

$idiom = get_idiom_by_id($pdo, $idiom_id);
if (!$idiom) {
    die("Idiom not found.");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($idiom['Idiom']) ?> - Idiom Index</title>
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
        <div class="idiom-card">
            <h2><?= $idiom['Idiom'] ?></h2>

            <div class="vote-section">
                <button class="upvote-btn" data-idiom-id="<?= $idiom['IdiomID'] ?>">👍</button>
                <span class="upvote-count" data-idiom-id="<?= $idiom['IdiomID'] ?>"><?= $idiom['VotesUp']?></span>

                <button class="downvote-btn" data-idiom-id="<?= $idiom['IdiomID'] ?>">👎</button>
                <span class="downvote-count" data-idiom-id="<?= $idiom['IdiomID'] ?>"><?= $idiom['VotesDown']?></span>
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
            
            <div class="idiom-section">
                <h3>Contributor</h3>
                <div class="idiom-block">
                    <?php if (!empty($idiom['Contributors'])): ?>
                        <?= htmlspecialchars(implode(', ', $idiom['Contributors'])); ?>
                    <?php else: ?>
                        Anon.
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <footer>
        <p>&copy; CSC 436 Idiom Database</p>
    </footer>
</body>
</html>
