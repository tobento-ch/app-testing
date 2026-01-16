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

use Psr\Http\Client\ClientInterface;
use Tobento\App\AppInterface;
use Tobento\App\Testing\FakerInterface;

final class FakeHttpClient implements FakerInterface
{
    protected null|TestClient $testClient = null;
    
    /**
     * Create a new instance.
     *
     * @param AppInterface $app
     */
    public function __construct(
        private AppInterface $app,
    ) {}
    
    /**
     * Returns a new instance.
     *
     * @param AppInterface $app
     * @return static
     */
    public function new(AppInterface $app): static
    {
        $fakeClient = new static($app);
        $fakeClient->client();
        return $fakeClient;
    }

    /**
     * Returns the test client.
     *
     * @return TestClient
     */
    public function client(): TestClient
    {
        if (is_null($this->testClient)) {
            $this->testClient = new TestClient();
        }

        $this->app->on(
            ClientInterface::class,
            function(ClientInterface $client): ClientInterface {
                return $this->testClient ?: $client;
            }
        )->priority(-1500);
        
        return $this->testClient;
    }
}