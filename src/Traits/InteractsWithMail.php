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
use Tobento\App\Testing\Mail\FakeMail;

trait InteractsWithMail
{
    /**
     * Returns a new fake mail instance.
     *
     * @param null|AppInterface $app
     * @return FakeMail
     */
    final public function fakeMail(null|AppInterface $app = null): FakeMail
    {
        return new FakeMail($app ?: $this->getApp());
    }
}