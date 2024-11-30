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
use Tobento\App\Testing\Logging\FakeLogging;

trait InteractsWithLogging
{
    /**
     * Returns a new fake logger instance.
     *
     * @param null|AppInterface $app
     * @return FakeLogging
     */
    final public function fakeLogging(null|AppInterface $app = null): FakeLogging
    {
        if ($this->hasFaker(FakeLogging::class)) {
            return $this->getFaker(FakeLogging::class);
        }
        
        return $this->addFaker(new FakeLogging($app ?: $this->getApp()));
    }
}