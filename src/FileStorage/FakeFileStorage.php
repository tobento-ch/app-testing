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

use Tobento\App\AppInterface;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\Storages;
use Tobento\Service\FileStorage\Flysystem;
use Tobento\Service\Filesystem\Dir;
use Nyholm\Psr7\Factory\Psr17Factory;

final class FakeFileStorage
{
    /**
     * Create a new FakeFileStorage.
     *
     * @param AppInterface $app
     */
    public function __construct(
        private AppInterface $app,
    ) {
        $app->on(
            StoragesInterface::class,
            function(StoragesInterface $storages): StoragesInterface {
                
                $rootDir = $this->app->dir('app').'storage/testing/file-storage/';
                
                (new Dir())->delete($rootDir);
                
                $fakeStorages = new Storages();
                
                foreach($storages->getDefaults() as $default => $storage) {
                    $fakeStorages->addDefault($default, $storage);
                }

                foreach($storages->names() as $name) {
                    $fakeStorages->add($this->createStorage($name, $rootDir));
                }
                
                return $fakeStorages;
            }
        );
    }

    /**
     * Returns the storages.
     *
     * @return StoragesInterface
     */
    public function storages(): StoragesInterface
    {
        return $this->app->get(StoragesInterface::class);
    }
    
    /**
     * Returns the storage.
     *
     * @param null|string $name
     * @return StorageInterface
     */
    public function storage(null|string $name = null): StorageInterface
    {
        if (is_string($name)) {
            return $this->app->get(StoragesInterface::class)->get($name);
        }
        
        return $this->app->get(StorageInterface::class);
    }
    
    /**
     * Create a storage.
     *
     * @param string $name
     * @param string $rootDir
     * @return StorageInterface
     */
    private function createStorage(string $name, string $rootDir): StorageInterface
    {
        $filesystem = new \League\Flysystem\Filesystem(
            adapter: new \League\Flysystem\Local\LocalFilesystemAdapter(
                location: $rootDir.$name
            )
        );
        
        return new TestStorage(
            name: $name,
            flysystem: $filesystem,
            fileFactory: new Flysystem\FileFactory(
                flysystem: $filesystem,
                streamFactory: new Psr17Factory()
            ),
        );
    }
}