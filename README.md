# App Testing

Testing support for the app.

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [Getting Started](#getting-started)
    - [Config Tests](#config-tests)
    - [Http Tests](#http-tests)
        - [Request And Response](#request-and-response)
        - [Subsequent Requests](#subsequent-requests)
        - [File Uploads](#file-uploads)
        - [Crawl Response Content](#crawl-response-content)
        - [Json Response](#json-response)
        - [Response Macros](#response-macros)
        - [Refresh Session](#refresh-session)
        - [Dump Response](#dump-response)
        - [Response Emitter](#response-emitter)
    - [Http Client Tests](#http-client-tests)
    - [Auth Tests](#auth-tests)
        - [Authenticating Users](#authenticating-users)
        - [Adding Permissions](#adding-permissions)
        - [Seeding Users](#seeding-users)
    - [File Storage Tests](#file-storage-tests)
    - [Queue Tests](#queue-tests)
    - [Event Tests](#event-tests)
    - [Mail Tests](#mail-tests)
    - [Notifier Tests](#notifier-tests)
    - [Database Tests](#database-tests)
        - [Reset Databases](#reset-databases)
        - [Replace Databases](#replace-databases)
    - [Logging Tests](#logging-tests)
    - [Addons](#addons)
        - [Languages Addon](#languages-addon)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app testing project running this command.

```
composer require tobento/app-testing
```

## Requirements

- PHP 8.4 or greater

# Documentation

## Getting Started

To test your application extend the ```Tobento\App\Testing\TestCase``` class.

Next, use the ```createApp``` method to create the app for testing:

```php
use Tobento\App\Testing\TestCase;
use Tobento\App\AppInterface;

final class SomeAppTest extends TestCase
{
    public function createApp(): AppInterface
    {
        return require __DIR__.'/../app/app.php';
    }
}
```

**Tmp App**

You may use the ```createTmpApp``` method, to create an app for testing individual boots only.

```php
use Tobento\App\Testing\TestCase;
use Tobento\App\AppInterface;

final class SomeAppTest extends TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(SomeRoutes::class);
        // ...
        return $app;
    }
}
```

**Finally**, write tests using the available fakers.

By default, the app is not booted nor run yet. You will need to do it on each test method. Some faker methods will run the app automatically though such as the fakeHttp ```response``` method.

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testSomeRoute(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'foo/bar');
        
        // interact with the app:
        $app = $this->getApp();
        $app->booting();
        $app->run();
        // or
        $app = $this->bootingApp();
        $app->run();
        // or
        $app = $this->runApp();
        
        // assertions:
        $http->response()
            ->assertStatus(200)
            ->assertBodySame('foo');
    }
}
```

**Using `onCreateApp()`**

In addition to the `createApp()` method, you may use `onCreateApp()` inside your test methods to register custom implementations - such as routes, middleware, or service overrides - needed specifically for that test.

When `onCreateApp()` is executed, the application has not been booted yet. This allows you to use [`$app->on()`](https://github.com/tobento-ch/app#on) to modify or extend services before they are resolved. The callback you provide will be applied to every app instance created during the test, including those created internally during redirects.

```php
use Tobento\App\AppInterface;
use Tobento\App\Testing\TestCase;
use Tobento\Service\Routing\RouterInterface;

final class SomeAppTest extends TestCase
{
    public function testSomeRoute(): void
    {
        $this->onCreateApp(function(AppInterface $app) {
            $app->on(RouterInterface::class, function($router) {
                $router->get('example', fn() => 'ok');
            });
        });
    
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'example');
        
        // interact with the app:
        $app = $this->getApp();

        // assertions:
        $http->response()
            ->assertStatus(200)
            ->assertBodySame('ok');
    }
}
```

**App clearing**

When creating a tmp app, you may call the ```deleteAppDirectory``` method to delete the app directory.

```php
final class SomeAppTest extends TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(SomeRoutes::class);
        // ...
        return $app;
    }
    
    public function testSomeRoute(): void
    {
        // testing...
        
        $this->deleteAppDirectory();
    }
}
```

Or you may use the ```tearDown``` method:

```php
final class SomeAppTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        
        $this->deleteAppDirectory();
    }
}
```

## Config Tests

In some cases you may want to define or replace config values for specific tests:

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testSomething(): void
    {
        // faking:
        $config = $this->fakeConfig();
        $config->with('app.environment', 'testing');
        
        $this->runApp();
        
        // assertions:
        $config
            ->assertExists(key: 'app.environment')
            ->assertSame(key: 'app.environment', value: 'testing');
    }
}
```

## Http Tests

### Request And Response

If you have installed the [App Http](https://github.com/tobento-ch/app-http) bundle you may test your application using the ```fakeHttp``` method.

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testSomeRoute(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'user/1');
        
        // you may interact with the app:
        $app = $this->getApp();
        $app->booting();
        
        // assertions:
        $http->response()
            ->assertStatus(200)
            ->assertBodySame('foo');
    }
}
```

**Request method**

```php
$http = $this->fakeHttp();
$http->request(
    method: 'GET',
    uri: 'foo/bar',
    server: [],
    query: ['sort' => 'desc'],
    headers: ['Content-type' => 'application/json'],
    cookies: ['token' => 'xxxxxxx'],
    files: ['profile' => ...],
    body: ['foo' => 'bar'],
);
```

Or you may prefer using methods:

```php
$http = $this->fakeHttp();
$http->request(method: 'GET', uri: 'foo/bar', server: [])
    ->query(['sort' => 'desc'])
    ->headers(['Content-type' => 'application/json'])
    ->cookies(['token' => 'xxxxxxx'])
    ->files(['profile' => ...])
    ->body(['foo' => 'bar']);
```

Use the ```json``` method to create a request with the following headers:

* ```Accept: application/json```
* ```Content-type: application/json```

```php
$http = $this->fakeHttp();
$http->request('POST', 'foo/bar')->json(['foo' => 'bar']);
```

**Response method**

Calling the response method will automatically run the app.

```php
use Psr\Http\Message\ResponseInterface;

$http->response()
    ->assertStatus(200)
    ->assertBodySame(body: 'foo', escape: false)
    ->assertBodyNotSame(body: 'bar', escape: false)
    ->assertBodyContains(value: 'foo', escape: false)
    ->assertBodyNotContains(value: 'bar', escape: false)    
    ->assertContentType('application/json')
    ->assertHasHeader(name: 'Content-type')
    ->assertHasHeader(name: 'Content-type', value: 'application/json') // with value
    ->assertHeaderMissing(name: 'Content-type')
    ->assertCookieExists(key: 'token')
    ->assertCookieMissed(key: 'token')
    ->assertCookieSame(key: 'token', value: 'value')
    ->assertHasSession(key: 'key')
    ->assertHasSession(key: 'key', value: 'value') // with value
    ->assertSessionMissing(key: 'key')
    ->assertLocation(uri: 'uri')
    ->assertRedirectToRoute(name: 'route', parameters: []);

// you may get the response:
$response = $http->response()->response();
var_dump($response instanceof ResponseInterface::class);
// bool(true)
```

**withoutMiddleware**

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testSomeRoute(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->withoutMiddleware(Middleware::class, AnotherMiddleware::class);
        $http->request('GET', 'user/1');
        
        // assertions:
        $http->response()->assertStatus(200);
    }
}
```

**previousUri**

You may set a previous uri if your controller uses the previous uri to redirect back if an error occurs for example. 

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testSomeRoute(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->previousUri('users/create');
        $http->request('POST', 'users');
        
        // Or after booting using a named route:
        $app = $this->bootingApp();
        $http->previousUri($app->routeUrl('users.create'));
        
        // assertions:
        $http->response()
            ->assertStatus(301)
            ->assertLocation(uri: 'users/create');
    }
}
```

**Http Url**

You may sometimes wish to modify the http url in order to have relative urls for instance;

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testSomeRoute(): void
    {
        // faking:
        $config = $this->fakeConfig();
        $config->with('http.url', ''); // modify
        
        $http = $this->fakeHttp();
        $http->request('GET', 'orders');
        
        // assertions:
        $http->response()
            // if modified:
            ->assertNodeExists('a[href="orders/5"]')
            
            // if not modified:
            ->assertNodeExists('a[href="http://localhost/orders/5"]');
    }
}
```
        
### Subsequent Requests

After making a request, subsequent requests will create a new app. Any fakers from the first request will be rebooted.

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testSomeRoute(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'user/1');
        
        // assertions:
        $http->response()->assertStatus(200);
        
        // subsequent request:
        $http->request('GET', 'user/2');
        
        // assertions:
        $http->response()->assertStatus(200);
    }
}
```

**Following redirects**

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testSomeRoute(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'login');
        $auth = $this->fakeAuth();
        
        // you may interact with the app:
        $app = $this->bootingApp();
        $user = $auth->getUserRepository()->create(['username' => 'tom']);
        $auth->authenticatedAs($user);
        
        // assertions:
        $http->response()->assertStatus(302);
        $auth->assertAuthenticated();
        
        // following redirects:
        $http->followRedirects()->assertStatus(200);
        
        // fakers others than http, must be recalled.
        $this->fakeAuth()->assertAuthenticated();
        // $auth->assertAuthenticated(); // would be from previous request
    }
}
```

### File Uploads

You can use the file factory to generate dummy files or images for testing purposes:

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testSomeRoute(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: 'user/1',
            files: [
                // Create a fake image 640x480
                'profile' => $http->getFileFactory()->createImage('profile.jpg', 640, 480),
            ],
        );
        
        // assertions:
        $http->response()->assertStatus(200);
    }
}
```

**Create a fake image**

```php
$image = $http->getFileFactory()->createImage(
    filename: 'profile.jpg', 
    width: 640, 
    height: 480
);
```

**Create a fake file**

```php
$file = $http->getFileFactory()->createFile(
    filename: 'foo.txt'
);

// Create a file with size - 100kb
$file = $http->getFileFactory()->createFile(
    filename: 'foo.txt',
    kilobytes: 100
);

// Create a file with size - 100kb
$file = $http->getFileFactory()->createFile(
    filename: 'foo.txt',
    mimeType: 'text/plain'
);

$file = $http->getFileFactory()->createFileWithContent(
    filename: 'foo.txt', 
    content: 'Hello world',
    mimeType: 'text/plain'
);
```

### Crawl Response Content

You may crawl the response content using the [Symfony Dom Crawler](https://symfony.com/doc/current/components/dom_crawler.html).

```php
use Tobento\App\Testing\TestCase;
use Symfony\Component\DomCrawler\Crawler;

final class SomeAppTest extends TestCase
{
    public function testSomeRoute(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'user/comments');
        
        // assertions:
        $response = $http->response()->assertStatus(200);
        
        $this->assertCount(4, $response->crawl()->filter('.comment'));
        
        // returns the crawler:
        $crawler = $response()->crawl(); // Crawler
    }
}
```

**assertNodeExists**

```php
use Tobento\App\Testing\TestCase;
use Symfony\Component\DomCrawler\Crawler;

final class SomeAppTest extends TestCase
{
    public function testSomeRoute(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'user/comments');
        
        // assertions:
        $http->response()
            ->assertStatus(200)
            // Assert if a node exists:
            ->assertNodeExists('a[href="https://example.com"]')
            // Assert if a node exists based on a truth-test callback:
            ->assertNodeExists('h1', fn (Crawler $n): bool => $n->text() === 'Comments')
            // Assert if a node exists based on a truth-test callback:
            ->assertNodeExists('ul', static function (Crawler $n) {
                return $n->children()->count() === 2
                    && $n->children()->first()->text() === 'foo';
            }, 'There first ul child has no text "foo"');
    }
}
```

**assertNodeMissing**

```php
use Tobento\App\Testing\TestCase;
use Symfony\Component\DomCrawler\Crawler;

final class SomeAppTest extends TestCase
{
    public function testSomeRoute(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'user/comments');
        
        // assertions:
        $http->response()
            ->assertStatus(200)
            // Assert if a node is missing:
            ->assertNodeMissing('h1')
            // Assert if a node is missing based on a truth-test callback:
            ->assertNodeMissing('p', static function (Crawler $n) {
                return $n->attr('class') === 'error'
                    && $n->text() === 'Error Message';
            }, 'An unexpected error message was found');
    }
}
```

**Example Form Crawling**

```php
use Tobento\App\Testing\TestCase;
use Symfony\Component\DomCrawler\Crawler;

final class SomeAppTest extends TestCase
{
    public function testSomeRoute(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'user/comments');
        
        // assertions:
        $response = $http->response()->assertStatus(200);
        
        $form = $response->crawl(
            // you may pass a base uri or base href:
            uri: 'http://www.example.com',
            baseHref: null,
        )->selectButton('My super button')->form();
        
        $this->assertSame('POST', $form->getMethod());
    }
}
```

### JSON Response

You may test JSON responses using the ```assertJson``` method.

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testSomeRoute(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'api/user/1');
        
        // assertions:
        $http->response()
            ->assertStatus(200)
            ->assertJson([
                'id' => 1,
                'name' => 'John',
            ]);
    }
}
```

#### Assertable Json

You may use the ```AssertableJson``` class to fluently test JSON responses.

```php
use Tobento\App\Testing\TestCase;
use Tobento\App\Testing\Http\AssertableJson;

final class SomeAppTest extends TestCase
{
    public function testSomeRoute(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'api/users');
        
        // assertions:
        $http->response()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(items: 3)
                     ->has(key: '0', items: 2, value: AssertableJson $json) =>
                        $json->has(key: 'id', value: 1)
                             ->has(key: 'name', value: 'Tom')
                     )
            );
    }
}
```

**Assert Key**

Assert that the key exists:

```php
$json->has(key: 'name');

// using dot notation:
$json->has(key: 'address.firstname');
```

**Assert Value**

Assert that the value matches:

```php
$json->has(value: ['name' => 'Tom']);
```

**Assert Key And Value**

Assert that the value matches the key value:

```php
$json->has(key: 'name', value: 'Tom');
$json->has(key: 'address.firstname', value: 'John');
```

**Assert Items**

Asserts that the items count matches.

```php
$json->has(items: 3);

// with key
$json->has(key: 'colors', items: 3);
```

**Assert Passes**

Asserts that passes evaluates to true.

```php
$json->has(passes: true);
$json->has(passes: false); // will fail

// with key
$json->has(key: 'color', passes: fn (mixed $color) => is_string($color));
```

**Hasnt**

Use the ```hasnt``` method asserting the opposite of the ```has``` method.

```php
$json->hasnt(key: 'name');
$json->hasnt(key: 'address.firstname');
$json->hasnt(value: ['name' => 'Tom']);
$json->hasnt(key: 'address.firstname', value: 'John');
$json->hasnt(items: 3);
$json->hasnt(key: 'colors', items: 3);
$json->hasnt(value: 'name', passes: fn (mixed $color) => is_string($color));
```

### Response Macros

You may want to write convenience helpers to the test response using macros.

```php
use Tobento\App\Testing\Http\TestResponse;

final class SomeAppTest extends TestCase
{
    public function createApp(): AppInterface
    {
        // ...
        
        // we may add the macro here:
        TestResponse::macro('assertOk', function(): static {
            $this->assertStatus(200);                
            return $this;
        });

        return $app;
    }
    
    public function testSomeRoute(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'user/comments');
        
        // assertions:
        $http->response()->assertOk();
    }
}
```

### Refresh Session

You may refresh your session after each test by using ```RefreshSession``` trait:

```php
use Tobento\App\Testing\TestCase;
use Tobento\App\Testing\Http\RefreshSession;

final class SomeAppTest extends TestCase
{
    use RefreshSession;
    
    public function testSomething(): void
    {
        // ...
    }
}
```

### Dump Response

You may use the dump methods to examine and debug the PSR-7 response contents:
```php
// dumps response:
$http->response()->dump();

// dumps response body:
$http->response()->dumpBody();

// dumps response and stops execution:
$http->response()->dd();

// dumps response body and stops execution:
$http->response()->ddBody();
```

### Response Emitter

When your application emits a PSR-7 response directly-bypassing the normal HTTP kernel-you can fake the response emitter to capture and assert against the emitted response.

This is useful when testing:

- download handlers
- streaming responses
- services that manually emit responses
- middleware or components that call the emitter directly

You may fake the response emitter as follows:

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testOutgoingResponse(): void
    {
        // Fake the response emitter:
        $emitter = $this->fakeHttpResponseEmitter();

        // Interact with your application:
        $app = $this->getApp();
        $app->booting();

        // Code that triggers an emitted response...
        // e.g. $app->get(DownloadHandler::class)->handle($file);

        // Assert that a response was emitted:
        $response = $emitter->response();
        $response->assertStatus(200);

        // Assert headers:
        $response->assertHasHeader('Content-Type', 'application/pdf');

        // Assert body content:
        $response->assertBodySame(body: 'foo', escape: false);
    }
}
```

**Notes**

- The fake emitter automatically re-registers itself when the testing framework boots a new app instance  
  (for example during `followRedirects()`).

- `TestResponse` is fully supported, including:
  - status assertions  
  - header assertions  
  - body assertions  
  - JSON assertions  
  - macro support  

For a full list of available `TestResponse` methods, see the  
[Request And Response - Response Methods](#request-and-response) section.

## Http Client Tests

If you have installed the [App Http](https://github.com/tobento-ch/app-http) bundle, you may test outgoing HTTP requests made by your application using the `fakeHttpClient` method.

When your application makes outgoing HTTP requests using a PSR-18 client, you can intercept and assert those requests by faking the HTTP client.  
This allows you to test integrations such as webhooks, API calls, and external services without performing real network requests.

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testOutgoingRequest(): void
    {
        // Fake the PSR-18 client:
        $client = $this->fakeHttpClient();

        // Interact with your application:
        $app = $this->getApp();
        $app->booting();

        // Your code that triggers an outgoing HTTP request...
        // e.g. $app->get(Service::class)->sendWebhook();

        // Assert that at least one request was sent:
        $client->assertSent();

        // Assert that a specific request was sent:
        $client->assertSent(function ($request) {
            return $request->getMethod() === 'POST'
                && (string)$request->getUri() === 'https://example.com/webhook';
        });

        // Assert that no GET request was sent:
        $client->assertNotSent(function ($request) {
            return $request->getMethod() === 'GET';
        });

        // Assert the exact number of requests sent:
        $client->assertSentCount(1);
        
        // Assert that a POST request was sent exactly twice:
        $client->assertSentTimes(function ($request) {
            return $request->getMethod() === 'POST';
        }, 2);

        // Assert that no requests were sent:
        $client->assertNothingSent();
    }
}
```

**Fake Response**

You may fake the response returned by the next outgoing HTTP request.  
This is useful when testing how your application behaves when an external API returns a specific status code, headers, or body.

```php
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testFakeResponse(): void
    {
        // Fake the PSR-18 client:
        $client = $this->fakeHttpClient();

        // Interact with your application:
        $app = $this->getApp();
        $app->booting();

        // Fake the next response using a concrete PSR-7 implementation:
        $client->fakeResponse(new Response(201));

        // Or fake the next response using the PSR-17 response factory:
        $responseFactory = $app->get(ResponseFactoryInterface::class);
        $client->fakeResponse($responseFactory->createResponse(200));

        // Your code that triggers an outgoing HTTP request...
        // e.g. $app->get(Service::class)->sendWebhook();

        // Assert that a request was sent:
        $client->assertSent();

        // Assert that a specific request was sent:
        $client->assertSent(function($request) {
            return $request->getMethod() === 'POST';
        });
    }
}
```

**Fake Exception**

You may fake an exception that will be thrown on the next outgoing HTTP request.  
This is useful for testing how your application handles network failures, timeouts, or unexpected client errors.

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testFakeException(): void
    {
        // Fake the PSR-18 client:
        $client = $this->fakeHttpClient();

        // Fake an exception thrown on the next request:
        $client->fakeException(new \RuntimeException('Network error'));

        // Interact with your application:
        $app = $this->getApp();
        $app->booting();

        // Your code that triggers an outgoing HTTP request...
        // e.g. $app->get(Service::class)->sendWebhook();

        // Assert that a request was sent:
        $client->assertSent();

        // Assert that a specific request was sent:
        $client->assertSent(function ($request) {
            return $request->getMethod() === 'POST';
        });
    }
}
```

**Inspecting Requests**

You may inspect the recorded outgoing HTTP requests for debugging or for writing additional custom assertions.  
This is useful when you need to check headers, payloads, query parameters, or the order of requests.

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testInspectRequests(): void
    {
        // Fake the PSR-18 client:
        $client = $this->fakeHttpClient();

        // Interact with your application:
        $app = $this->getApp();
        $app->booting();

        // Your code that triggers outgoing HTTP requests...
        // e.g. $app->get(Service::class)->sendWebhook();

        // Get the last outgoing request:
        $last = $client->lastRequest();

        // Get all outgoing requests:
        $all = $client->requests();

        // Example: inspect headers or body
        // $last->getHeaderLine('Content-Type');
        // (string)$last->getBody();
    }
}
```

## Auth Tests

If you have installed the [App User](https://github.com/tobento-ch/app-user) bundle you may test your application using the ```fakeAuth``` method.

### Authenticating Users

The next two examples assumes you have already seeded test users in some way:

```php
use Tobento\App\Testing\TestCase;
use Tobento\App\User\UserRepositoryInterface;

final class SomeAppTest extends TestCase
{
    public function testSomeRouteWhileAuthenticated(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'profile');
        $auth = $this->fakeAuth();
        
        // you may change the token storage:
        //$auth->tokenStorage('inmemory');
        //$auth->tokenStorage('session');
        //$auth->tokenStorage('repository');
        
        // boot the app:
        $app = $this->bootingApp();
        
        // authenticate user:
        $userRepo = $app->get(UserRepositoryInterface::class);
        $user = $userRepo->findByIdentity(email: 'foo@example.com');
        // or:
        $user = $auth->getUserRepository()->findByIdentity(email: 'foo@example.com');
        
        $auth->authenticatedAs($user);
        
        // assertions:
        $http->response()->assertStatus(200);
        $auth->assertAuthenticated();
        // or:
        //$auth->assertNotAuthenticated();
    }
}
```

**Example With Token**

You may want to authenticate a user by creating a token manually:

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testSomeRouteWhileAuthenticated(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'profile');
        $auth = $this->fakeAuth();
        
        // boot the app:
        $app = $this->bootingApp();
        
        // authenticate user:
        $user = $auth->getUserRepository()->findByIdentity(email: 'foo@example.com');
        
        $token = $auth->getTokenStorage()->createToken(
            payload: ['userId' => $user->id(), 'passwordHash' => $user->password()],
            authenticatedVia: 'loginform',
            authenticatedBy: 'testing',
            //issuedAt: $issuedAt,
            //expiresAt: $expiresAt,
        );
        
        $auth->authenticatedAs($token);
        
        // assertions:
        $http->response()->assertStatus(200);
        $auth->assertAuthenticated();
    }
}
```

### Adding Permissions

If your application uses the ACL system, you may assign permissions to the authenticated user using the `addPermissions()` method.

Permissions are applied immediately and are also re-applied automatically when the testing framework boots a fresh app instance (for example during `followRedirects()`).

```php
use Tobento\App\Testing\TestCase;

final class SomeAppTest extends TestCase
{
    public function testRouteWithPermissions(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'dashboard');
        $auth = $this->fakeAuth();
        
        // boot the app:
        $app = $this->bootingApp();
        
        // authenticate user:
        $user = $auth->getUserRepository()->findByIdentity(email: 'editor@example.com');
        $auth->authenticatedAs($user);
        
        // add permissions:
        $auth->addPermissions([
            'articles',
            'articles.edit',
            'articles.own',
        ]);
        
        // assertions:
        $http->response()->assertStatus(200);
        $auth->assertAuthenticated();
    }
}
```

### Seeding Users

This is one possible way of seeding users for testing. You could also seed users by creating and using [Seeders](https://github.com/tobento-ch/app-seeding/#seeders).

```php
use Tobento\App\Testing\TestCase;
use Tobento\App\Testing\Database\RefreshDatabases;
use Tobento\App\User\UserRepositoryInterface;
use Tobento\App\User\AddressRepositoryInterface;
use Tobento\App\Seeding\User\UserFactory;

final class SomeAppTest extends TestCase
{
    use RefreshDatabases;

    public function testSomeRouteWhileAuthenticated(): void
    {
        // faking:
        $http = $this->fakeHttp();
        $http->request('GET', 'profile');
        $auth = $this->fakeAuth();
        
        // boot the app:
        $app = $this->bootingApp();
        
        // Create a user:
        $user = $auth->getUserRepository()->create(['username' => 'tom']);
        // or using the user factory:
        $user = UserFactory::new()->withUsername('tom')->withPassword('123456')->createOne();
        
        // authenticate user:
        $auth->authenticatedAs($user);
        
        // assertions:
        $http->response()->assertStatus(200);
        $auth->assertAuthenticated();
    }
}
```

You may check out the [User Seeding](https://github.com/tobento-ch/app-seeding#user-seeding) to learn more about the ```UserFactory::class```.

## File Storage Tests

If you have installed the [App File Storage](https://github.com/tobento-ch/app-file-storage) bundle you may test your application using the ```fakeFileStorage``` method which allows you to create a fake storage that mimics the behavior of a real storage, but doesn't actually send any files to the cloud. This way, you can test file uploads without worrying about accidentally sending real files to the cloud.

Example using a tmp app:

```php
use Psr\Http\Message\ServerRequestInterface;
use Tobento\App\AppInterface;
use Tobento\App\Testing\FileStorage\RefreshFileStorages;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Routing\RouterInterface;

class FileStorageTest extends \Tobento\App\Testing\TestCase
{
    // you may refresh all file storages after each test.
    use RefreshFileStorages;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\FileStorage\Boot\FileStorage::class);
        
        // routes: just for demo, normally done with a boot!
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
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
                
                return 'response';
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
                // Create a fake image 640x480
                'profile' => $http->getFileFactory()->createImage('profile.jpg', 640, 480),
            ],
        );
        
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
            ->assertFolderNotExist('baz');
    }
}
```

**Storage Method**

```php
$fileStorage = $this->fakeFileStorage();

// Get default storage:
$defaultStorage = $fileStorage->storage();

// Get specific storage:
$storage = $fileStorage->storage(name: 'uploads');
```

**Storages Method**

```php
use Tobento\Service\FileStorage\StoragesInterface;

$fileStorage = $this->fakeFileStorage();

// Get the storages:
$storages = $fileStorage->storages();

var_dump($storages instanceof StoragesInterface);
// bool(true)
```

## Queue Tests

If you have installed the [App Queue](https://github.com/tobento-ch/app-queue) bundle you may test your application using the ```fakeQueue``` method which allows you to create a fake queue to prevent jobs from being sent to the actual queue.

Example using a tmp app:

```php
use Tobento\App\AppInterface;
use Tobento\Service\Routing\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\Job;

class QueueTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Queue\Boot\Queue::class);
        
        // routes: just for demo, normally done with a boot!
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('queue', function (ServerRequestInterface $request, QueueInterface $queue) {    

                $queue->push(new Job(
                    name: 'sample',
                    payload: ['key' => 'value'],
                ));            
                
                return 'response';
            });
        });
        
        return $app;
    }

    public function testIsQueued()
    {
        // fakes:
        $fakeQueue = $this->fakeQueue();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'queue');
        
        // run the app:
        $this->runApp();
        
        // assertions:
        $fakeQueue->queue(name: 'sync')
            ->assertPushed('sample')
            ->assertPushed('sample', function (JobInterface $job): bool {
                return $job->getPayload()['key'] === 'value';
            })
            ->assertNotPushed('sample:foo')
            ->assertNotPushed('sample', function (JobInterface $job): bool {
                return $job->getPayload()['key'] === 'invalid';
            })
            ->assertPushedTimes('sample', 1);
        
        $fakeQueue->queue(name: 'file')
            ->assertNothingPushed();
    }
}
```

**Clear Queue**

Sometimes it may be useful to clear the queue using the ```clearQueue``` method:

```php
use Tobento\Service\Queue\QueueInterface;

