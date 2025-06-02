<?php
function fetchConsultant($username) {
    $pdo = resolveDb("consultants");
    $stmt = $pdo->prepare(
        'SELECT
            *
         FROM users
         WHERE username = ?'
    );
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_OBJ);
}
