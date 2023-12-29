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
use Tobento\Service\Requester\RequesterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobento\Service\Cookie\CookieValuesInterface;

class HttpTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        $app->boot(\Tobento\App\Http\Boot\Cookies::class);
        return $app;
    }

    public function testGetRequestWithQuery()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'blog',
            query: ['sort' => 'desc'],
        );
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function (RequesterInterface $requester) {
                return 'blog:sort:'.$requester->input()->get('sort');
            });
        });
        
        $http->response()
            ->assertStatus(200)
            ->assertBodySame('blog:sort:desc');
    }
    
    public function testGetJsonRequest()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'blog',
            query: ['sort' => 'desc'],
            headers: ['Content-type' => 'application/json'],
        );
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function () {
                return ['page' => 'blog'];
            });
        });
        
        $http->response()
            ->assertStatus(200)
            ->assertBodySame(json_encode(['page' => 'blog']))
            ->assertBodyNotSame('bar')
            ->assertBodyContains('blog')
            ->assertContentType('application/json')
            ->assertHasHeader(name: 'Content-type')
            ->assertHasHeader(name: 'Content-type', value: 'application/json')
            ->assertHeaderMissing(name: 'Accept');
    }
    
    public function testPostRequest()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'blog',
            body: ['foo' => 'bar'],
        );
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('blog', function (RequesterInterface $requester) {
                return $requester->input()->all();
            });
        });
        
        $http->response()
            ->assertStatus(200)
            ->assertBodySame('{"foo":"bar"}');
    }
    
    public function testPostJsonRequest()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'blog')->json(['foo' => 'bar']);
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('blog', function (ServerRequestInterface $request) {
                return (string)$request->getBody();
            });
        });
        
        $http->response()
            ->assertStatus(200)
            ->assertBodySame('{"foo":"bar"}');
    }
    
    public function testHeadersRequest()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'blog',
            headers: [
                'X-Requested-With' => 'XMLHttpRequest',
            ],
        );
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('blog', function (ServerRequestInterface $request) {
                return $request->getHeaderLine('X-Requested-With');
            });
        });
        
        $http->response()->assertBodySame('XMLHttpRequest');
    }
    
    public function testCookiesRequest()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'blog',
            cookies: ['token' => 'xxxxxxx'],
        );
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('blog', function (ServerRequestInterface $request) {
                $cookieValues = $request->getAttribute(CookieValuesInterface::class);
                return $cookieValues->get('token');
            });
        });
        
        $http->response()->assertBodySame('xxxxxxx');
    }
    
    public function testFilesRequestWithImage()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'blog',
            files: [
                'foo' => $http->getFileFactory()->createImage('foo.jpg', 640, 480),
            ],
        );
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('blog', function (ServerRequestInterface $request) {
                $file = $request->getUploadedFiles()['foo'];
                return $file->getClientFilename();
            });
        });
        
        $http->response()->assertBodySame('foo.jpg');
    }
    
    public function testFilesRequestWithFile()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'blog',
            files: [
                'foo' => $http->getFileFactory()->createFile(
                    filename: 'foo.txt', 
                    kilobytes: 100,
                ),
            ],
        );
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('blog', function (ServerRequestInterface $request) {
                $file = $request->getUploadedFiles()['foo'];
                return $file->getClientFilename();
            });
        });
        
        $http->response()->assertBodySame('foo.txt');
    }
    
    public function testFilesRequestWithFileContent()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'blog',
            files: [
                'foo' => $http->getFileFactory()->createFileWithContent(
                    filename: 'foo.txt',
                    content: 'Hello world',
                    mimeType: 'text/plain'
                ),
            ],
        );
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('blog', function (ServerRequestInterface $request) {
                $file = $request->getUploadedFiles()['foo'];
                return $file->getClientFilename();
            });
        });
        
        $http->response()->assertBodySame('foo.txt');
    }
}