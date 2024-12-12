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

namespace Tobento\App\Testing\FileStorage;

use Tobento\Service\Filesystem\Dir;

trait RefreshFileStorages
{
    /**
     * Refreshes the file storages.
     *
     * @return void
     */
    public function refreshFileStorages(): void
    {
        $this->beforeRefreshFileStorages();
        
        $app = $this->getApp();
        $rootDir = $app->dir('app').'storage/testing/file-storage/';
                
        (new Dir())->delete($rootDir);
        
        $this->afterRefreshFileStorages();
    }
    
    /**
     * Refreshes file storages on tearDown.
     *
     * @return void
     */
    protected function tearDownRefreshFileStorages(): void
    {
        $this->refreshFileStorages();
    }
    
    /**
     * Perform any work that should take place before the file storages has started refreshing.
     *
     * @return void
     */
    protected function beforeRefreshFileStorages(): void
    {
        // ...
    }

    /**
     * Perform any work that should take place once the file storages has finished refreshing.
     *
     * @return void     
     */
    protected function afterRefreshFileStorages(): void
    {
        // ...
    }
}