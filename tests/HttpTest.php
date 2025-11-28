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
use Tobento\App\Http\Middleware\SecurePolicyHeaders;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Cookie\CookieValuesInterface;
use Tobento\Service\Session\SessionInterface;
use Tobento\Service\Support\Str;
use Tobento\Service\Uri\PreviousUriInterface;
use Symfony\Component\DomCrawler\Crawler;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;

class HttpTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\Session::class);
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
            ->assertBodyNotContains('foo')
            ->assertContentType('application/json')
            ->assertHasHeader(name: 'Content-type')
            ->assertHasHeader(name: 'Content-type', value: 'application/json')
            ->assertHeaderMissing(name: 'Accept');
    }
    
    public function testBodyWithEscaping()
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
                return Str::esc('you\'ve been redirected to this page.');
            });
        });
        
        $http->response()
            ->assertStatus(200)
            ->assertBodySame('you\'ve been redirected to this page.', true)
            ->assertBodyNotSame('bar', true)
            ->assertBodyContains('you\'ve been', true)
            ->assertBodyNotContains('foo', true);
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
    
    public function testSession()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function (ServerRequestInterface $request) {
                $session = $request->getAttribute(SessionInterface::class);
                $session->set('key', 'value');
                $session->set('foo', 1);
                return 'blog';
            });
        });
        
        $http->response()
            ->assertStatus(200)
            ->assertHasSession('key')
            ->assertHasSession('key', 'value')
            ->assertHasSession('foo', 1)
            ->assertSessionMissing('baz');
    }
    
    public function testRequestWithoutMiddleware()
    {
        $http = $this->fakeHttp();
        $http->withoutMiddleware(SecurePolicyHeaders::class);
        $http->request(method: 'GET', uri: 'blog');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function (ServerRequestInterface $request) {
                return $request->getHeaderLine('X-Requested-With');
            })->middleware(SecurePolicyHeaders::class);
        });
        
        $http->response()->assertHeaderMissing('Strict-Transport-Security');
    }
    
    public function testPreviousUri()
    {
        $http = $this->fakeHttp();
        $http->previousUri('prev-uri');
        $http->request(method: 'POST', uri: 'redirects-to-prev-uri');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router, AppInterface $app): void {
            $router->post('redirects-to-prev-uri', function (PreviousUriInterface $previousUri) {
                return (string)$previousUri;
            });
        });
        
        $http->response()->assertBodySame('prev-uri');
    }
    
    public function testPreviousUriUsingUri()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'redirects-to-prev-uri');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router, AppInterface $app): void {
            $router->post('redirects-to-prev-uri', function (PreviousUriInterface $previousUri) {
                return (string)$previousUri;
            });
        });
        
        $app = $this->bootingApp();
        $http->previousUri($app->get(UriFactoryInterface::class)->createUri('prev-uri'));
        
        $http->response()->assertBodySame('prev-uri');
    }
    
    public function testPreviousUriUsingRouteUrl()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'redirects-to-prev-uri');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router, AppInterface $app): void {
            $router->post('redirects-to-prev-uri', function (PreviousUriInterface $previousUri) {
                return 'redirects';
            });
            
            $router->post('foo', function () {
                return 'foo response';
            })->name('foo');
        });
        
        $app = $this->bootingApp();
        $http->previousUri($app->routeUrl('foo'));
        
        $http->response()->assertBodySame('redirects');
    }
    
    public function testAssertLocation()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'redirects');
        
        $app = $this->bootingApp();
        $app->get(RouterInterface::class)->get('redirects', function (ResponserInterface $responser) {
            return $responser->redirect(uri: 'redirects-to-article');
        });
        
        $http->response()->assertLocation('redirects-to-article');
    }
    
    public function testAssertRedirectToRoute()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'redirects');
        
        $app = $this->bootingApp();
        $app->get(RouterInterface::class)->get('redirects', function (ResponserInterface $responser, RouterInterface $router) {
            return $responser->redirect(uri: $router->url('foo', ['id' => '5']));
        });
        
        $app->get(RouterInterface::class)->get('foo/{id}', function ($id) {
            return 'foo/'.$id;
        })->name('foo');
        
        $http->response()->assertRedirectToRoute('foo', ['id' => '5']);
    }
    
    public function testAssertRedirectToRouteWithPreviousUri()
    {
        $http = $this->fakeHttp();
        $http->previousUri('blog');
        $http->request(method: 'GET', uri: 'redirects');
        
        $app = $this->bootingApp();
        $app->get(RouterInterface::class)->get(
            'redirects',
            function (ResponserInterface $responser, RouterInterface $router, PreviousUriInterface $previousUri) {
                return $responser->redirect(uri: $previousUri);
            }
        );
        
        $app->get(RouterInterface::class)->get('blog', function () {
            return 'blog';
        })->name('blog');
        
        $http->response()->assertRedirectToRoute('blog');
    }
    
    public function testCrawling()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'blog',
        );
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function (RequesterInterface $requester) {
                $html = <<<'HTML'
                <!DOCTYPE html>
                <html>
                    <body>
                        <p class="message">Hello World!</p>
                        <p>Hello Crawler!</p>
                    </body>
                </html>
                HTML;
                return $html;
            });
        });
        
        $response = $http->response()->assertStatus(200);
        $this->assertSame('Hello World!', $response->crawl()->filter('body > p')->first()->text());
    }
    
    public function testCrawlingForm()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function (RequesterInterface $requester) {
                $html = <<<'HTML'
                <!DOCTYPE html>
                <html>
                    <body>
                        <h1>Title</h1>
                        <form method="POST">
                            <button id="my-super-button" type="submit">My super button</button>
                        </form>
                    </body>
                </html>
                HTML;
                return $html;
            });
        });
        
        $response = $http->response()->assertStatus(200);
        $form = $response->crawl(uri: 'http://www.example.com')->selectButton('My super button')->form();
        $this->assertSame('POST', $form->getMethod());
    }

    public function testAssertNodeExists()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function (RequesterInterface $requester) {
                $html = <<<'HTML'
                <!DOCTYPE html>
                <html>
                    <body>
                        <h1>Title</h1>
                        <ul><li>foo</li><li>bar</li></ul>
                        <a href="https://example.com">Link</p>
                    </body>
                </html>
                HTML;
                return $html;
            });
        });
        
        $http->response()
            ->assertStatus(200)
            ->assertNodeExists('h1', fn (Crawler $n): bool => $n->text() === 'Title')
            ->assertNodeExists('ul', static function (Crawler $n) {
                return $n->children()->count() === 2
                    && $n->children()->first()->text() === 'foo';
            })
            ->assertNodeExists('a[href="https://example.com"]');
    }
    
    public function testAssertNodeExistsThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected "h1" node was not found.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function (RequesterInterface $requester) {
                return '<!DOCTYPE html><html><body><h2>Title</h2></body></html>';
            });
        });
        
        $http->response()->assertNodeExists('h1');
    }
    
    public function testAssertNodeExistsWithCallbackThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected "h1" node was not found.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function (RequesterInterface $requester) {
                return '<!DOCTYPE html><html><body><h1>Title</h1></body></html>';
            });
        });
        
        $http->response()->assertNodeExists('h1', fn (Crawler $n): bool => $n->text() === 'Foo');
    }
    
    public function testAssertNodeExistsThrowsExceptionUsingMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Custom message');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function (RequesterInterface $requester) {
                return '<!DOCTYPE html><html><body><h2>Title</h2></body></html>';
            });
        });
        
        $http->response()->assertNodeExists(selector: 'h1', message: 'Custom message');
    }
    
    public function testAssertNodeMissing()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function (RequesterInterface $requester) {
                return '<!DOCTYPE html><html><body><h1>Title</h1></body></html>';
            });
        });
        
        $http->response()
            ->assertStatus(200)
            ->assertNodeMissing('h1', fn (Crawler $n): bool => $n->text() === 'Foo')
            ->assertNodeMissing('h2');
    }
    
    public function testAssertNodeMissingThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The unexpected "h1" node was found.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function (RequesterInterface $requester) {
                return '<!DOCTYPE html><html><body><h1>Title</h1></body></html>';
            });
        });
        
        $http->response()->assertNodeMissing('h1');
    }
    
    public function testAssertNodeMissingWithCallbackThrowsException()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The unexpected "h1" node was found.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function (RequesterInterface $requester) {
                return '<!DOCTYPE html><html><body><h1>Title</h1></body></html>';
            });
        });
        
        $http->response()->assertNodeMissing('h1', fn (Crawler $n): bool => $n->text() === 'Title');
    }
    
    public function testAssertNodeMissingThrowsExceptionUsingMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Custom message');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function (RequesterInterface $requester) {
                return '<!DOCTYPE html><html><body><h1>Title</h1></body></html>';
            });
        });
        
        $http->response()->assertNodeMissing(selector: 'h1', message: 'Custom message');
    }
    
    public function testMacro()
    {
        \Tobento\App\Testing\Http\TestResponse::macro(
            'assertOk',
            function(): static {
                $this->assertStatus(200);                
                return $this;
            }
        );
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'blog');
        
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('blog', function (RequesterInterface $requester) {
                return 'blog';
            });
        });
        
        $http->response()->assertOk();
    }
}