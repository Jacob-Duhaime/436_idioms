<?php
session_start();
session_unset(); // Optional: Clear session variables
session_destroy();
header('Location: index.php');
exit;
?>

