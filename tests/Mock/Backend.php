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

namespace Tobento\App\Testing\Test\Mock;

use Tobento\Apps\AppBoot;

class Backend extends AppBoot
{
    protected const APP_BOOT = [
        \Tobento\App\Http\Boot\Routing::class,
    ];

    protected const APP_ID = 'backend';

    protected const SLUG = 'backend';
}