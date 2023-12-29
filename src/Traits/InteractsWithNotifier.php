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
use Tobento\App\Testing\Notifier\FakeNotifier;

trait InteractsWithNotifier
{
    /**
     * Returns a new fake notifier instance.
     *
     * @param null|AppInterface $app
     * @return FakeNotifier
     */
    final public function fakeNotifier(null|AppInterface $app = null): FakeNotifier
    {
        return new FakeNotifier($app ?: $this->getApp());
    }
}