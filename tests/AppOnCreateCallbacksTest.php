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
use Tobento\App\AppFactory;
use Tobento\App\Testing\TestCase;

class AppOnCreateCallbacksTest extends TestCase
{
    public function createApp(): AppInterface
    {
        return $this->createTmpApp(rootDir: __DIR__.'/..');
    }

    public function testCallbacksAreAppliedToInitialApp(): void
    {
        $called = false;

        $this->onCreateApp(function (AppInterface $app) use (&$called) {
            $called = true;
        });

        $this->getApp();

        $this->assertTrue($called);
    }

    public function testCallbacksAreAppliedToNewAppInstances(): void
    {
        $count = 0;

        $this->onCreateApp(function (AppInterface $app) use (&$count) {
            $count++;
        });

        $this->getApp();   // initial app
        $this->newApp();   // new app instance

        $this->assertSame(2, $count);
    }

    public function testCallbacksAreNotAppliedTwiceToSameApp(): void
    {
        $calls = 0;

        $this->onCreateApp(function (AppInterface $app) use (&$calls) {
            $calls++;
        });

        $this->getApp();   // first application

        $this->getApp();   // should NOT apply again

        $this->assertSame(1, $calls);
    }

    public function testCallbacksRegisteredAfterInitialAppAreAppliedImmediately(): void
    {
        $calls = 0;

        $this->getApp(); // initial app created

        $this->onCreateApp(function (AppInterface $app) use (&$calls) {
            $calls++;
        });

        $this->assertSame(1, $calls);
    }
}