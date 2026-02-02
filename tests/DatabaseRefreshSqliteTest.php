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

namespace Tobento\App\Testing\Test;

use Tobento\App\AppInterface;
use Tobento\App\Testing\Database\RefreshDatabases;
use Tobento\Service\Database\DatabasesInterface;
use Tobento\Service\Database\DatabaseInterface;
use Tobento\Service\Database\PdoDatabase;

class DatabaseRefreshSqliteTest extends \Tobento\App\Testing\TestCase
{
    use RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..', folder: 'app-sqlite');
        $app->boot(\Tobento\App\User\Boot\User::class);
        
        // example changing databases:
        $app->on(DatabasesInterface::class, static function (DatabasesInterface $databases) {
            $databases->addDefault('storage', 'sqlite-storage');
        });
        
        return $app;
    }

    public function testDatabaseRefreshes()
    {
        $auth = $this->fakeAuth();
        $this->bootingApp();
        
        $this->assertSame(0, $auth->getUserRepository()->count());
        
        $auth->getUserRepository()->create(['username' => 'tom']);
        
        $this->assertSame(1, $auth->getUserRepository()->count());
    }
    
    public function testDatabaseRefreshesAgain()
    {
        $auth = $this->fakeAuth();
        $this->bootingApp();
        
        $this->assertSame(0, $auth->getUserRepository()->count());
        
        $auth->getUserRepository()->create(['username' => 'tom']);
        
        $this->assertSame(1, $auth->getUserRepository()->count());
    }
}