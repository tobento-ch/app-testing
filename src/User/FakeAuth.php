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

namespace Tobento\App\Testing\User;

use PHPUnit\Framework\TestCase;
use Tobento\App\Testing\FakerInterface;
use Tobento\App\AppInterface;
use Tobento\App\User\UserInterface;
use Tobento\App\User\UserRepositoryInterface;
use Tobento\App\User\PasswordHasherInterface;
use Tobento\App\User\Authentication\AuthInterface;
use Tobento\App\User\Authentication\AuthenticatedInterface;
use Tobento\App\User\Authentication\Token\TokenInterface;
use Tobento\App\User\Authentication\Token\TokenStorageInterface;
use Tobento\App\User\Authentication\Token\InMemoryStorage;
use Tobento\App\User\Authentication\Token\SessionStorage;
use Tobento\App\User\Authentication\Token\RepositoryStorage;
use Tobento\App\User\Authentication\Token\TokenRepository;
use Tobento\Service\Storage\StorageInterface;
use Tobento\Service\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Clock\ClockInterface;

final class FakeAuth implements FakerInterface
{
    private null|TokenStorageInterface $tokenStorage = null;
    
    private null|string $tokenStorageName = null;
    
    /**
     * Create a new FakeAuth.
     *
     * @param AppInterface $app
     */
    public function __construct(
        private AppInterface $app,
    ) {
        $app->on(
            TokenStorageInterface::class,
            function(TokenStorageInterface $tokenStorage): TokenStorageInterface {
                if (is_null($this->tokenStorageName)) {
                    return $tokenStorage;
                }
                
                return $this->createTokenStorage($this->tokenStorageName);
            }
        );
    }
    
    /**
     * Returns a new instance.
     *
     * @param AppInterface $app
     * @return static
     */
    public function new(AppInterface $app): static
    {
        $fakeAuth = new static($app);
        
        if (!is_null($this->tokenStorageName)) {
            $fakeAuth->tokenStorage($this->tokenStorageName);
        }
        
        $auth = $this->app->get(AuthInterface::class);
        
        if ($auth->hasAuthenticated()) {
            $fakeAuth->authenticatedAs($auth->getAuthenticated()->token());
        }
        
        return $fakeAuth;
    }
    
    /**
     * Set the authenticated user.
     *
     * @param UserInterface|TokenInterface $token
     * @return static $this
     */
    public function authenticatedAs(UserInterface|TokenInterface $token): static
    {
        if ($token instanceof UserInterface) {
            $token = $this->createToken($token);
        }
        
        if ($this->app->has(ServerRequestInterface::class)) {
            $request = $this->app->get(ServerRequestInterface::class);
            
            $this->app->set(
                ServerRequestInterface::class,
                $request->withHeader('X-Auth-Token', $token->id())
            )->prototype();
            
            return $this;
        }
        
        $this->app->on(
            ServerRequestInterface::class,
            function(ServerRequestInterface $request) use ($token): ServerRequestInterface {
                return $request->withHeader('X-Auth-Token', $token->id());
            }
        )->priority(-1500);
        
        return $this;
    }

    /**
     * Returns the authenticated user or null if none.
     *
     * @return null|AuthenticatedInterface
     */
    public function getAuthenticated(): null|AuthenticatedInterface
    {
        if (! $this->app->has(AuthInterface::class)) {
            return null;
        }
        
        return $this->app->get(AuthInterface::class)->getAuthenticated();
    }

    /**
     * Set the token storage.
     *
     * @param string $name
     * @return static $this
     */
    public function tokenStorage(string $name): static
    {
        $this->tokenStorageName = $name;
        return $this;
    }
    
    /**
     * Returns the token storage.
     *
     * @return null|TokenStorageInterface
     */
    public function getTokenStorage(): null|TokenStorageInterface
    {
        if (! $this->app->has(TokenStorageInterface::class)) {
            return null;
        }
        
        return $this->app->get(TokenStorageInterface::class);
    }
    
    /**
     * Returns the user repository.
     *
     * @return UserRepositoryInterface
     */
    public function getUserRepository(): UserRepositoryInterface
    {
        return $this->app->get(UserRepositoryInterface::class);
    }
    
    /**
     * Asserts that a user is authenticated.
     *
     * @return static
     */
    public function assertAuthenticated(): static
    {
        TestCase::assertTrue(
            !is_null($this->getAuthenticated()),
            'The user is not authenticated'
        );
        
        return $this;
    }
    
    /**
     * Asserts that a user is not authenticated.
     *
     * @return static
     */
    public function assertNotAuthenticated(): static
    {
        TestCase::assertTrue(
            is_null($this->getAuthenticated()),
            'The user is authenticated'
        );
        
        return $this;
    }
    
    /**
     * Returns the created token storage.
     *
     * @param string $name
     * @return TokenStorageInterface
     */
    private function createTokenStorage(string $name): TokenStorageInterface
    {
        switch ($name) {
            case 'session':
                return new SessionStorage(
                    session: $this->app->get(SessionInterface::class),
                    clock: $this->app->get(ClockInterface::class),
                    regenerateId: false,
                );
            case 'repository':
                return new RepositoryStorage(
                    clock: $this->app->get(ClockInterface::class),
                    repository: new TokenRepository(
                        storage: $this->app->get(StorageInterface::class)->new(),
                        table: 'auth_tokens',
                    ),
                );
            default:
                return new InMemoryStorage(
                    clock: $this->app->get(ClockInterface::class),
                );
        }
    }
    
    /**
     * Returns the created token.
     *
     * @param UserInterface $user
     * @return TokenInterface
     */
    private function createToken(UserInterface $user): TokenInterface
    {
        $tokenStorage = $this->app->get(TokenStorageInterface::class);
        
        return $tokenStorage->createToken(
            payload: ['userId' => $user->id(), 'passwordHash' => $user->password()],
            authenticatedVia: 'loginform',
            authenticatedBy: 'testing',
        );
    }
}