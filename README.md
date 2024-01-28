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
        - [File Uploads](#file-uploads)
    - [Auth Tests](#auth-tests)
    - [File Storage Tests](#file-storage-tests)
    - [Queue Tests](#queue-tests)
    - [Event Tests](#event-tests)
    - [Mail Tests](#mail-tests)
    - [Notifier Tests](#notifier-tests)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app testing project running this command.

```
composer require tobento/app-testing
```

## Requirements

- PHP 8.0 or greater

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

Finally, write tests using the available fakers.

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
    serverParams: [],
    query: ['sort' => 'desc'],
    headers: ['Content-type' => 'application/json'],
    files: ['profile' => ...],
    body: ['foo' => 'bar'],
);
```

Or you may prefer using methods:

```php
$http = $this->fakeHttp();
$http->request(method: 'GET', uri: 'foo/bar', serverParams: [])
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
    ->assertBodySame('foo')
    ->assertBodyNotSame('bar')
    ->assertBodyContains('foo')
    ->assertContentType('application/json')
    ->assertHasHeader(name: 'Content-type')
    ->assertHasHeader(name: 'Content-type', value: 'application/json') // with value
    ->assertHeaderMissing(name: 'Content-type')
    ->assertCookieExists(key: 'token')
    ->assertCookieMissed(key: 'token')
    ->assertCookieSame(key: 'token', value: 'value');

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

## Auth Tests

If you have installed the [App User](https://github.com/tobento-ch/app-user) bundle you may test your application using the ```fakeAuth``` method.

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
        //$auth->tokenStorage('storage');
        
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

You may want to authenticate a user by creating a token:

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

**Example Seeding Users**

This is one possible way of seeding users for testing. You could also seed users by creating and using [Seeders](https://github.com/tobento-ch/app-seeding/#seeders).

```php
use Tobento\App\Testing\TestCase;
use Tobento\App\User\UserRepositoryInterface;
use Tobento\App\Seeding\User\UserFactory;

final class SomeAppTest extends TestCase
{
    public function tearDown(): void
    {
        // delete all users after each test:
        $this->getApp()->get(UserRepositoryInterface::class)->delete(where: []);
    }

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
use Tobento\App\AppInterface;
use Tobento\Service\Routing\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\FileStorage\Visibility;

class FileStorageTest extends \Tobento\App\Testing\TestCase
{
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
                $storage->setVisibility('foo/bar', Visibility::PRIVATE);
                
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
            ->assertFolderNotExist('baz')
            ->assertVisibilityChanged('foo/bar');
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

## Event Tests

If you have installed the [App Event](https://github.com/tobento-ch/app-event) bundle you may test your application using the ```fakeEvents``` method which records all events that are dispatched and provides assertion methods that you can use to check if specific events were dispatched and how many times. Currently, only [Default Events](https://github.com/tobento-ch/app-event#default-events) will be recorded. [Specific Events](https://github.com/tobento-ch/app-event#specific-events) are not supported yet.

Example using a tmp app:

```php
use Tobento\App\AppInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Event\EventsInterface;
use Psr\Http\Message\ServerRequestInterface;

class QueueTest extends \Tobento\App\Testing\TestCase
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
                
                $message = (new Message())
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

class MailTest extends \Tobento\App\Testing\TestCase
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

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)
- [Spiral Framework - Testing - For Inspiration and Code Snippets](https://github.com/spiral/testing)