$fakeQueue->clearQueue(
    queue: $fakeQueue->queue(name: 'file') // QueueInterface
);
```

**Run Jobs**

Sometimes it may be useful to run jobs using the ```runJobs``` method:

```php
$fakeQueue->runJobs($fakeQueue->queue(name: 'sync')->getAllJobs());
```

## Event Tests

If you have installed the [App Event](https://github.com/tobento-ch/app-event) bundle you may test your application using the ```fakeEvents``` method which records all events that are dispatched and provides assertion methods that you can use to check if specific events were dispatched and how many times. Currently, only [Default Events](https://github.com/tobento-ch/app-event#default-events) will be recorded. [Specific Events](https://github.com/tobento-ch/app-event#specific-events) are not supported yet.

Example using a tmp app:

```php
use Tobento\App\AppInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Event\EventsInterface;
use Psr\Http\Message\ServerRequestInterface;

class EventTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Event\Boot\Event::class);
        
        // routes: just for demo, normally done with a boot!
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('registration', function (ServerRequestInterface $request, EventsInterface $events) {    

                $events->dispatch(new UserRegistered(username: 'tom'));
                
                return 'response';
            });
        });
        
        return $app;
    }

    public function testDispatchesEvent()
    {
        // fakes:
        $events = $this->fakeEvents();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'registration');
        
        // run the app:
        $this->runApp();
        
        // assertions:
        $events
            // Assert if an event dispatched one or more times:
            ->assertDispatched(UserRegistered::class)
            // Assert if an event dispatched one or more times based on a truth-test callback:
            ->assertDispatched(UserRegistered::class, static function(UserRegistered $event): bool {
                return $event->username === 'tom';
            })
            // Asserting if an event were dispatched a specific number of times:
            ->assertDispatchedTimes(UserRegistered::class, 1)
            // Asserting an event were not dispatched:
            ->assertNotDispatched(FooEvent::class)
            // Asserting an event were not dispatched based on a truth-test callback:
            ->assertNotDispatched(UserRegistered::class, static function(UserRegistered $event): bool {
                return $event->username !== 'tom';
            })
            // Assert if an event has a listener attached to it:
            ->assertListening(UserRegistered::class, SomeListener::class);
    }
}
```

## Mail Tests

If you have installed the [App Mail](https://github.com/tobento-ch/app-mail) bundle you may test your application using the ```fakeMail``` method which allows you to create a fake mailer to prevent messages from being sent.

Example using a tmp app:

```php
use Tobento\App\AppInterface;
use Tobento\Service\Routing\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobento\Service\Mail\MailerInterface;
use Tobento\Service\Mail\Message;
use Tobento\Service\Mail\Address;
use Tobento\Service\Mail\Parameter;

class MailTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\View\Boot\View::class); // to support message templates
        $app->boot(\Tobento\App\Mail\Boot\Mail::class);
        
        // routes: just for demo, normally done with a boot!
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('mail', function (ServerRequestInterface $request, MailerInterface $mailer) {
                
                $message = new Message()
                    ->from('from@example.com')
                    ->to(new Address('to@example.com', 'Name'))
                    ->subject('Subject')
                    ->html('<p>Lorem Ipsum</p>');

                $mailer->send($message);
                
                return 'response';
            });
        });
        
        return $app;
    }

    public function testMessageMailed()
    {
        // fakes:
        $fakeMail = $this->fakeMail();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'mail');
        
        // run the app:
        $this->runApp();
        
        // assertions:
        $fakeMail->mailer(name: 'default')
            ->sent(Message::class)
            ->assertFrom('from@example.com', 'Name')
            ->assertHasTo('to@example.com', 'Name')
            ->assertHasCc('cc@example.com', 'Name')
            ->assertHasBcc('bcc@example.com', 'Name')
            ->assertReplyTo('replyTo@example.com', 'Name')
            ->assertSubject('Subject')
            ->assertTextContains('Lorem')
            ->assertHtmlContains('Lorem')
            ->assertIsQueued()
            ->assertHasParameter(
                Parameter\File::class,
                fn (Parameter\File $f) => $f->file()->getBasename() === 'image.jpg'
            )
            ->assertTimes(1);
    }
}
```

## Notifier Tests

If you have installed the [App Notifier](https://github.com/tobento-ch/app-notifier) bundle you may test your application using the ```fakeNotifier``` method which allows you to create a fake notifier to prevent notification messages from being sent.

Example using a tmp app:

```php
use Tobento\App\AppInterface;
use Tobento\Service\Routing\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobento\Service\Notifier\NotifierInterface;
use Tobento\Service\Notifier\ChannelMessagesInterface;
use Tobento\Service\Notifier\Notification;
use Tobento\Service\Notifier\Recipient;

class NotifierTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Notifier\Boot\Notifier::class);
        
        // routes: just for demo, normally done with a boot!
        $this->getApp()->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('notify', function (ServerRequestInterface $request, NotifierInterface $notifier) {
                
                $notification = new Notification(
                    subject: 'New Invoice',
                    content: 'You got a new invoice for 15 EUR.',
                    channels: ['mail', 'sms', 'storage'],
                );

                // The receiver of the notification:
                $recipient = new Recipient(
                    email: 'mail@example.com',
                    phone: '15556666666',
                    id: 5,
                );

                $notifier->send($notification, $recipient);
                
                return 'response';
            });
        });
        
        return $app;
    }

    public function testNotified()
    {
        // fakes:
        $notifier = $this->fakeNotifier();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'notify');
        
        // run the app:
        $this->runApp();
        
        // assertions:
        $notifier
            // Assert if a notification is sent one or more times:
            ->assertSent(Notification::class)
            // Assert if a notification is sent one or more times based on a truth-test callback:
            ->assertSent(Notification::class, static function(ChannelMessagesInterface $messages): bool {
                $notification = $messages->notification();
                $recipient = $messages->recipient();
                
                // you may test the sent messages
                $mail = $messages->get('mail')->message();
                $this->assertSame('New Invoice', $mail->getSubject());

                return $notification->getSubject() === 'New Invoice'
                    && $messages->successful()->channelNames() === ['mail', 'sms', 'storage']
                    && $messages->get('sms')->message()->getTo()->phone() === '15556666666'
                    && $recipient->getAddressForChannel('mail', $notification)?->email() === 'mail@example.com';
            })
            // Asserting if a notification were sent a specific number of times:
            ->assertSentTimes(Notification::class, 1)
            // Asserting a notification were not sent:
            ->assertNotSent(Notification::class)
            // Asserting a notification were not sent based on a truth-test callback:
            ->assertNotSent(Notification::class, static function(ChannelMessagesInterface $messages): bool {
                $notification = $messages->notification();
                return $notification->getSubject() === 'New Invoice';
            })
            // Asserting that no notifications were sent:
            ->assertNothingSent();
    }
}
```

## Database Tests

If you have installed the [App Database](https://github.com/tobento-ch/app-database) bundle you may interact with your databases.

### Reset Databases

There are two strategies to reset your databases:

**Refresh Strategy**

This strategy cleans your database after each test.

```php
use Tobento\App\Testing\TestCase;
use Tobento\App\Testing\Database\RefreshDatabases;

