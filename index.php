<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

header('Content-Type: text/html; charset=UTF-8');
readfile(__DIR__ . '/index.html');






