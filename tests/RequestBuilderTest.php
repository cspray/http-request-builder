<?php declare(strict_types=1);

namespace Cspray\HttpRequestBuilder\Tests;

use Amp\Http\Client\Form;
use Amp\Http\Client\Request;
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

        self::assertInstanceOf(Request::class, $subject);
        self::assertSame([], $subject->getHeaders());
        self::assertNull($subject->getBody()->getContent()->read());
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

        self::assertInstanceOf(Request::class, $subject);
        self::assertSame([['HeaderA', 'foo'], ['HeaderB', 'bar']], $subject->getHeaderPairs());
        self::assertNull($subject->getBody()->getContent()->read());
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

        self::assertInstanceOf(Request::class, $subject);
        self::assertSame([['HeaderA', 'foo'], ['HeaderB', 'bar'], ['HeaderC', 'baz']], $subject->getHeaderPairs());
        self::assertNull($subject->getBody()->getContent()->read());
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

        self::assertInstanceOf(Request::class, $subject);
        self::assertSame([['HeaderA', 'Harry'], ['HeaderB', 'Mack']], $subject->getHeaderPairs());
        self::assertNull($subject->getBody()->getContent()->read());
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

        self::assertInstanceOf(Request::class, $subject);
        self::assertSame([['HeaderA', 'a value']], $subject->getHeaderPairs());
        self::assertNull($subject->getBody()->getContent()->read());
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

        self::assertInstanceOf(Request::class, $subject);
        self::assertSame('application/json; charset=utf-8', $subject->getBody()->getContentType());
        self::assertSame(json_encode([
            'foo' => 'bar',
            'bar' => [
                'baz' => 42
            ]
        ], JSON_THROW_ON_ERROR), $subject->getBody()->getContent()->read());
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

        self::assertInstanceOf(Request::class, $subject);
        self::assertSame([['HeaderA', 'whatever/whatever']], $subject->getHeaderPairs());
        self::assertSame('application/json; charset=utf-8', $subject->getBody()->getContentType());
        self::assertSame(json_encode([
            'foo' => 'bar',
        ], JSON_THROW_ON_ERROR), $subject->getBody()->getContent()->read());
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

        self::assertInstanceOf(Request::class, $subject);
        self::assertSame('application/json; charset=utf-8', $subject->getBody()->getContentType());
        self::assertSame(json_encode([
            'foo' => 'bar',
        ], JSON_THROW_ON_ERROR), $subject->getBody()->getContent()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-json-body-headers', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithHeaderChainedToWithFormBodyDoesNotOverwriteHeaders(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $form = new Form();
        $form->addText('foo', 'bar');
        $subject = RequestBuilder::withHeaders(['HeaderA' => 'whatever/whatever'])
            ->withFormBody($form)
            ->$method('http://' . $method . '.example.com/with-form-body-headers');

        self::assertInstanceOf(Request::class, $subject);
        self::assertSame([['HeaderA', 'whatever/whatever']], $subject->getHeaderPairs());
        self::assertSame('foo=bar', $subject->getBody()->getContent()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-form-body-headers', (string) $subject->getUri());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testWithFormBodyAddContentHeadersAfterDoesNotOverride(string $httpMethod) : void {
        $method = strtolower($httpMethod);
        $body = new Form();
        $body->addText('foo', 'baz');
        $subject = RequestBuilder::withFormBody($body)
            ->setHeader('Content-Type', 'text/plain')
            ->$method('http://' . $method . '.example.com/with-form-body-form-body');

        self::assertInstanceOf(Request::class, $subject);
        self::assertSame('application/x-www-form-urlencoded', $subject->getBody()->getContentType());
        self::assertSame('foo=baz', $subject->getBody()->getContent()->read());
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

        self::assertInstanceOf(Request::class, $subject);
        self::assertSame('text/plain', $subject->getBody()->getContentType());
        self::assertSame('Just a spazz, y\'all.', $subject->getBody()->getContent()->read());
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

        self::assertInstanceOf(Request::class, $subject);
        self::assertSame([['HeaderA', 'whatever/whatever']], $subject->getHeaderPairs());
        self::assertSame('text/html', $subject->getBody()->getContentType());
        self::assertSame('<!DOCTYPE html><html></html>', $subject->getBody()->getContent()->read());
        self::assertSame($httpMethod, $subject->getMethod());
        self::assertSame('http://' . $method . '.example.com/with-html-body', (string) $subject->getUri());
    }

}
