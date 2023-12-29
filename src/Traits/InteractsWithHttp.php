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
use Tobento\App\Testing\Http\FakeHttp;
use Tobento\App\Testing\Http\FileFactory;

trait InteractsWithHttp
{
    /**
     * Returns a new file factory instance.
     *
     * @return FileFactory
     */
    final public function getFileFactory(): FileFactory
    {
        return new FileFactory();
    }
    
    /**
     * Returns a new fake http instance.
     *
     * @param null|AppInterface $app
     * @return FakeHttp
     */
    final public function fakeHttp(null|AppInterface $app = null): FakeHttp
    {
        return new FakeHttp(
            app: $app ?: $this->getApp(),
            fakeConfig: $this->fakeConfig(),
            fileFactory: $this->getFileFactory(),
        );
    }
}