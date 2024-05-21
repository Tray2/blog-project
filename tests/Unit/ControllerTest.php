<?php

use App\libs\Controller;

it('returns the post index page when home is used', function () {
    $result = Controller::home();
    expect($result)->toContain('home.php');
});
