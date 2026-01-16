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
use Tobento\App\Testing\Http\FakeSimpleResponseEmitter;

trait InteractsWithHttpResponseEmitter
{
    /**
     * Returns a fake simple response emitter instance.
     *
     * @param null|AppInterface $app
     * @return FakeSimpleResponseEmitter
     */
    final public function fakeHttpResponseEmitter(null|AppInterface $app = null): FakeSimpleResponseEmitter
    {
        if ($this->hasFaker(FakeSimpleResponseEmitter::class)) {
            return $this->getFaker(FakeSimpleResponseEmitter::class);
        }

        $fake = new FakeSimpleResponseEmitter($app ?: $this->getApp());
        $fake->register();

        return $this->addFaker($fake);
    }
}