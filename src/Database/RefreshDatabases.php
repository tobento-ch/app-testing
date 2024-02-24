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

namespace Tobento\App\Testing\Database;

use Tobento\Service\Database\DatabasesInterface;

trait RefreshDatabases
{
    /**
     * Refreshes the databases.
     *
     * @param null|array $names Refresh only specific databases by name
     * @return void
     */
    public function refreshDatabases(null|array $names = null): void
    {
        $this->beforeRefreshDatabases();
        
        $app = $this->getApp();
        $databases = $app->get(DatabasesInterface::class);
        $cleaner = new Cleaner();
        
        if (is_null($names)) {
            foreach($databases->names() as $name) {
                $cleaner->truncateDatabase($databases->get($name));
            }
            
            $this->afterRefreshDatabases();
            return;
        }

        foreach($databases->names() as $name) {
            $database = $databases->get($name);
            
            if (in_array($database->name(), $names)) {
                $cleaner->truncateDatabase($database);
            }
        }
        
        $this->afterRefreshDatabases();
    }
    
    /**
     * Refreshes databases on tearDown.
     *
     * @return void
     */
    protected function tearDownRefreshDatabases(): void
    {
        $this->refreshDatabases();
    }
    
    /**
     * Perform any work that should take place before the databases has started refreshing.
     *
     * @return void
     */
    protected function beforeRefreshDatabases(): void
    {
        // ...
    }

    /**
     * Perform any work that should take place once the databases has finished refreshing.
     *
     * @return void     
     */
    protected function afterRefreshDatabases(): void
    {
        // ...
    }
}