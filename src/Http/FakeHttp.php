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

namespace Tobento\App\Testing\Http;

use Tobento\App\AppInterface;
use Tobento\App\Testing\App\FakeConfig;
use Tobento\App\Http\Boot\Http;
use Tobento\App\Http\Boot\Middleware;
use Tobento\App\Http\ResponseEmitterInterface;
use Tobento\App\Http\SessionFactory as DefaultSessionFactory;
use Tobento\App\Testing\Http\MiddlewareBoot;
use Psr\Http\Message\ServerRequestInterface;

final class FakeHttp
{
    private null|Request $request = null;
    
    /**
     * Create a new FakeHttp.
     *
     * @param AppInterface $app
     * @param FakeConfig $fakeConfig
     * @param FileFactory $fileFactory
     */
    public function __construct(
        private AppInterface $app,
        private FakeConfig $fakeConfig,
        private FileFactory $fileFactory,
    ) {
        // Replace response emitter for testing:
        $app->on(ResponseEmitterInterface::class, ResponseEmitter::class);
        
        // Replaces session middleware to ignore session start and save exceptions.
        $app->on(Middleware::class, MiddlewareBoot::class);
        
        // Add session factory without using server request:
        $fakeConfig->with('session.factory', \Tobento\App\Testing\Http\SessionFactory::class);
        
        $app->on(
            ServerRequestInterface::class,
            function(ServerRequestInterface $request): ServerRequestInterface {
                return !is_null($this->request) ? $this->request->getRequest() : $request;
            }
        );
    }

    /**
     * Set the request.
     *
     * @param string $method
     * @param string $uri
     * @param array $server
     * @param null|array $query
     * @param null|array $headers
     * @param null|array $cookies
     * @param null|array $files
     * @param null|array|object $body
     * @return Request
     */
    public function request(
        string $method,
        string $uri,
        array $server = [],
        null|array $query = null,
        null|array $headers = null,
        null|array $cookies = null,
        null|array $files = null,
        null|array|object $body = null,
    ): Request {
        $this->request = new Request($method, $uri, $server);
        
        if (!is_null($query)) {
             $this->request->query($query);
        }
        
        if (!is_null($headers)) {
             $this->request->headers($headers);
        }
        
        if (!is_null($cookies)) {
             $this->request->cookies($cookies);
        }
        
        if (!is_null($files)) {
             $this->request->files($files);
        }
        
        if (!is_null($body)) {
             $this->request->body($body);
        }
        
        return $this->request;
    }
    
    /**
     * Returns the test response.
     *
     * @return TestResponse
     */
    public function response(): TestResponse
    {
        $this->app->run();
        
        return new TestResponse($this->app->get(Http::class)->getResponse());
    }
    
    /**
     * Without middleware.
     *
     * @param string ...$middleware
     * @return static $this
     */
    public function withoutMiddleware(string ...$middleware): static
    {
        $this->app->on(Middleware::class, fn ($m) => $m->without(...$middleware));
        
        return $this;
    }
    
    /**
     * Returns the file factory.
     *
     * @return FileFactory
     */
    public function getFileFactory(): FileFactory
    {
        return $this->fileFactory;
    }
}