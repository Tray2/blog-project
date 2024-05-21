<?php

namespace App\libs;
class BlogIndexLoader
{
    public static function load(string $path = __DIR__ . '/../../posts'): array
    {
        $frontMatter = [];

        foreach(scandir($path) as $file) {
            if ($file !== '.' && $file !== '..') {
                $post = file_get_contents($path . '/' .  $file);
                $parts = preg_split('/^---$/m' , $post, -1, PREG_SPLIT_NO_EMPTY);
                $frontMatter[] = FrontMatter::parse($parts[0]);
            }
        }

        $publishedAt = array_column($frontMatter, 'published_at');
        array_multisort($publishedAt, SORT_DESC, $frontMatter);

        return $frontMatter;
    }
}