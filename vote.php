<?php
require 'includes/database-connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idiom_id'], $_POST['vote'])) {
    $id = (int)$_POST['idiom_id'];
    $vote = $_POST['vote'] === 'up' ? 1 : -1;

    $sql = "UPDATE idioms SET votes = votes + :vote WHERE id = :id";
    pdo($pdo, $sql, ['vote' => $vote, 'id' => $id]);

    header("Location: idiom.php?id=" . $id);
    exit;
}
