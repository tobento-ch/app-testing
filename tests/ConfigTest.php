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
use Tobento\Service\Routing\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobento\Service\Config\ConfigInterface;

class ConfigTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        return $app;
    }

    public function testConfig()
    {
        $config = $this->fakeConfig();
        $config->with('http.hosts', ['example.de']);
        $http = $this->fakeHttp();
        
        $this->runApp();

        $config
            ->assertExists(key: 'http.hosts')
            ->assertSame(key: 'http.hosts', value: ['example.de']);
    }
}