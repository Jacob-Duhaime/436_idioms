<?php
require 'includes/database-connection.php';
session_start();


header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in']);
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

$data = json_decode(file_get_contents("php://input"), true);

$idiomID = $data['idiomID'] ?? null;
$voteType = $data['voteType'] ?? null;
$voteDate = date('Y-m-d');

if (!isset($idiomID, $voteType) || !in_array($voteType, [0, 1])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Check for existing vote
$stmt = $pdo->prepare("SELECT * FROM Rating WHERE UserID = :userID AND IdiomID = :idiomID");
$stmt->execute(['userID' => $userID, 'idiomID' => $idiomID]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    $voteset = $pdo->prepare("UPDATE Rating SET VoteType = :voteType, VoteDate = :voteDate WHERE UserID = :userID AND IdiomID = :idiomID");
} else {
    $voteset = $pdo->prepare("INSERT INTO Rating (UserID, IdiomID, VoteType, VoteDate) VALUES (:userID, :idiomID, :voteType, :voteDate)");
}

$voteset->execute([
    'userID' => $userID,
    'idiomID' => $idiomID,
    'voteType' => $voteType,
    'voteDate' => $voteDate
]);

// Fetch updated vote counts
$votes_up_stmt = $pdo->prepare("SELECT COUNT(*) FROM Rating WHERE IdiomID = :idiomID AND VoteType = 1");
$votes_up_stmt->execute(['idiomID' => $idiomID]);
$votes_up = $votes_up_stmt->fetchColumn();

$votes_down_stmt = $pdo->prepare("SELECT COUNT(*) FROM Rating WHERE IdiomID = :idiomID AND VoteType = 0");
$votes_down_stmt->execute(['idiomID' => $idiomID]);
$votes_down = $votes_down_stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'votes_up' => (int) $votes_up,
    'votes_down' => (int) $votes_down
]);