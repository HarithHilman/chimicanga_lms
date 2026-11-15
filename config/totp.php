<?php
// ============================================================================
// RFC 6238 TIME-BASED ONE-TIME PASSWORD (TOTP) ENGINE (Zero Dependencies)
// ============================================================================

function generateTotpSecret(int $length = 16): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $alphabet[random_int(0, 31)];
    }
    return $secret;
}

function base32DecodeTotp(string $secret): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret));
    $binaryString = '';

    for ($i = 0; $i < strlen($secret); $i++) {
        $val = strpos($alphabet, $secret[$i]);
        if ($val !== false) {
            $binaryString .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }
    }

    $bytes = '';
    foreach (str_split($binaryString, 8) as $byte) {
        if (strlen($byte) === 8) {
            $bytes .= chr(bindec($byte));
        }
    }
    return $bytes;
}

function getTotpCodeAtSlice(string $secret, int $timeSlice): string {
    $key = base32DecodeTotp($secret);
    $timeBytes = pack('N*', 0) . pack('N*', $timeSlice);
    $hash = hash_hmac('sha1', $timeBytes, $key, true);
    $offset = ord($hash[19]) & 0x0F;

    $binary =
        ((ord($hash[$offset]) & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) << 8) |
        (ord($hash[$offset + 3]) & 0xFF);

    return str_pad((string)($binary % 1000000), 6, '0', STR_PAD_LEFT);
}

function verifyTotpCode(string $secret, string $userCode, int $window = 1): bool {
    if (!preg_match('/^\d{6}$/', $userCode) || $secret === '') {
        return false;
    }

    $currentSlice = (int) floor(time() / 30);
    for ($i = -$window; $i <= $window; $i++) {
        $expected = getTotpCodeAtSlice($secret, $currentSlice + $i);
        if (hash_equals($expected, $userCode)) {
            return true;
        }
    }
    return false;
}

function getTotpQrUrl(string $issuer, string $accountEmail, string $secret): string {
    $label = rawurlencode($issuer . ':' . $accountEmail);
    $otpauth = "otpauth://totp/{$label}?secret={$secret}&issuer=" . rawurlencode($issuer) . "&algorithm=SHA1&digits=6&period=30";
    return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($otpauth);
}