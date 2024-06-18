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

use Tobento\App\Testing\FakerInterface;
use Tobento\App\Testing\TestCase;
use Tobento\App\AppInterface;
use Tobento\App\Testing\App\FakeConfig;
use Tobento\App\Http\Boot\Http;
use Tobento\App\Http\Boot\Middleware;
use Tobento\App\Http\ResponseEmitterInterface;
use Tobento\App\Http\SessionFactory as DefaultSessionFactory;
use Tobento\Service\Middleware\MiddlewareFactoryInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Session\SessionInterface;
use Tobento\Service\Uri\PreviousUri;
use Tobento\Service\Uri\PreviousUriInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Stringable;

final class FakeHttp implements FakerInterface
{
    private null|Request $request = null;
    
    private null|TestResponse $response = null;
    
    /**
     * Create a new FakeHttp.
     *
     * @param AppInterface $app
     * @param FakeConfig $fakeConfig
     * @param FileFactory $fileFactory
     * @param TestCase $testCase
     * @param null|SessionInterface $session
     * @param array $replaceMiddlewares
     */
    public function __construct(
        private AppInterface $app,
        private FakeConfig $fakeConfig,
        private FileFactory $fileFactory,
        private TestCase $testCase,
        private null|SessionInterface $session = null,
        private array $replaceMiddlewares = [],
    ) {
        // Replace response emitter for testing:
        $app->on(ResponseEmitterInterface::class, ResponseEmitter::class);
        
        // Replaces session middleware to ignore session start and save exceptions.
        $this->app->on(
            MiddlewareFactoryInterface::class,
            static function(MiddlewareFactoryInterface $factory) use ($replaceMiddlewares) {
                $factory->replaceMiddleware(
                    \Tobento\Service\Session\Middleware\Session::class,
                    \Tobento\App\Testing\Http\SessionMiddleware::class
                );
                
                $factory->replaceMiddleware(\Tobento\Service\Form\Middleware\VerifyCsrfToken::class, null);
                
                foreach($replaceMiddlewares as $mw => $withMw) {
                    $factory->replaceMiddleware($mw, $withMw);
                }
            }
        );
        
        // Add session factory without using server request:
        $fakeConfig->with('session.factory', \Tobento\App\Testing\Http\SessionFactory::class);
        
        $app->on(
            ServerRequestInterface::class,
            function(ServerRequestInterface $request): ServerRequestInterface {
                return !is_null($this->request) ? $this->request->getRequest() : $request;
            }
        );
        
        $app->on(
            SessionInterface::class,
            function(SessionInterface $sess) use ($session): SessionInterface {
                if ($session) {
                    foreach($session->all() as $key => $value) {
                        $sess->set($key, $value);
                    }
                }
                return $sess;
            }
        );
    }
    
    /**
     * Returns a new instance.
     *
     * @param AppInterface $app
     * @return static
     */
    public function new(AppInterface $app): static
    {
        $session = null;
        $replaceMiddlewares = [];
        
        if ($this->app->has(SessionInterface::class)) {
            $session = $this->app->get(SessionInterface::class);
        }
        
        if ($this->app->has(MiddlewareFactoryInterface::class)) {
            $replaceMiddlewares = $this->app->get(MiddlewareFactoryInterface::class)->getReplaceMiddlewares();
        }

        return new static(
            app: $app,
            fakeConfig: $this->fakeConfig,
            fileFactory: $this->fileFactory,
            testCase: $this->testCase,
            session: $session,
            replaceMiddlewares: $replaceMiddlewares,
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
        // Make a new app request if one was previously made:
        if (!is_null($this->request)) {
            $previousResponse = $this->response();
            $app = $this->testCase->newApp();
            
            foreach ($this->testCase->getFakers() as $faker) {
                $this->testCase->addFaker($faker->new($app));
            }

            $http = $this->testCase->getFaker($this::class);
            
            if ($previousResponse->cookies()) {
                $cookies = array_merge($previousResponse->cookies(), is_array($cookies) ? $cookies : []);
            }
            
            $server = array_merge($this->request->getRequest()->getServerParams(), $server);
            
            return $http->request(
                method: $method,
                uri: $uri,
                server: $server,
                query: $query,
                headers: $headers,
                cookies: $cookies,
                files: $files,
                body: $body,
            );
        }
        
        // First app request:
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
        $httpFaker = $this->testCase->getFaker($this::class);
        
        if ($httpFaker !== $this) {
            return $httpFaker->response();
        }
        
        if ($this->response) {
            return $this->response;
        }
        
        $this->testCase->runApp();
        
        return $this->response = new TestResponse(
            response: $this->app->get(Http::class)->getResponse(),
            session: $this->app->has(SessionInterface::class) ? $this->app->get(SessionInterface::class) : null,
            router: $this->app->has(RouterInterface::class) ? $this->app->get(RouterInterface::class) : null,
        );
    }
    
    /**
     * Follow redirects.
     *
     * @return TestResponse
     */
    public function followRedirects(): TestResponse
    {
        $previousResponse = $this->response();
        
        if (! $previousResponse->isRedirect()) {
            return $previousResponse;
        }
        
        $location = $previousResponse->response()->getHeaderLine('Location');
        
        $this->request(method: 'GET', uri: $location);
        
        return $this->testCase->getFaker($this::class)->followRedirects();
    } 
    
    /**
     * Without middleware.
     *
     * @param string ...$middleware
     * @return static $this
     */
    public function withoutMiddleware(string ...$middleware): static
    {
        $this->app->on(
            MiddlewareFactoryInterface::class,
            static function(MiddlewareFactoryInterface $factory) use ($middleware) {
                foreach($middleware as $mw) {
                    $factory->replaceMiddleware($mw, null);
                }
            }
        );
        
        return $this;
    }
    
    /**
     * Set the previous uri.
     *
     * @param string|Stringable|UriInterface $uri
     * @return static $this
     */
    public function previousUri(string|Stringable|UriInterface $uri): static
    {
        $this->app->on(PreviousUriInterface::class, function() use ($uri) {
            if ($uri instanceof UriInterface) {
                return new PreviousUri($uri);
            }
            
            return new PreviousUri($this->app->get(UriFactoryInterface::class)->createUri((string)$uri));
        })->priority(-1000);
        
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