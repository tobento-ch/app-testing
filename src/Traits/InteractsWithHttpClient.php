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

namespace Tobento\App\Testing\Traits;

use Tobento\App\AppInterface;
use Tobento\App\Testing\Http\FakeHttpClient;
use Tobento\App\Testing\Http\TestClient;

trait InteractsWithHttpClient
{
    /**
     * Returns a fake PSR-18 HTTP client.
     *
     * @param null|AppInterface $app
     * @return TestClient
     */     
    final public function fakeHttpClient(null|AppInterface $app = null): TestClient
    {
        if ($this->hasFaker(FakeHttpClient::class)) {
            return $this->getFaker(FakeHttpClient::class)->client();
        }
        
        return $this->addFaker(new FakeHttpClient($app ?: $this->getApp()))->client();
    }
}