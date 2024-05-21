<?php

namespace App\libs;

class FrontMatter
{
    public static function parse($matter): array
    {
        $parts = (explode("\n", $matter));
        $frontMatter = [];
        foreach ($parts as $part) {
            if ($part === '---') continue;
            $key = substr($part, 0, strpos($part, ':'));
            $value = htmlspecialchars(trim(substr($part, strpos($part, ':') + 1)));

            $frontMatter[$key] = $value;
        }
        return $frontMatter;
    }
}