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
use Tobento\App\Testing\App\FakeConfig;

trait InteractsWithConfig
{
    /**
     * Returns a new fake config instance.
     *
     * @param null|AppInterface $app
     * @return FakeConfig
     */
    final public function fakeConfig(null|AppInterface $app = null): FakeConfig
    {
        if ($this->hasFaker(FakeConfig::class)) {
            return $this->getFaker(FakeConfig::class);
        }
        
        return $this->addFaker(new FakeConfig($app ?: $this->getApp()));
    }
}