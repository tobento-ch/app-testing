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

namespace Tobento\App\Testing\Addon;

use Psr\Http\Message\ServerRequestInterface;
use Tobento\App\AppInterface;
use Tobento\Apps\AppsInterface;

/**
 * Use this when the app under test internally drives another app
 * mid-request as an implementation detail (e.g. a backend app that boots
 * a frontend app to render a live preview/inline editor). That sub-app
 * has no real HTTP request of its own under the test harness, and would
 * otherwise resolve ServerRequestInterface via Http::boot()'s
 * fromGlobals() fallback, which is empty under CLI.
 *
 * This differs from the standard "testing a sub-app directly" pattern
 * (see tobento-ch/apps README), where the sub-app itself is returned
 * from createApp() and its routes are matched directly with no parent
 * involved — no forwarding needed there, since the fake request is
 * already written relative to that sub-app's own routes.
 */
trait ForwardRequestAddon
{
    /**
     * Forwards the currently faked request to the given sub-app, stripping
     * the given prefix so the sub-app's router sees a path relative to its
     * own routes.
     *
     * @param AppInterface $app The app under test.
     * @param string $appId The target sub-app's id, e.g. 'frontend'.
     * @param string $prefix The route prefix to strip, matching the route
     *                       pattern used to delegate to it (e.g. 'app/frontend/'),
     *                       not the sub-app's full computed slug — the fake
     *                       request path is relative to the route, not the
     *                       real mount path.
     * @return AppInterface The target sub-app instance, eagerly created.
     */
    protected function forwardRequestToApp(AppInterface $app, string $appId, string $prefix): AppInterface
    {
        $apps = $app->get(AppsInterface::class);

        if (! $apps->has($appId)) {
            throw new \RuntimeException(sprintf(
                'Cannot forward request to sub-app "%s": it is not registered.',
                $appId
            ));
        }

        $subApp = $apps->get($appId)->app();

        $subApp->on(ServerRequestInterface::class, function () use ($app, $prefix) {
            $request = $app->get(ServerRequestInterface::class);
            $uri = $request->getUri();
            $fullPath = ltrim($uri->getPath(), '/');

            return $request->withUri($uri->withPath(substr($fullPath, strlen($prefix))));
        });


        // The sub-app has its own, separate MiddlewareFactoryInterface — the
        // parent's CSRF-skip and session-middleware fakes (registered in
        // FakeHttp's constructor) never apply there. Without this, the
        // sub-app's real CSRF middleware runs against a fake request/body
        // with no valid token and rejects it (419), and its real session
        // middleware may throw on the faked, non-persisted session.
        $subApp->on(
            \Tobento\Service\Middleware\MiddlewareFactoryInterface::class,
            static function (\Tobento\Service\Middleware\MiddlewareFactoryInterface $factory) {
                $factory->replaceMiddleware(
                    \Tobento\Service\Session\Middleware\Session::class,
                    \Tobento\App\Testing\Http\SessionMiddleware::class
                );
                $factory->replaceMiddleware(\Tobento\Service\Form\Middleware\VerifyCsrfToken::class, null);
            }
        );
        
        return $subApp;
    }
}