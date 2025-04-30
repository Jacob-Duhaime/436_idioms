<?php
require 'includes/database-connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $name = trim($_POST['name']);
    $password = $_POST['password'];
    $cakeDay = date('Y-m-d'); // today's date

    // Check if email already exists
    $checkSql = "SELECT * FROM User WHERE Email = :email";
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute(['email' => $email]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['flash_error'] = 'An account with that email already exists. Please log in instead.';
        header('Location: index.php');
        exit;
    }

    // Hash password and insert new user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $insertSql = "INSERT INTO User (Username, Email, Password, CakeDay)
                  VALUES (:name, :email, :password, :cakeDay)";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
        'name' => $name,
        'email' => $email,
        'password' => $hashedPassword,
        'cakeDay' => $cakeDay
    ]);

    // Start session and store user info
    $_SESSION['user'] = $name;
    $_SESSION['email'] = $email;
    $_SESSION['cakeday'] = $cakeDay; // (fix typo: was 'cake_day')

    header("Location: index.php");
    exit;
} else {
    header('Location: index.php');
    exit;
}