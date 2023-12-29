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

use Tobento\Service\Session\SessionFactory as DefaultSessionFactory;
use Tobento\Service\Session\SessionInterface;
use Tobento\Service\Uri\BaseUriInterface;

/**
 * SessionFactory
 */
class SessionFactory extends DefaultSessionFactory
{
    /**
     * Create a new SessionFactory.
     *
     * @param null|BaseUriInterface $baseUri
     */
    public function __construct(
        //protected null|BaseUriInterface $baseUri = null,
    ) {}
    
    /**
     * Create a new Session.
     *
     * @param string $name
     * @param array $config
     * @return SessionInterface
     */
    public function createSession(string $name, array $config = []): SessionInterface
    {
        /*if ($this->baseUri && !isset($config['cookiePath'])) {
            $basePath = $this->baseUri->getPath();
            $config['cookiePath'] = rtrim($basePath, '/').'/';
        }*/
        
        return parent::createSession($name, $config);
    }
}