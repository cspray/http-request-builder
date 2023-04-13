<?php declare(strict_types=1);

namespace Cspray\HttpRequestBuilder;

use Amp\Http\Client\Body\FormBody;
use Amp\Http\Client\Body\JsonBody;
use Amp\Http\Client\Form;
use Amp\Http\Client\Request;
use Psr\Http\Message\UriInterface;

interface FluentRequestBuilder {

    public function setHeaders(array $headers) : self;

    public function addHeaders(array $headers) : self;

    public function setHeader(string $header, string|array $value) : self;

    public function withJsonBody(array $body) : self;

    public function withFormBody(Form $body) : self;

    public function withBody(string $body, string $contentType = 'text/plain') : self;

    public function connect(string|UriInterface $uri) : Request;

    public function delete(string|UriInterface $uri) : Request;

    public function get(string|UriInterface $uri) : Request;

    public function head(string|UriInterface $uri) : Request;

    public function patch(string|UriInterface $uri) : Request;

    public function post(string|UriInterface $uri) : Request;

    public function put(string|UriInterface $uri) : Request;

    public function options(string|UriInterface $uri) : Request;

    public function trace(string|UriInterface $uri) : Request;

}