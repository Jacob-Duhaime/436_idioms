<?php
session_start();
require 'includes/database-connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $sql = "SELECT * FROM User WHERE Email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['Password'])) {
        $_SESSION['user'] = $user['Username'];
        $_SESSION['email'] = $user['Email'];
        $_SESSION['cakeday'] = $user['CakeDay'];

        header('Location: index.php');
        exit;
    } else {
        // Set flash message
        $_SESSION['flash_error'] = 'Incorrect email or password. Please try again.';
        header('Location: index.php');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
