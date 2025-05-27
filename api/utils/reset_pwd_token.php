<?php

    function generateResetToken(string $companyId): string {
        $companyEncoded = base64_encode($companyId);
        $randomBytes = bin2hex(random_bytes(32)); // 32 bytes -> 64 hex characters
        $timestamp = base64_encode(date('Y-m-d H:i:s'));

        return $companyEncoded . '.' . $randomBytes . '.' . $timestamp;
    }
    echo generateResetToken("alza");

?>