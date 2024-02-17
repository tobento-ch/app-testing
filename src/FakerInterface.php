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

namespace Tobento\App\Testing;

use Tobento\App\AppInterface;

interface FakerInterface
{
    /**
     * Returns a new instance.
     *
     * @param AppInterface $app
     * @return static
     */
    public function new(AppInterface $app): static;
}