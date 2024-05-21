<?php

namespace App\libs;

class Route
{
    public static function dispatch(string $url =  ''): string
    {
        if ($url === '') {
            $url = $_SERVER['REQUEST_URI'];
        }

        $page = '';
        $route = explode('/', trim($url, '/'));
        match($route[0]) {
            '' => $page = Controller::home(),
            'posts' => $page = Controller::index($route[1] ?? null),
            'about' =>$page = Controller::about(),
            default => $page = Controller::status(404),
        };

        return $page;
    }
}