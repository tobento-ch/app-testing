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
use ReflectionClass;

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
    
    private array $fakers = [];
    
    protected function setUp(): void
    {
        parent::setUp();

        if (static::CREATE_APP_ON_SETUP) {
            $this->app = $this->createApp();
        }
        
        $this->fakers = [];
        
        $this->runTraits('setUp');
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        
        $this->runTraits('tearDown');
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
     * @param string $folder
     * @param bool $fresh
     * @return AppInterface
     */
    public function createTmpApp(string $rootDir, string $folder = 'app', bool $fresh = false): AppInterface
    {
        $rootDir = realpath($rootDir);
        
        if (! file_exists($rootDir.'/vendor')) {
            throw new \InvalidArgumentException(sprintf('Invalid $rootDir defined on %s', __METHOD__));
        }
        
        $appDir = $rootDir.'/tests/tmp/'.$folder.'/';
        
        if ($fresh) {
            (new Dir())->delete($appDir);
        }
        
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
     * Returns a new created app.
     *
     * @return AppInterface
     */
    public function newApp(): AppInterface
    {
        return $this->app = $this->createApp();
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
    
    /**
     * Returns true if faker exists, otherwise false.
     *
     * @param string $faker
     * @return bool
     */
    public function hasFaker(string $faker): bool
    {
        return isset($this->fakers[$faker]);
    }
    
    /**
     * Returns the faker.
     *
     * @param string $faker
     * @return object
     */
    public function getFaker(string $faker): object
    {
        return $this->fakers[$faker];
    }
    
    /**
     * Adds an faker
     *
     * @param FakerInterface $faker
     * @return object
     */
    public function addFaker(FakerInterface $faker): object
    {
        $this->fakers[$faker::class] = $faker;
        return $faker;
    }
    
    /**
     * Returns the fakers.
     *
     * @return array
     */
    public function getFakers(): array
    {
        return $this->fakers;
    }
    
    /**
     * Run traits.
     *
     * @param string $method
     * @return void
     */
    private function runTraits(string $method): void
    {
        $ref = new ReflectionClass(static::class);

        foreach ($ref->getTraits() as $trait) {
            if (method_exists($this, $name = $method . $trait->getShortName())) {
                $this->{$name}();
            }
        }

        while($parent = $ref->getParentClass()) {
            foreach ($parent->getTraits() as $trait) {
                if (method_exists($this, $name = $method . $trait->getShortName())) {
                    $this->{$name}();
                }
            }

            $ref = $parent;
        }
    }
}