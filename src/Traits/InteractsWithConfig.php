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
    protected null|FakeConfig $fakeConfig = null;
    
    /**
     * Returns a new fake config instance.
     *
     * @param null|AppInterface $app
     * @return FakeConfig
     */
    final public function fakeConfig(null|AppInterface $app = null): FakeConfig
    {
        if ($this->fakeConfig) {
            return $this->fakeConfig;
        }

        return $this->fakeConfig = new FakeConfig($app ?: $this->getApp());
    }
}