<?php
require 'includes/database-connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    // If the user is not logged in, show an error message
    $_SESSION['flash_error'] = "You must be logged in to edit your account.";
    header('Location: index.php');
    exit;
}

// Process the form when submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the form data
    $currentPassword = $_POST['currentPassword'];
    $newUsername = trim($_POST['newUsername']);
    $newEmail = trim($_POST['newEmail']);
    $newPassword = $_POST['newPassword'];

    // Get the current user information from the session
    $userEmail = $_SESSION['email'];

    // Fetch the current password hash from the database
    $sql = "SELECT Password FROM User WHERE Email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $userEmail]);

    if ($stmt->rowCount() === 1) {
        // Get the stored password hash
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $storedPasswordHash = $row['Password'];

        // Verify the current password
        if (password_verify($currentPassword, $storedPasswordHash)) {
            // Prepare the update query for the fields that are provided

            // Start building the update query and values
            $updateFields = [];
            $updateValues = [];

            // If new username is provided, add to the query
            if (!empty($newUsername)) {
                $updateFields[] = "Username = :newUsername";
                $updateValues['newUsername'] = $newUsername;
            }

            // If new email is provided, add to the query
            if (!empty($newEmail)) {
                $updateFields[] = "Email = :newEmail";
                $updateValues['newEmail'] = $newEmail;
            }

            // If new password is provided, hash it and add to the query
            if (!empty($newPassword)) {
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateFields[] = "Password = :newPassword";
                $updateValues['newPassword'] = $hashedNewPassword;
            }

            // If any fields need to be updated, perform the update
            if (!empty($updateFields)) {
                // Merge the dynamic query parts and execute the update
                $updateSql = "UPDATE User SET " . implode(", ", $updateFields) . " WHERE Email = :email";
                $stmt = $pdo->prepare($updateSql);
                $updateValues['email'] = $userEmail;

                if ($stmt->execute($updateValues)) {
                    // If the update was successful, update the session data if necessary
                    if (isset($updateValues['newUsername'])) {
                        $_SESSION['user'] = $updateValues['newUsername'];
                    }
                    if (isset($updateValues['newEmail'])) {
                        $_SESSION['email'] = $updateValues['newEmail'];
                    }

                    // Success message to be displayed in index.php
                    $_SESSION['flash_success'] = "Your account has been updated successfully!";
                    header('Location: index.php');
                    exit;
                } else {
                    // If the update failed, show an error message
                    $_SESSION['flash_error'] = "There was an error updating your account. Please try again.";
                    header('Location: index.php');
                    exit;
                }
            }
        } else {
            // If the current password does not match, show an error message
            $_SESSION['flash_error'] = "The current password is incorrect.";
            header('Location: index.php');
            exit;
        }
    } else {
        // If no user is found (which shouldn't happen if the session is valid)
        $_SESSION['flash_error'] = "User not found.";
        header('Location: index.php');
        exit;
    }
    header('Location: index.php');
    exit;
}
?>
