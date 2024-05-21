<?php

use App\libs\BlogIndexLoader;

it('loads the posts in the provided directory', function () {
    $stubDirectory = dirname(__FILE__, 2) . '/stubs/posts/';
    $result = BlogIndexLoader::load($stubDirectory);

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(1)
        ->and($result)->toMatchArray(
            [
                [
                    "" => "",
                    "title" => "Post Stub",
                    "slug" => "post-stub",
                    "created_at" => "2021-11-13",
                    "updated_at" => "2021-11-13",
                    "published_at" => "2021-11-13",
                    "author" => "Tray2",
                    "summary" => "This is the post stub.",
                    "image" => "poststub.png",
                ],
            ]
        );
    });
