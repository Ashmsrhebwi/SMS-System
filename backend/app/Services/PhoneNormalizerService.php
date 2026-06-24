<?php

namespace App\Services;

class PhoneNormalizerService
{
    public function normalize(string $phone): ?string
    {
        $cleaned = preg_replace('/[\s\-\.\(\)]+/', '', $phone);

        // Already in E.164 format for UK
        if (preg_match('/^\+44\d{10}$/', $cleaned)) {
            return $cleaned;
        }

        // UK local format: 07XXXXXXXXX → +447XXXXXXXXX
        if (preg_match('/^07\d{9}$/', $cleaned)) {
            return '+44' . substr($cleaned, 1);
        }

        // UK without leading zero: 7XXXXXXXXX → +447XXXXXXXXX
        if (preg_match('/^7\d{9}$/', $cleaned)) {
            return '+44' . $cleaned;
        }

        // UK with country code but no plus: 447XXXXXXXXX
        if (preg_match('/^447\d{9}$/', $cleaned)) {
            return '+' . $cleaned;
        }

        // Other E.164 format (non-UK)
        if (preg_match('/^\+\d{8,15}$/', $cleaned)) {
            return $cleaned;
        }

        return null;
    }

    public function isValid(string $phone): bool
    {
        return $this->normalize($phone) !== null;
    }
}
