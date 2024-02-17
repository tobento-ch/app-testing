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
use Tobento\App\Testing\User\FakeAuth;

trait InteractsWithUser
{
    /**
     * Returns a new fake auth instance.
     *
     * @param null|AppInterface $app
     * @return FakeAuth
     */
    final public function fakeAuth(null|AppInterface $app = null): FakeAuth
    {
        if ($this->hasFaker(FakeAuth::class)) {
            return $this->getFaker(FakeAuth::class);
        }
        
        return $this->addFaker(new FakeAuth($app ?: $this->getApp()));
    }
}