final class SomeAppTest extends TestCase
{
    use RefreshDatabases;
    
    public function testSomething(): void
    {
        // ...
    }
}
```

**Migrate Strategy**

This strategy cleans your database after each test using migrations.

```php
use Tobento\App\Testing\TestCase;
use Tobento\App\Testing\Database\MigrateDatabases;

final class SomeAppTest extends TestCase
{
    use MigrateDatabases;
    
    public function testSomething(): void
    {
        // ...
    }
}
```

### Replace Databases

You may replace your database to test different databases.

**Example replacing the default storage database:**

```php
use Tobento\App\Testing\TestCase;
use Tobento\App\AppInterface;
use Tobento\App\Testing\Database\RefreshDatabases;
use Tobento\Service\Database\DatabasesInterface;
use Tobento\Service\Database\DatabaseInterface;
use Tobento\Service\Database\PdoDatabase;

final class SomeAppTest extends TestCase
{
    use RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..', folder: 'app-mysql');
        $app->boot(\Tobento\App\User\Boot\User::class);
        
        // example changing databases:
        $app->on(DatabasesInterface::class, static function (DatabasesInterface $databases) {
            // change default storage database:
            $databases->addDefault('storage', 'mysql-storage');
            
            // you may change the mysql database:
            $databases->register(
                'mysql',
                function(string $name): DatabaseInterface {
                    return new PdoDatabase(
                        new \PDO(
                            dsn: 'mysql:host=localhost;dbname=app_testing',
                            username: 'root',
                            password: '',
                        ),
                        $name
                    );
                }
            );
        });
        
