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
use Tobento\App\Testing\Queue\FakeQueue;

trait InteractsWithQueue
{
    /**
     * Returns a new fake queue instance.
     *
     * @param null|AppInterface $app
     * @return FakeQueue
     */
    final public function fakeQueue(null|AppInterface $app = null): FakeQueue
    {
        return new FakeQueue($app ?: $this->getApp());
    }
}