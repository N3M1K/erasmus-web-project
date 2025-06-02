<?php

function logOut($route) {
    session_start();
    session_unset();
    session_destroy();

    // Pokud používáš vlastní TSID cookie
    if (isset($_COOKIE['tsid'])) {
        setcookie('tsid', '', time() - 3600, "/", "", true, true); // bezpečné, HTTP only
        setcookie('cmp', '', time() - 3600, "/", "", true, true);
    }
    exit;
}