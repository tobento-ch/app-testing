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
use Tobento\App\Testing\Event\FakeEvent;
use Tobento\App\Testing\Event\TestEvents;

trait InteractsWithEvent
{
    /**
     * Returns a new test events instance.
     *
     * @param null|AppInterface $app
     * @return TestEvents
     */
    final public function fakeEvents(null|AppInterface $app = null): TestEvents
    {
        if ($this->hasFaker(FakeEvent::class)) {
            return $this->getFaker(FakeEvent::class)->events();
        }
        
        return $this->addFaker(new FakeEvent($app ?: $this->getApp()))->events();
    }
}