        return $app;
    }
    
    public function testSomething(): void
    {
        // ...
    }
}
```

## Logging Tests

If you have installed the [App Logging](https://github.com/tobento-ch/app-logging) bundle you may test your application using the ```fakeLogging``` method which allows you to create a fake logger to prevent logging with the actual logger.

Example using a tmp app:

```php
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tobento\App\AppInterface;
use Tobento\App\Testing\Logging\LogEntry;
use Tobento\Service\Routing\RouterInterface;

class LoggingTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Logging\Boot\Logging::class);
        
        // routes: just for demo, normally done with a boot!
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->post('login', function (ServerRequestInterface $request, LoggerInterface $logger) {    
                
                $logger->info('User logged in.', ['user_id' => 3]);
                
                return 'response';
            });
        });
        
        return $app;
    }

    public function testIsLogged()
    {
        // fakes:
        $fakeLogging = $this->fakeLogging();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'login');
        
        // run the app:
        $this->runApp();
        
        // assertions using default logger:
        $fakeLogging->logger()
            ->assertLogged(fn (LogEntry $log): bool =>
                $log->level === 'info'
                && $log->message === 'User logged in.' 
                && $log->context === ['user_id' => 3]
            )
            ->assertNotLogged(
                fn (LogEntry $log): bool => $log->level === 'error'
            )
            ->assertLoggedTimes(
                fn (LogEntry $log): bool => $log->level === 'info',
                1
            );
        
        // specific logger:
        $fakeLogging->logger(name: 'error')
            ->assertNothingLogged();
    }
}
```

## Addons

### Languages Addon

If you have installed the [App Language](https://github.com/tobento-ch/app-language) bundle you may test your application using the ```LanguagesAddon``` trait which allows you to register languages using the ```withLanguages``` method. The first locale is the default language.

```php
use Tobento\App\AppInterface;

class LanguagesTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Addon\LanguagesAddon;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Language\Boot\Language::class);
        
        // you may define languages globally:
        $this->withLanguages('en', 'de', 'fr');
        
        return $app;
    }

    public function testWithLanguages()
    {
        // fakes:
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'login');
        
        // you may define it for each tests:
        $this->withLanguages('en', 'de');
        
        $http->response()->assertStatus(200);
    }
}
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)
- [Spiral Framework - Testing - For Inspiration and Code Snippets](https://github.com/spiral/testing)