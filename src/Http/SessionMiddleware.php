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

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tobento\Service\Session\SessionInterface;
use Tobento\Service\Session\SessionStartException;
use Tobento\Service\Session\SessionSaveException;
use Tobento\Service\Session\SessionExpiredException;
use Tobento\Service\Session\SessionValidationException;

/**
 * Starting, saving and adding Session to request.
 */
class SessionMiddleware implements MiddlewareInterface
{
    /**
     * Create a new Session.
     *
     * @param SessionInterface $session
     */
    public function __construct(
        protected SessionInterface $session,
    ) {}
    
    /**
     * Process the middleware.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws SessionStartException
     * @throws SessionValidationException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {        
        try {
            $this->session->start();
        } catch (SessionExpiredException $e) {
            $this->session->destroy();
            
            // You might to restart session and regenerate id
            // on the current request.
            // $this->session->start();
            // $this->session->regenerateId();

            // Or you might send a message to the user instead.
        } catch (SessionStartException $e) {
            // do nothing for testing
        }
        
        $request = $request->withAttribute(SessionInterface::class, $this->session);

        $response = $handler->handle($request);

        try {
            $this->session->save();
        } catch (SessionSaveException $e) {
            // do nothing for testing
        }
        
        return $response;
    }
}