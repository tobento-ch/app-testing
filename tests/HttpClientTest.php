<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Testing\Test;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Tobento\App\AppInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;

class HttpClientTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        return $app;
    }

    /**
     * Helper to send an outgoing HTTP request through the app's PSR-18 client.
     */
    protected function send(
        string $method = 'POST',
        string $uri = 'https://example.com/webhook'
    ) {
        $app = $this->getApp();
        $requestFactory = $app->get(RequestFactoryInterface::class);
        $client = $app->get(ClientInterface::class);

        $request = $requestFactory->createRequest($method, $uri);

        return $client->sendRequest($request);
    }

    public function testAssertSent(): void
    {
        $client = $this->fakeHttpClient();

        $this->getApp()->booting();
        $this->send();

        $client->assertSent();
    }

    public function testAssertSentWithCallback(): void
    {
        $client = $this->fakeHttpClient();

        $this->getApp()->booting();
        $this->send('POST', 'https://example.com/webhook');

        $client->assertSent(function ($request) {
            return $request->getMethod() === 'POST'
                && (string)$request->getUri() === 'https://example.com/webhook';
        });
    }

    public function testAssertNotSent(): void
    {
        $client = $this->fakeHttpClient();

        $this->getApp()->booting();
        $this->send('POST');

        $client->assertNotSent(function ($request) {
            return $request->getMethod() === 'GET';
        });
    }

    public function testAssertSentCount(): void
    {
        $client = $this->fakeHttpClient();

        $this->getApp()->booting();
        $this->send();

        $client->assertSentCount(1);
    }

    public function testAssertSentTimes(): void
    {
        $client = $this->fakeHttpClient();

        $this->getApp()->booting();
        $this->send();
        $this->send();

        $client->assertSentTimes(function ($request) {
            return $request->getMethod() === 'POST';
        }, 2);
    }

    public function testAssertNothingSent(): void
    {
        $client = $this->fakeHttpClient();

        $this->getApp()->booting();

        $client->assertNothingSent();
    }

    public function testFakeResponse(): void
    {
        $client = $this->fakeHttpClient();

        $this->getApp()->booting();
        $client->fakeResponse(new Response(201));

        $response = $this->send();

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testFakeResponseUsingFactory(): void
    {
        $client = $this->fakeHttpClient();

        $this->getApp()->booting();
        $factory = $this->getApp()->get(ResponseFactoryInterface::class);

        $client->fakeResponse($factory->createResponse(202));

        $response = $this->send();

        $this->assertSame(202, $response->getStatusCode());
    }

    public function testFakeException(): void
    {
        $client = $this->fakeHttpClient();

        $this->getApp()->booting();
        $client->fakeException(new \RuntimeException('Network error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Network error');

        $this->send();
    }

    public function testLastRequestAndRequests(): void
    {
        $client = $this->fakeHttpClient();

        $this->getApp()->booting();

        $this->send('POST', 'https://example.com/one');
        $this->send('POST', 'https://example.com/two');

        $last = $client->lastRequest();
        $all = $client->requests();

        $this->assertSame('https://example.com/two', (string)$last->getUri());
        $this->assertCount(2, $all);
    }

    public function testAssertSentThrowsException(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $client = $this->fakeHttpClient();
        $this->getApp()->booting();

        $client->assertSent();
    }

    public function testAssertSentTimesThrowsException(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $client = $this->fakeHttpClient();
        $this->getApp()->booting();

        $client->assertSentTimes(fn () => true, 2);
    }

    public function testAssertNotSentThrowsException(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $client = $this->fakeHttpClient();
        $this->getApp()->booting();

        $this->send();

        $client->assertNotSent(fn () => true);
    }
    
    public function testHttpClientFakesPersistAcrossRedirects()
    {
        // Register routes that trigger a redirect
        $this->onCreateApp(function(AppInterface $app) {
            $app->on(RouterInterface::class, function($router) {
                $router->get('start', function(ResponserInterface $responser) {
                    return $responser->redirect(uri: 'final');
                });

                $router->get('final', function(ClientInterface $client) {
                    // Outgoing request made during redirect
                    $this->send();
                    return 'ok';
                });
            });
        });

        // Register fake client once
        $client = $this->fakeHttpClient();
        // Fake incoming HTTP
        $http = $this->fakeHttp();
        $http->request('GET', 'start');

        // Follow redirect
        $http->followRedirects()->assertBodySame('ok');
        
        $client = $this->fakeHttpClient();
        
        // Assert outgoing request was captured by fake client
        $client->assertSent();
    }
}