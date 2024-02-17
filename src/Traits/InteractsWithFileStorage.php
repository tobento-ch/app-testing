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
use Tobento\App\Testing\FileStorage\FakeFileStorage;

trait InteractsWithFileStorage
{
    /**
     * Returns a new fake file storage instance.
     *
     * @param null|AppInterface $app
     * @return FakeFileStorage
     */
    final public function fakeFileStorage(null|AppInterface $app = null): FakeFileStorage
    {
        if ($this->hasFaker(FakeFileStorage::class)) {
            return $this->getFaker(FakeFileStorage::class);
        }
        
        return $this->addFaker(new FakeFileStorage($app ?: $this->getApp()));
    }
}