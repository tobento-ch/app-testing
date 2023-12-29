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

namespace Tobento\App\Testing\App;

use Tobento\App\AppInterface;
use Tobento\App\Testing\App\Config;
use Tobento\Service\Config\ConfigInterface;
use Tobento\Service\Config\PhpLoader;
use Tobento\Service\Collection\Translations;
use PHPUnit\Framework\TestCase;

final class FakeConfig
{
    protected array $data = [];
    
    /**
     * Create a new FakeConfig.
     *
     * @param AppInterface $app
     */
    public function __construct(
        private AppInterface $app,
    ) {        
        $app->on(
            ConfigInterface::class,
            function(): ConfigInterface {

                $config = new Config(new Translations());

                $config->addLoader(
                    new PhpLoader($this->app->dirs()->sort()->group('config'))
                );
                
                $config->setTestData($this->data);
                
                return $config;
            }
        );
    }
    
    /**
     * Add a config value for the specified key.
     *
     * @param string $key
     * @param mixed $value
     * @return static $this
     */
    public function with(string $key, mixed $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }
    
    /**
     * Asserts that the specified config key exists.
     *
     * @param string $key
     * @return static $this
     */
    public function assertExists(string $key): static
    {
        TestCase::assertTrue(
            $this->config()->has($key),
            sprintf('Config doesn\'t have key [%s]', $key)
        );

        return $this;
    }
    
    /**
     * Asserts that the specified config key has the specified value.
     *
     * @param string $key
     * @param mixed $value
     * @return static $this
     */
    public function assertSame(string $key, mixed $value): static
    {
        $this->assertExists($key);

        TestCase::assertSame(
            $value,
            $this->config()->get($key),
            \sprintf('Config with key [%s] is not equal.', $key)
        );

        return $this;
    }
    
    /**
     * Returns the config.
     *
     * @return ConfigInterface
     */
    public function config(): ConfigInterface
    {
        return $this->app->get(ConfigInterface::class);
    }    
}