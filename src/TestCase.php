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

use PHPUnit\Framework\TestCase as BaseTestCase;
use Tobento\App\AppInterface;
use Tobento\App\AppFactory;
use Tobento\Service\Filesystem\Dir;

abstract class TestCase extends BaseTestCase
{
    use Traits\InteractsWithConfig;
    use Traits\InteractsWithHttp;
    use Traits\InteractsWithUser;
    use Traits\InteractsWithFileStorage;
    use Traits\InteractsWithQueue;
    use Traits\InteractsWithEvent;
    use Traits\InteractsWithMail;
    use Traits\InteractsWithNotifier;
    
    public const CREATE_APP_ON_SETUP = true;

    private null|AppInterface $app = null;
    
    protected function setUp(): void
    {
        parent::setUp();

        if (static::CREATE_APP_ON_SETUP) {
            $this->app = $this->createApp();
        }
    }

    /**
     * Create a new App instance.
     *
     * @return AppInterface
     */
    abstract public function createApp(): AppInterface;
    
    /**
     * Create a new tmp App instance.
     *
     * @param string $rootDir
     * @return AppInterface
     */
    public function createTmpApp(string $rootDir): AppInterface
    {
        $rootDir = realpath($rootDir);
        
        if (! file_exists($rootDir.'/vendor')) {
            throw new \InvalidArgumentException(sprintf('Invalid $rootDir defined on %s', __METHOD__));
        }
        
        $appDir = $rootDir.'/tests/tmp/app/';
        
        (new Dir())->create($appDir);
        
        $app = (new AppFactory())->createApp();
        
        $app->dirs()
            ->dir($rootDir, 'root')
            ->dir($appDir, 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config', priority: 10)
            ->dir($app->dir('root').'vendor', 'vendor')
            // for testing only we add public within app dir.
            ->dir($app->dir('app').'public', 'public');
        
        return $app;
    }
    
    /**
     * Returns the app.
     *
     * @return AppInterface
     */
    public function getApp(): AppInterface
    {
        if (is_null($this->app)) {
            $this->app = $this->createApp();
        }
        
        return $this->app;
    }
    
    /**
     * Boots and returns the app.
     *
     * @return AppInterface
     */
    public function bootingApp(): AppInterface
    {
        return $this->getApp()->booting();
    }
    
    /**
     * Runs and returns the app.
     *
     * @return AppInterface
     */
    public function runApp(): AppInterface
    {
        $app = $this->bootingApp();
        $app->run();
        return $app;
    }
    
    /**
     * Deletes the app directory.
     *
     * @return void
     */
    public function deleteAppDirectory(): void
    {
        // Careful this will delete your app directory!
        
        if (!is_null($this->app)) {
            (new Dir())->delete($this->app->dir('app'));
        }
    }
}