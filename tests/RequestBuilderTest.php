<?php declare(strict_types=1);

namespace Cspray\HttpRequestBuilder\Tests;

use Amp\Http\Client\Body\FormBody;
use Amp\Http\Client\Body\JsonBody;
use Cspray\HttpRequestBuilder\RequestBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Cspray\HttpRequestBuilder\RequestBuilder
 */
final class RequestBuilderTest extends TestCase {

    public static function httpMethodProvider() : array {
        return [
            ['GET'],
            ['POST'],
            ['PUT'],
            ['DELETE'],
            ['PATCH'],
            ['CONNECT'],
            ['HEAD'],
            ['TRACE'],
            ['OPTIONS']
        ];
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testSimpleUriOnlyRequest(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::$method('http://' . $method . '.example.com');

        self::assertSame([], $subject->getHeaders());
        self::assertNull($subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithHeadersSetProperly(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::withHeaders([
            'HeaderA' => 'foo',
            'HeaderB' => 'bar'
        ])->$method('http://' . $method . '.example.com/with-headers');

        self::assertSame([['HeaderA', 'foo'], ['HeaderB', 'bar']], $subject->getRawHeaders());
        self::assertNull($subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-headers', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithHeadersChainedToAddHeadersDoesNotReplaceHeaders(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::withHeaders([
            'HeaderA' => 'foo',
            'HeaderB' => 'bar'
        ])->addHeaders([
            'HeaderC' => 'baz'
        ])->$method('http://' . $method . '.example.com/with-extra-headers');

        self::assertSame([['HeaderA', 'foo'], ['HeaderB', 'bar'], ['HeaderC', 'baz']], $subject->getRawHeaders());
        self::assertNull($subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-extra-headers', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithHeadersChainedToSetHeaderReplacesExistingHeaders(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::withHeaders([
            'HeaderA' => 'Harry',
            'HeaderB' => 'Henderson'
        ])->setHeader('HeaderB', 'Mack')
            ->$method('http://' . $method . '.example.com/spazz');

        self::assertSame([['HeaderA', 'Harry'], ['HeaderB', 'Mack']], $subject->getRawHeaders());
        self::assertNull($subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/spazz', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithHeaderHasSingleHeaderIncludedInRequest(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::withHeader('HeaderA', 'a value')
            ->$method('http://' . $method . '.example.com/with-header');

        self::assertSame([['HeaderA', 'a value']], $subject->getRawHeaders());
        self::assertNull($subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-header', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithJsonBodyAsArrayCreatesCorrectHeadersAndBody(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::withJsonBody([
            'foo' => 'bar',
            'bar' => [
                'baz' => 42
            ]
        ])->$method('http://' . $method . '.example.com/with-json-body-array');

        self::assertSame([['content-type', 'application/json; charset=utf-8']], $subject->getRawHeaders());
        self::assertSame(json_encode([
            'foo' => 'bar',
            'bar' => [
                'baz' => 42
            ]
        ], JSON_THROW_ON_ERROR), $subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-json-body-array', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithHeaderChainedToWithJsonBodyDoesNotOverwriteHeaders(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::withHeaders(['HeaderA' => 'whatever/whatever'])
            ->withJsonBody(['foo' => 'bar'])
            ->$method('http://' . $method . '.example.com/with-json-body-headers');

        self::assertSame([['HeaderA', 'whatever/whatever'], ['content-type', 'application/json; charset=utf-8']], $subject->getRawHeaders());
        self::assertSame(json_encode([
            'foo' => 'bar',
        ], JSON_THROW_ON_ERROR), $subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-json-body-headers', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithHeaderChainedToWithJsonBodyDoesOverwriteContentType(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::withHeaders(['Content-Type' => 'text/plain'])
            ->withJsonBody(['foo' => 'bar'])
            ->$method('http://' . $method . '.example.com/with-json-body-headers');

        self::assertSame([['content-type', 'application/json; charset=utf-8']], $subject->getRawHeaders());
        self::assertSame(json_encode([
            'foo' => 'bar',
        ], JSON_THROW_ON_ERROR), $subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-json-body-headers', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithJsonBodyAsJsonBody(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::withJsonBody(new JsonBody(['foo' => 'baz']))
            ->$method('http://' . $method . '.example.com/with-json-body-json-body');

        self::assertSame([['content-type', 'application/json; charset=utf-8']], $subject->getRawHeaders());
        self::assertSame(json_encode([
            'foo' => 'baz',
        ], JSON_THROW_ON_ERROR), $subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-json-body-json-body', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithJsonBodyAddContentHeadersAfterDoesNotOverride(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::withJsonBody(new JsonBody(['foo' => 'baz']))
            ->setHeader('Content-Type', 'text/plain')
            ->$method('http://' . $method . '.example.com/with-json-body-json-body');

        self::assertSame([['content-type', 'application/json; charset=utf-8']], $subject->getRawHeaders());
        self::assertSame(json_encode([
            'foo' => 'baz',
        ], JSON_THROW_ON_ERROR), $subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-json-body-json-body', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithFormBodyAsArrayHasCorrectHeadersAndBody(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::withFormBody([
            'foo' => 'bar',
            'bar' => '42'
        ])->$method('http://' . $method . '.example.com/with-form-body-array');

        self::assertSame([['Content-Type', 'application/x-www-form-urlencoded']], $subject->getRawHeaders());
        self::assertSame('foo=bar&bar=42', $subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-form-body-array', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithHeaderChainedToWithFormBodyDoesNotOverwriteHeaders(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::withHeaders(['HeaderA' => 'whatever/whatever'])
            ->withFormBody(['foo' => 'bar'])
            ->$method('http://' . $method . '.example.com/with-form-body-headers');

        self::assertSame([['HeaderA', 'whatever/whatever'], ['Content-Type', 'application/x-www-form-urlencoded']], $subject->getRawHeaders());
        self::assertSame('foo=bar', $subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-form-body-headers', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithHeaderChainedToWithFormBodyDoesOverwriteContentType(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::withHeaders(['Content-Type' => 'text/plain'])
            ->withFormBody(['foo' => 'bar'])
            ->$method('http://' . $method . '.example.com/with-form-body-headers');

        self::assertSame([['Content-Type', 'application/x-www-form-urlencoded']], $subject->getRawHeaders());
        self::assertSame('foo=bar', $subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-form-body-headers', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithFormBodyAsFormBody(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $body = new FormBody();
        $body->addFields(['foo' => 'baz']);
        $subject = RequestBuilder::withFormBody($body)
            ->$method('http://' . $method . '.example.com/with-form-body-form-body');

        self::assertSame([['Content-Type', 'application/x-www-form-urlencoded']], $subject->getRawHeaders());
        self::assertSame('foo=baz', $subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-form-body-form-body', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithFormBodyAddContentHeadersAfterDoesNotOverride(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $body = new FormBody();
        $body->addFields(['foo' => 'baz']);
        $subject = RequestBuilder::withFormBody($body)
            ->setHeader('Content-Type', 'text/plain')
            ->$method('http://' . $method . '.example.com/with-form-body-form-body');

        self::assertSame([['Content-Type', 'application/x-www-form-urlencoded']], $subject->getRawHeaders());
        self::assertSame('foo=baz', $subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-form-body-form-body', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithBodyHasCorrectHeadersAndBody(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::withBody("Just a spazz, y'all.")
            ->$method('http://' . $method . '.example.com/with-body');

        self::assertSame([['Content-Type', 'text/plain']], $subject->getRawHeaders());
        self::assertSame('Just a spazz, y\'all.', $subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-body', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithHeaderChainedToWithBodyDoesNotOverwriteHeaders(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::withHeaders(['HeaderA' => 'whatever/whatever'])
            ->withBody('<!DOCTYPE html><html></html>', 'text/html')
            ->$method('http://' . $method . '.example.com/with-html-body');

        self::assertSame([['HeaderA', 'whatever/whatever'], ['Content-Type', 'text/html']], $subject->getRawHeaders());
        self::assertSame('<!DOCTYPE html><html></html>', $subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-html-body', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithHeaderChainedToWithBodyDoesOverwriteContentType(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $subject = RequestBuilder::withHeaders(['Content-Type' => 'text/plain'])
            ->withBody('1,2,3', 'text/csv')
            ->$method('http://' . $method . '.example.com/with-csv-body');

        self::assertSame([['Content-Type', 'text/csv']], $subject->getRawHeaders());
        self::assertSame('1,2,3', $subject->getBody()->createBodyStream()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-csv-body', (string) $subject->getUri());
    }

}