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
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\FileStorage\Visibility;

class FileStorageTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        $app->boot(\Tobento\App\FileStorage\Boot\FileStorage::class);
        
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('foo', function (ResponserInterface $responser, StoragesInterface $storages) {
                $storage = $storages->get('uploads');
                $storage->write(path: 'foo.txt', content: 'Foo');
                return $responser->redirect(uri: 'bar');
            });

            $router->get('bar', function (ResponserInterface $responser, StoragesInterface $storages) {
                $storage = $storages->get('uploads');
                $storage->write(path: 'bar.txt', content: 'Bar');
                return 'bar';
            });
        });
        
        return $app;
    }

    public function testFileUpload()
    {
        // fakes:
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'upload',
            files: [
                'profile' => $http->getFileFactory()->createImage('profile.jpg', 640, 480),
            ],
        );
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('upload', function (ServerRequestInterface $request, StoragesInterface $storages) {    
                
                $file = $request->getUploadedFiles()['profile'];
                $storage = $storages->get('uploads');
                
                $storage->write(
                    path: $file->getClientFilename(),
                    content: $file->getStream()
                );
                
                $storage->copy(from: $file->getClientFilename(), to: 'copy/'.$file->getClientFilename());
                $storage->move(from: 'copy/'.$file->getClientFilename(), to: 'move/'.$file->getClientFilename());
                $storage->createFolder('foo/bar');
                $storage->setVisibility('foo/bar', Visibility::PRIVATE);
                
                return 'response';
            });
        });
        
        // run the app:
        $this->runApp();
        
        // assertions:
        $fileStorage->storage(name: 'uploads')
            ->assertCreated('profile.jpg')
            ->assertNotCreated('foo.jpg')
            ->assertExists('profile.jpg')
            ->assertNotExist('foo.jpg')
            ->assertCopied(from: 'profile.jpg', to: 'copy/profile.jpg')
            ->assertNotCopied(from: 'foo.jpg', to: 'copy/foo.jpg')
            ->assertMoved(from: 'copy/profile.jpg', to: 'move/profile.jpg')
            ->assertNotMoved(from: 'foo.jpg', to: 'copy/foo.jpg')
            ->assertFolderCreated('foo/bar')
            ->assertFolderNotCreated('baz')
            ->assertFolderExists('foo/bar')
            ->assertFolderNotExist('baz')
            ->assertVisibilityChanged('foo/bar');
    }
    
    public function testFollowingRedirects()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'foo');
        
        $http->response()->assertStatus(302);
        $fileStorage->storage(name: 'uploads')->assertCreated('foo.txt');
        
        $http->followRedirects()->assertStatus(200)->assertBodySame('bar');
        $this->fakeFileStorage()->storage(name: 'uploads')->assertCreated('bar.txt');
    }
}