<?php

use App\libs\MarkdownParser;

it('parses markdown', function () {
    $result = MarkdownParser::parse('# This is a header');
    expect($result)->toBe('<h1>This is a header</h1>');
});
