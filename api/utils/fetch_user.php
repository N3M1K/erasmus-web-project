<?php
function fetchPerson($tsid) {
    if (isset($_COOKIE['tsid']) && isset($_COOKIE['cmp'])) {
        $cmp = $_COOKIE['cmp'];
        $tsid = $_COOKIE['tsid'];

        try {
            $pdo = resolveDb($cmp);
            $stmt = $pdo->prepare('SELECT * FROM users WHERE tsid = ?');
            $stmt->execute([$tsid]);
            $users = $stmt->fetchAll();
        }
    }
}