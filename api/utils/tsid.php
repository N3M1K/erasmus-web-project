<?php
function generateTsid(): string {
    return generateRandomHex(32);
}
function generateRandomHex(int $length = 8): string {
    $characters = 'abcdef0123456789';
    $result = '';

    for ($i = 0; $i < $length; $i++) {
        $randomIndex = random_int(0, strlen($characters) - 1);
        $result .= $characters[$randomIndex];
    }

    return $result;
}
