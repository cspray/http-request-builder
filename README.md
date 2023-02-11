# Http Request Builder

A library to fluently create [amphp/http-client](https://github.com/amphp/http-client) Request objects.

## Installation

```
composer require cspray/http-request-builder
```

## Usage

This library has 1 focused purpose; easily create Requests.

```php
<?php declare(strict_types=1);

use Cspray\HttpRequestBuilder\RequestBuilder;

$request = RequestBuilder::withJsonBody([
    'three words' => 'enthusiastic,positivity,clashing'
])->post('https://api.example.com');

\Amp\Http\Client\HttpClientBuilder::buildDefault()->request($request);
```