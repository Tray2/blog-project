<?php

namespace App\libs;

use League\CommonMark\GithubFlavoredMarkdownConverter;

class MarkdownParser
{
    public static function parse($text): string
    {
        $converter = new GithubFlavoredMarkdownConverter();
        try {
            return trim((string)$converter->convert($text));
        } catch (\Exception $e) {
            return 'Parsing of post failed: ' . $e->getMessage();
        }
    }
}