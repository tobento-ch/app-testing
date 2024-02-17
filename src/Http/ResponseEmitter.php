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

use Tobento\App\Http\ResponseEmitter as DefaultResponseEmitter;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * ResponseEmitter
 */
class ResponseEmitter extends DefaultResponseEmitter
{
    /**
     * Emit the specified response.
     *
     * @param ResponseInterface $response
     * @return void
     */
    public function emit(ResponseInterface $response): void
    {
        try {
            foreach($this->beforeHandlers as $handler) {
                $handler($response);
            }
            
        } catch (Throwable $t) {
            $response = $this->httpErrorHandlers->handleThrowable($t);
            
            if (! $response instanceof ResponseInterface) {
                throw $t;
            }
        }
    }
}