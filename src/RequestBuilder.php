<?php declare(strict_types=1);

namespace Cspray\HttpRequestBuilder;

use Amp\ByteStream\ReadableBuffer;
use Amp\ByteStream\ReadableStream;
use Amp\Http\Client\Body\FormBody;
use Amp\Http\Client\Body\JsonBody;
use Amp\Http\Client\Request;
use Amp\Http\Client\RequestBody;
use Psr\Http\Message\UriInterface;

final class RequestBuilder {

    public static function withHeaders(array $headers) : FluentRequestBuilder {
        return self::createFluentRequestBuilder()->setHeaders($headers);
    }

    public static function withHeader(string $name, string $value) : FluentRequestBuilder {
        return self::createFluentRequestBuilder()->setHeaders([$name => $value]);
    }

    public static function withJsonBody(array|JsonBody $json) : FluentRequestBuilder {
        return self::createFluentRequestBuilder()->withJsonBody($json);
    }

    public static function withFormBody(array|FormBody $formData) : FluentRequestBuilder {
        return self::createFluentRequestBuilder()->withFormBody($formData);
    }

    public static function withBody(string $body, string $contentType = 'text/plain') : FluentRequestBuilder {
        return self::createFluentRequestBuilder()->withBody($body, $contentType);
    }

    public static function get(string|UriInterface $uri) : Request {
        return self::createFluentRequestBuilder()->get($uri);
    }

    public static function post(string|UriInterface $uri) : Request {
        return self::createFluentRequestBuilder()->post($uri);
    }

    public static function put(string|UriInterface $uri) : Request {
        return self::createFluentRequestBuilder()->put($uri);
    }

    public static function patch(string|UriInterface $uri) : Request {
        return self::createFluentRequestBuilder()->patch($uri);
    }

    public static function delete(string|UriInterface $uri) : Request {
        return self::createFluentRequestBuilder()->delete($uri);
    }

    public static function connect(string|UriInterface $uri) : Request {
        return self::createFluentRequestBuilder()->connect($uri);
    }

    public static function head(string|UriInterface $uri) : Request {
        return self::createFluentRequestBuilder()->head($uri);
    }

    public static function trace(string|UriInterface $uri) : Request {
        return self::createFluentRequestBuilder()->trace($uri);
    }

    public static function options(string|UriInterface $uri) : Request {
        return self::createFluentRequestBuilder()->options($uri);
    }

    private static function createFluentRequestBuilder() : FluentRequestBuilder {
        return new class implements FluentRequestBuilder {

            private array $headers = [];

            private RequestBody|string $requestBody = '';

            public function setHeaders(array $headers) : FluentRequestBuilder {
                $clone = clone $this;
                $clone->headers = $headers;
                return $clone;
            }

            public function addHeaders(array $headers) : FluentRequestBuilder {
                $clone = clone $this;
                $clone->headers = array_merge($clone->headers, $headers);
                return $clone;
            }

            public function setHeader(string $header, string|array $value) : FluentRequestBuilder {
                $clone = clone $this;
                $clone->headers[$header] = $value;
                return $clone;
            }

            public function withJsonBody(array|JsonBody $body) : FluentRequestBuilder {
                $clone = clone $this;
                if (is_array($body)) {
                    $body = new JsonBody($body);
                }
                $clone->requestBody = $body;
                return $clone;
            }

            public function withFormBody(array|FormBody $body) : FluentRequestBuilder {
                $clone = clone $this;
                if (is_array($body)) {
                    $formBody = new FormBody();
                    $formBody->addFields($body);
                } else {
                    $formBody = $body;
                }
                $clone->requestBody = $formBody;
                return $clone;
            }

            public function withBody(string $body, string $contentType = 'text/plain') : FluentRequestBuilder {
                $clone = clone $this;
                $clone->requestBody = new class($body, $contentType) implements RequestBody {

                    public function __construct(
                        private readonly string $body,
                        private readonly string $contentType,
                    ) {}

                    public function getHeaders() : array {
                        return [
                            'Content-Type' => $this->contentType
                        ];
                    }

                    public function createBodyStream() : ReadableStream {
                        return new ReadableBuffer($this->body);
                    }

                    public function getBodyLength() : ?int {
                        return strlen($this->body);
                    }
                };
                return $clone;
            }

            public function connect(UriInterface|string $uri) : Request {
                $request = new Request(
                    uri: $uri,
                    method: 'CONNECT',
                    body: $this->requestBody
                );
                $request->setHeaders($this->getAllHeaders());
                return $request;
            }

            public function delete(UriInterface|string $uri) : Request {
                $request = new Request(
                    uri: $uri,
                    method: 'DELETE',
                    body: $this->requestBody
                );
                $request->setHeaders($this->getAllHeaders());
                return $request;
            }

            public function get(UriInterface|string $uri) : Request {
                $request = new Request(
                    uri: $uri,
                    method: 'GET',
                    body: $this->requestBody
                );
                $request->setHeaders($this->getAllHeaders());
                return $request;
            }

            public function head(UriInterface|string $uri) : Request {
                $request = new Request(
                    uri: $uri,
                    method: 'HEAD',
                    body: $this->requestBody
                );
                $request->setHeaders($this->getAllHeaders());
                return $request;
            }

            public function options(UriInterface|string $uri) : Request {
                $request = new Request(
                    uri: $uri,
                    method: 'OPTIONS',
                    body: $this->requestBody
                );
                $request->setHeaders($this->getAllHeaders());
                return $request;
            }

            public function patch(UriInterface|string $uri) : Request {
                $request = new Request(
                    uri: $uri,
                    method: 'PATCH',
                    body: $this->requestBody
                );
                $request->setHeaders($this->getAllHeaders());
                return $request;
            }

            public function post(UriInterface|string $uri) : Request {
                $request = new Request(
                    uri: $uri,
                    method: 'POST',
                    body: $this->requestBody
                );
                $request->setHeaders($this->getAllHeaders());
                return $request;
            }

            public function put(UriInterface|string $uri) : Request {
                $request = new Request(
                    uri: $uri,
                    method: 'PUT',
                    body: $this->requestBody
                );
                $request->setHeaders($this->getAllHeaders());
                return $request;
            }

            public function trace(UriInterface|string $uri) : Request {
                $request = new Request(
                    uri: $uri,
                    method: 'TRACE',
                    body: $this->requestBody
                );
                $request->setHeaders($this->getAllHeaders());
                return $request;
            }

            private function getAllHeaders() : array {
                $bodyHeaders = $this->requestBody instanceof RequestBody ? $this->requestBody->getHeaders() : [];
                return array_merge([], $this->headers, $bodyHeaders);
            }
        };
    }

}
