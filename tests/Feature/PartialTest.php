<?php

use App\libs\Route;
use GuzzleHttp\Exception\GuzzleException;

it('it requires the given partial', function () {
    $httpClient = new GuzzleHttp\Client();
    try {
        $response = $httpClient->request('GET', 'http://blog.test');
        expect($response->getStatusCode())
            ->toBe(200)
            ->and($response->getBody()
                ->getContents())
                ->toContain('All rights reserved');
    } catch (GuzzleException $e) {
        $this->fail($e->getMessage());
    }
});

it('it requires the given route', function () {
    ob_start();
    Route::dispatch('/');
    $e = ob_get_flush();
    ob_end_clean();

    expect($e)->toEqual('');
});