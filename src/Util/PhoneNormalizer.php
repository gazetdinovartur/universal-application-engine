<?php

namespace App\Util;

/**
 * Портировано из legacy/wordpress/forminator-payment.js и google-apps-script/Code.gs.
 */
final class PhoneNormalizer
{
    public static function toE164(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '8')) {
            $digits = '7'.substr($digits, 1);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '7'.$digits;
        }

        if (strlen($digits) !== 11) {
            return null;
        }

        return '+'.$digits;
    }

    public static function toDigits(?string $phone): string
    {
        if (!$phone) {
            return '';
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            $digits = '7'.substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            $digits = '7'.$digits;
        }

        return $digits;
    }
}
