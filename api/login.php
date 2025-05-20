<?php
require_once("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['token'] ?? '';

    if ($login === '' || $password === '' || $token === '') { exit(""); }
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $login)) { exit(""); }
    if (!preg_match('/^[a-f0-9]{32}$/i', $token)) { exit(""); }

    
}
