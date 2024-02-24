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

use PHPUnit\Framework\ExpectationFailedException;
use Tobento\App\AppInterface;
use Tobento\App\Testing\Database\MigrateDatabases;

class DatabaseMigrateTest extends \Tobento\App\Testing\TestCase
{
    use MigrateDatabases;

    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\User\Boot\User::class);
        return $app;
    }

    public function testDatabaseMigrating()
    {
        $auth = $this->fakeAuth();
        $this->bootingApp();
        
        $this->assertSame(0, $auth->getUserRepository()->count());
        
        $auth->getUserRepository()->create(['username' => 'tom']);
        
        $this->assertSame(1, $auth->getUserRepository()->count());
    }
    
    public function testDatabaseMigratingAgain()
    {
        $auth = $this->fakeAuth();
        $this->bootingApp();
        
        $this->assertSame(0, $auth->getUserRepository()->count());
        
        $auth->getUserRepository()->create(['username' => 'tom']);
        
        $this->assertSame(1, $auth->getUserRepository()->count());
    }
}