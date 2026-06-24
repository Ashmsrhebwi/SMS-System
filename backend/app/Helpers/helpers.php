<?php

if (!function_exists('mask_phone')) {
    function mask_phone(string $phone): string
    {
        if (strlen($phone) <= 5) {
            return $phone;
        }
        $first = substr($phone, 0, 3);
        $last = substr($phone, -2);
        $middle = str_repeat('*', strlen($phone) - 5);
        return $first . $middle . $last;
    }
}

if (!function_exists('display_phone')) {
    function display_phone(string $phone): string
    {
        if (auth()->check() && auth()->user()->isAdmin()) {
            return $phone;
        }
        return mask_phone($phone);
    }
}
