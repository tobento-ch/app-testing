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

namespace Tobento\App\Testing\Test\Addon;

use Tobento\App\AppInterface;
use Tobento\Service\Language\LanguagesInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;

class LanguagesAddonTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Addon\LanguagesAddon;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        $app->boot(\Tobento\App\Language\Boot\Language::class);
        
        $app->on(RouterInterface::class, function(RouterInterface $router) {
            $router->get('{?locale}/{page}', function(null|string $locale, string $page, LanguagesInterface $languages) {
                if (! $languages->has((string)$locale)) {
                    $locale = 'undefined';
                }
                return ['locale' => $locale, 'page' => $page];
            })->name('page');
        });
        
        return $app;
    }

    public function testLanguageAreSet()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'de/foo');
        
        $this->withLanguages('en', 'de', 'fr');
        $app = $this->bootingApp();
        $languages = $app->get(LanguagesInterface::class);
        
        $this->assertSame(['en', 'de', 'fr'], $languages->column('locale'));
        $this->assertSame('en', $languages->default()->locale());
    }
    
    public function testLanguageIsAvailable()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'de/foo');
        
        $this->withLanguages('en', 'de');
        
        $http->response()->assertStatus(200)->assertBodySame('{"locale":"de","page":"foo"}');
    }
    
    public function testLanguageIsNotAvailable()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'fr/foo');
        
        $this->withLanguages('en', 'de');
        
        $http->response()->assertStatus(200)->assertBodySame('{"locale":"undefined","page":"foo"}');
    }
}