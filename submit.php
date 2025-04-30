<?php
require 'includes/database-connection.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

// Retrieve userID from the session
$userEmail = $_SESSION['email'];
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
    $translationLang = trim($_POST['translation_lang']);

    // Ensure at least one field is filled out
    if (empty($idiom) && empty($meaning) && empty($example) && empty($origin) && empty($translation)) {
        die("At least one field must be filled out.");
    }

    // translation language list
    $validLanguages = [
        'Spanish', 'Chinese', 'French', 'Italian', 'German',
        'Japanese', 'Russian', 'Korean', 'Portuguese',
        'Polish', 'Latin', 'Pig Latin'
    ];
    
    // If translation is provided, a valid language must be selected
    if (!empty($translation)) {
        if (empty($translationLang)) {
            die("Please select a language for the translation.");
        }
        if (!in_array($translationLang, $validLanguages)) {
            die("Invalid translation language.");
        }
    }

    try {
        // Begin transaction
        $pdo->beginTransaction();

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

        // Get the ContID of the new contribution
        $contID = $pdo->lastInsertId();

        // Step 1: Insert the origin only if it doesn't exist
        if (!empty($origin)) {
            $stmt = $pdo->prepare("SELECT OriginID FROM Origin WHERE text = :origin");
            $stmt->execute(['origin' => $origin]);
            $originRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($originRow) {
                $originID = $originRow['OriginID'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO Origin (text) VALUES (:origin)");
                $stmt->execute(['origin' => $origin]);
                $originID = $pdo->lastInsertId();
            }
        } else {
            $originID = null;
        }

        // Step 2: Insert idiom only if it doesn't exist
        $stmt = $pdo->prepare("SELECT IdiomID FROM Idiom WHERE text = :idiom");
        $stmt->execute(['idiom' => $idiom]);
        $idiomRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($idiomRow) {
            $idiomID = $idiomRow['IdiomID'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO Idiom (text, OriginID, ContID) VALUES (:idiom, :originID, :contID)");
            $stmt->execute([
                'idiom' => $idiom,
                'originID' => $originID,
                'contID' => $contID
            ]);
            $idiomID = $pdo->lastInsertId();
        }

        // Step 3: Insert Meaning
        if (!empty($meaning)) {
            $stmt = $pdo->prepare("SELECT 1 FROM Meaning WHERE IdiomID = :idiomID AND text = :meaning");
            $stmt->execute(['idiomID' => $idiomID, 'meaning' => $meaning]);
            $meaningExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
            if (!$meaningExists) {
                $stmt = $pdo->prepare("INSERT INTO Meaning (IdiomID, text, ContID) VALUES (:idiomID, :meaning, :contID)");
                $stmt->execute([
                    'idiomID' => $idiomID,
                    'meaning' => $meaning,
                    'contID' => $contID
                ]);
            }
        }        

        // Step 4: Insert Example
        if (!empty($example)) {
            $stmt = $pdo->prepare("SELECT 1 FROM Example WHERE IdiomID = :idiomID AND text = :example");
            $stmt->execute(['idiomID' => $idiomID, 'example' => $example]);
            $exampleExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
            if (!$exampleExists) {
                $stmt = $pdo->prepare("INSERT INTO Example (IdiomID, text, ContID) VALUES (:idiomID, :example, :contID)");
                $stmt->execute([
                    'idiomID' => $idiomID,
                    'example' => $example,
                    'contID' => $contID
                ]);
            }
        }        

        // Step 5: Insert Translation
        if (!empty($translation)) {
            // Get LanguageID
            $stmt = $pdo->prepare("SELECT LanguageID FROM Language WHERE FLanguage = :language");
            $stmt->execute(['language' => $translationLang]);
            $languageRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
            if (!$languageRow) {
                throw new Exception("Language '$translationLang' does not exist.");
            }
        
            $languageID = $languageRow['LanguageID'];
        
            // Check if translation already exists
            $stmt = $pdo->prepare("SELECT 1 FROM Translation WHERE IdiomID = :idiomID AND LanguageID = :languageID AND text = :translation");
            $stmt->execute([
                'idiomID' => $idiomID,
                'languageID' => $languageID,
                'translation' => $translation
            ]);
            $translationExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
            if (!$translationExists) {
                $stmt = $pdo->prepare("INSERT INTO Translation (IdiomID, LanguageID, text, ContID) VALUES (:idiomID, :languageID, :translation, :contID)");
                $stmt->execute([
                    'idiomID' => $idiomID,
                    'languageID' => $languageID,
                    'translation' => $translation,
                    'contID' => $contID
                ]);
            }
        }        

        // Commit transaction
        $pdo->commit();

        // Set success flag
        $success = true;
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit an Idiom</title>
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
                <textarea id="meaning" name="meaning" rows="3"></textarea>
            </div>

            <div class="form-section">
                <label for="example">Example Sentence:</label>
                <textarea id="example" name="example" rows="3"></textarea>
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
                <option value="French">French</option>
                <option value="Italian">Italian</option>
                <option value="German">German</option>
                <option value="Japanese">Japanese</option>
                <option value="Russian">Russian</option>
                <option value="Korean">Korean</option>
                <option value="Portuguese">Portuguese</option>
                <option value="Polish">Polish</option>
                <option value="Latin">Latin</option>
                <option value="Pig Latin">Pig Latin</option>
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
