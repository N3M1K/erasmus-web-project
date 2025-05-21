<?php
function generateTsid(): string {
    return bin2hex(random_bytes(16));
}
