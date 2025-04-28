<?php
require 'includes/database-connection.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

// Retrieve userID from the session
$userEmail = $_SESSION['email']; // Assuming the session stores the user's email
$userID = null;

// Get the user ID from the database based on the email stored in the session
if ($userEmail) {
    $sql = "SELECT UserID FROM User WHERE Email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $userEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userID = $user['UserID'];
    }
}

// If no userID is found, redirect to the homepage
if (!$userID) {
    header('Location: index.php');
    exit;
}

// Initialize success flag
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capture form data
    $idiom = trim($_POST['idiom']);
    $meaning = trim($_POST['meaning']);
    $example = trim($_POST['example']);
    $origin = trim($_POST['origin']);
    $translation = trim($_POST['translation']);
    $translationLang = $_POST['translation_lang'];

    // Ensure at least one field is filled out (current idiom entry)
    if (empty($idiom) && empty($meaning) && empty($example) && empty($origin) && empty($translation)) {
        die("At least one field must be filled out.");
    }

    // Validate translation language (only Spanish and Chinese allowed)
    if ($translationLang && !in_array($translationLang, ['Spanish', 'Chinese'])) {
        die("Invalid translation language. Only Spanish and Chinese are allowed.");
    }

    // Calculate ContType based on filled fields
    $contType = 0;
    if (!empty($idiom)) $contType |= 1;  // Idiom
    if (!empty($meaning)) $contType |= 2;  // Meaning
    if (!empty($example)) $contType |= 4;  // Example
    if (!empty($origin)) $contType |= 8;  // Origin
    if (!empty($translation)) $contType |= 16;  // Translation

    // Insert contribution into the Contribution table
    $insertSql = "INSERT INTO Contribution (ContType, UserID) VALUES (:contType, :userID)";
    $stmt = $pdo->prepare($insertSql);
    $stmt->execute([
        'contType' => $contType,
        'userID' => $userID
    ]);

    // Get the ContID of the new contribution (auto-incremented)
    $contID = $pdo->lastInsertId();

    // Insert the idiom and associated data into the appropriate tables (Idiom, Meaning, Example, etc.)
    if (!empty($idiom)) {
        $insertIdiomSql = "INSERT INTO Idiom (IdiomName) VALUES (:idiom)";
        $stmt = $pdo->prepare($insertIdiomSql);
        $stmt->execute(['idiom' => $idiom]);
        $idiomID = $pdo->lastInsertId();
    }

    // Insert Meaning
    if (!empty($meaning)) {
        $insertMeaningSql = "INSERT INTO Meaning (IdiomID, MeaningText) VALUES (:idiomID, :meaning)";
        $stmt = $pdo->prepare($insertMeaningSql);
        $stmt->execute(['idiomID' => $idiomID ?? null, 'meaning' => $meaning]);
    }

    // Insert Example
    if (!empty($example)) {
        $insertExampleSql = "INSERT INTO Example (IdiomID, ExampleText) VALUES (:idiomID, :example)";
        $stmt = $pdo->prepare($insertExampleSql);
        $stmt->execute(['idiomID' => $idiomID ?? null, 'example' => $example]);
    }

    // Insert Origin
    if (!empty($origin)) {
        $insertOriginSql = "INSERT INTO Origin (IdiomID, OriginText) VALUES (:idiomID, :origin)";
        $stmt = $pdo->prepare($insertOriginSql);
        $stmt->execute(['idiomID' => $idiomID ?? null, 'origin' => $origin]);
    }

    // Insert Translation if selected
    if (!empty($translation)) {
        $insertTranslationSql = "INSERT INTO Translation (IdiomID, TranslationText, Language) VALUES (:idiomID, :translation, :language)";
        $stmt = $pdo->prepare($insertTranslationSql);
        $stmt->execute(['idiomID' => $idiomID ?? null, 'translation' => $translation, 'language' => $translationLang]);
    }

    // Set success flag
    $success = true;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit an Idiom</title>
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
        <h2>Submit a New Idiom</h2>
        <?php if ($success): ?>
            <div class="overlay" id="successPopup">
                <div class="popup">
                    <h3>Thank you!</h3>
                    <p>Your idiom has been submitted successfully.</p>
                    <button onclick="document.getElementById('successPopup').style.display='none'">Close</button>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" action="submit.php">
            <div class="form-section">
                <label for="idiom">Idiom:</label>
                <input type="text" id="idiom" name="idiom" required>
            </div>

            <div class="form-section">
                <label for="meaning">Meaning:</label>
                <textarea id="meaning" name="meaning" rows="3" required></textarea>
            </div>

            <div class="form-section">
                <label for="example">Example Sentence:</label>
                <textarea id="example" name="example" rows="3" required></textarea>
            </div>

            <div class="form-section">
                <label for="origin">Origin:</label>
                <textarea id="origin" name="origin" rows="2"></textarea>
            </div>

            <div class="form-section">
                <label for="translation">Translation:</label>
                <input type="text" id="translation" name="translation">
            </div>

            <div class="form-section">
                <label for="translation_lang">Translation Language:</label>
                <select id="translation_lang" name="translation_lang">
                    <option value="">--Select Language--</option>
                    <option value="Chinese">Chinese</option>
                    <option value="Spanish">Spanish</option>
                </select>
            </div>

            <div class="form-section">
                <button type="submit">Submit Idiom</button>
            </div>
        </form>
    </main>

    <footer>
        <p>&copy; CSC 436 Idiom Database</p>
    </footer>
</body>
</html>
