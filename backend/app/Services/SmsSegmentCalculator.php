<?php

namespace App\Services;

class SmsSegmentCalculator
{
    // GSM-7 basic character set (all characters that fit in standard GSM encoding)
    private const GSM7_BASIC = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ\x1BÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    // GSM-7 extended characters (count as 2 chars each in segment calculation)
    private const GSM7_EXTENDED = ['[', ']', '{', '}', '\\', '^', '|', '~', '€', "\f"];

    public static function isGsm7(string $body): bool
    {
        $len = mb_strlen($body, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($body, $i, 1, 'UTF-8');
            if (mb_strpos(self::GSM7_BASIC, $char) === false && !in_array($char, self::GSM7_EXTENDED, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Calculate SMS segment count.
     * GSM-7:  single=160 chars, multipart=153 chars per segment
     * Unicode: single=70 chars,  multipart=67 chars per segment
     */
    public static function segments(string $body): int
    {
        $len = mb_strlen($body, 'UTF-8');
        if ($len === 0) return 0;

        if (self::isGsm7($body)) {
            // Count extended chars as 2 characters
            $extCount = 0;
            foreach (self::GSM7_EXTENDED as $ext) {
                $extCount += substr_count($body, $ext);
            }
            $effectiveLen = $len + $extCount;

            return $effectiveLen <= 160 ? 1 : (int) ceil($effectiveLen / 153);
        }

        // Unicode (UCS-2): 70 chars single, 67 chars multipart
        return $len <= 70 ? 1 : (int) ceil($len / 67);
    }

    public static function cost(string $body): float
    {
        $segments = self::segments($body);
        $rate     = (float) config('sms.cost_per_segment', 0.0079);
        return round($segments * $rate, 4);
    }

    public static function formatCost(float $cost): string
    {
        $symbol = config('sms.currency_symbol', '$');
        return $symbol . number_format($cost, 4);
    }

    public static function formatCostShort(float $cost): string
    {
        $symbol = config('sms.currency_symbol', '$');
        return $symbol . number_format($cost, 2);
    }
}
