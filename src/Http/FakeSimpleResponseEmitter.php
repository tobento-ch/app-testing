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

use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Tobento\App\AppInterface;
use Tobento\App\Http\SimpleResponseEmitterInterface;
use Tobento\App\Testing\FakerInterface;

/**
 * FakeSimpleResponseEmitter
 *
 * A testing fake for SimpleResponseEmitterInterface. It captures the emitted
 * PSR-7 response and exposes it as a TestResponse for assertions.
 */
final class FakeSimpleResponseEmitter implements FakerInterface, SimpleResponseEmitterInterface
{
    /**
     * The emitted response instance, if any.
     *
     * @var null|ResponseInterface
     */
    protected null|ResponseInterface $response = null;

    /**
     * Whether the fake has been registered in the container.
     *
     * @var bool
     */
    protected bool $registered = false;
    
    /**
     * Create a new instance.
     *
     * @param AppInterface $app
     */
    public function __construct(
        protected AppInterface $app,
    ) {}
    
    /**
     * Create a new faker instance for a fresh app context.
     *
     * This is used internally when the testing framework boots a new App
     * instance (e.g., during followRedirects()). The fake re-registers itself
     * so that subsequent requests continue using it.
     *
     * @param AppInterface $app
     * @return static
     */
    public function new(AppInterface $app): static
    {
        $fake = new static($app);
        $fake->register();
        return $fake;
    }
    
    /**
     * Emit the specified response to the client.
     *
     * Implementations may send headers, output the body,
     * and perform any finalization required by the environment.
     *
     * @param ResponseInterface $response
     * @return ResponseInterface The emitted response.
     */
    public function emit(ResponseInterface $response): ResponseInterface
    {
        return $this->response = $response;
    }

    /**
     * Returns a TestResponse instance for asserting the emitted response.
     *
     * @return TestResponse
     * @throws RuntimeException If no response has been emitted.
     */
    public function response(): TestResponse
    {
        // Register the fake emitter in the container
        $this->register();

        if ($this->response === null) {
            throw new RuntimeException('No response was emitted.');
        }
        
        // Create a minimal testing request (required by TestResponse)
        $request = new Request(
            method: 'GET',
            uri: '/_fake_emitter'
        );

        return new TestResponse(
            request: $request,
            response: $this->response,
            session: null,
            router: null,
        );
    }
    
    /**
     * Registers the fake emitter in the app container.
     *
     * @return void
     */
    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->app->on(
            SimpleResponseEmitterInterface::class,
            fn () => $this
        )->priority(-1500);

        $this->registered = true;
    }
}