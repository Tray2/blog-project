<?php

namespace App\libs;

class Partial
{
    public static function load(string $partial, string $path = __DIR__ . '/../pages/partials/'): void
    {
        if (file_exists($path . $partial . '.php')) {
            require $path . $partial . '.php';
        }
    }
}