<?php

namespace App\libs;

class Controller
{
    public static function index($post = null): string
    {
        if (empty($post)) {
            return __DIR__ .'/../pages/home.php';
        }
        return __DIR__ . '/../pages/show.php';
    }

    public static function home(): string
    {
        return self::index();
    }

    public static function status(int $int): string
    {
        return __DIR__ . '/../pages/404.php';
    }

    public static function about(): string
    {
        return __DIR__ . '/../pages/about.php';
    }
}