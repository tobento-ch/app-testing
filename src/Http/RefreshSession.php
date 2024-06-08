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

namespace Tobento\App\Testing\Http;

use Tobento\Service\Session\SessionInterface;

trait RefreshSession
{
    /**
     * Refreshes the session.
     *
     * @return void
     */
    public function refreshSession(): void
    {
        $this->beforeRefreshSession();
        $this->getApp()->get(SessionInterface::class)->deleteAll();
        $this->afterRefreshSession();
    }
    
    /**
     * Refreshes session on tearDown.
     *
     * @return void
     */
    protected function tearDownRefreshSession(): void
    {
        $this->refreshSession();
    }
    
    /**
     * Perform any work that should take place before the session has started refreshing.
     *
     * @return void
     */
    protected function beforeRefreshSession(): void
    {
        // ...
    }

    /**
     * Perform any work that should take place once the session has finished refreshing.
     *
     * @return void     
     */
    protected function afterRefreshSession(): void
    {
        // ...
    }
}