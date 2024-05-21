<?php

use App\libs\FrontMatter;

it('parses yaml front matter to an array', function () {
    $frontMatter = <<<EOD
    ---
    title: This is the title
    author: Some Author
    ---
    EOD;

    $result = FrontMatter::parse($frontMatter);

    expect($result)->toBe([
        'title' => 'This is the title',
        'author' => 'Some Author'
    ]);

});
