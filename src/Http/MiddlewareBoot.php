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
 
namespace Tobento\App\Testing\Http;

use Tobento\App\Boot;
use Tobento\App\Http\Boot\Middleware;
use Tobento\Service\Middleware\MiddlewareDispatcherInterface;

/**
 * Middleware boot.
 */
class MiddlewareBoot extends Middleware
{
    /**
     * @var array
     */    
    protected array $replace = [
        \Tobento\Service\Session\Middleware\Session::class => \Tobento\App\Testing\Http\SessionMiddleware::class,
    ];
    
    /**
     * Add a middleware or multiple.
     *
     * @param mixed $middleware Any middleware.
     * @return static $this
     */
    public function add(mixed ...$middleware): static
    {
        foreach($middleware as $key => $m) {
            if (is_string($m) && isset($this->replace[$m])) {
                $middleware[$key] = $this->replace[$m];
            }
        }

        $this->app->get(MiddlewareDispatcherInterface::class)->add(...$middleware);
        
        return $this;
    }
}