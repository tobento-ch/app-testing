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

use Tobento\Service\Migration\MigratorInterface;
use Tobento\Service\Migration\MigrationFactoryInterface;

trait MigrateDatabases
{
    /**
     * Migration databases.
     *
     * @return void
     */
    public function migrateDatabases(): void
    {
        $this->beforeMigrateDatabases();
        
        $app = $this->getApp();
        $migrator = $app->get(MigratorInterface::class);
        $migrationFactory = $app->get(MigrationFactoryInterface::class);
        
        foreach($migrator->getInstalled() as $migration) {
            try {
                $migration = $migrationFactory->createMigration($migration);
            } catch (\Throwable $e) {
                continue;
            }

            // first uninstall
            foreach($migration->uninstall()->all() as $action) {
                if ($action->type() === 'database') {
                    $action->process();
                }
            }
            
            // first install
            foreach($migration->install()->all() as $action) {
                if ($action->type() === 'database') {
                    $action->process();
                }
            }
        }
        
        $this->afterMigrateDatabases();
    }
    
    /**
     * Migrate databases on tearDown.
     *
     * @return void
     */
    protected function tearDownMigrateDatabases(): void
    {
        $this->migrateDatabases();
    }
    
    /**
     * Perform any work that should take place before the databases has started migrating.
     *
     * @return void
     */
    protected function beforeMigrateDatabases(): void
    {
        // ...
    }

    /**
     * Perform any work that should take place once the databases has finished migrating.
     *
     * @return void
     */
    protected function afterMigrateDatabases(): void
    {
        // ...
    }
}