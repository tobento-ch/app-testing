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

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Tobento\Service\Session\SessionInterface;
use Tobento\Service\Routing\RouterInterface;
use Stringable;

/**
 * TestResponse
 */
class TestResponse implements Stringable
{
    /**
     * @var array $cookies
     */    
    protected array $cookies;
    
    /**
     * Create a new instance.
     *
     * @param ResponseInterface $response
     */
    public function __construct(
        protected ResponseInterface $response,
        protected null|SessionInterface $session = null,
        protected null|RouterInterface $router = null,
    ) {
        $this->cookies = $this->fetchCookies($response->getHeader('Set-Cookie'));
    }

    /**
     * Returns the response.
     *
     * @return ResponseInterface
     */
    public function response(): ResponseInterface
    {
        return $this->response;
    }
    
    /**
     * Asserts if the response is the same as the specified status code.
     *
     * @param int $status
     * @return static
     */
    public function assertStatus(int $status): static
    {
        TestCase::assertSame(
            $status,
            $this->response->getStatusCode(),
            sprintf(
                'Received response status [%s] but expected [%s].',
                $this->response->getStatusCode(),
                $status
            )
        );
        
        return $this;
    }
    
    /**
     * Asserts if the response body is the same as the specified body.
     *
     * @param string $body
     * @return static
     */
    public function assertBodySame(string $body): static
    {
        TestCase::assertSame(
            $body,
            (string)$this->response->getBody(),
            sprintf('Response is not same with [%s]', $body)
        );
        
        return $this;
    }
    
    /**
     * Asserts if the response body is the same as the specified body.
     *
     * @param string $body
     * @return static
     */
    public function assertBodyNotSame(string $body): static
    {
        TestCase::assertNotSame(
            $body,
            (string)$this->response->getBody(),
            sprintf('Response is same with [%s]', $body)
        );
        
        return $this;
    }
    
    /**
     * Asserts if the response body is the same as the specified body.
     *
     * @param string $body
     * @return static
     */
    public function assertBodyContains(string $body): static
    {
        TestCase::assertStringContainsString(
            $body,
            (string)$this->response->getBody(),
            sprintf('Response doesn\'t contain [%s]', $body)
        );
        
        return $this;
    }
    
    /**
     * Asserts if the response is the same as the specified content type.
     *
     * @param string $contentType The content type such as 'application/json'
     * @return static
     */
    public function assertContentType(string $contentType): static
    {
        $responseContentType = $this->response->getHeaderLine('Content-Type');
            
        TestCase::assertSame(
            $contentType,
            strstr($responseContentType, $responseContentType),
            sprintf(
                'Response does not contain content type [%s].',
                $contentType
            )
        );
        
        return $this;
    }
    
    /**
     * Asserts if the response has the same specified header (and value).
     *
     * @param string $name
     * @param null|string $value
     * @return static
     */
    public function assertHasHeader(string $name, null|string $value = null): static
    {
        TestCase::assertTrue(
            $this->response->hasHeader($name),
            sprintf('Response does not contain header with name [%s].', $name)
        );

        $headerValue = $this->response->getHeaderLine($name);

        if ($value) {
            TestCase::assertSame(
                $value,
                $headerValue,
                sprintf(
                    'Header [%s] was found, but value [%s] does not match [%s].',
                    $name,
                    $headerValue,
                    $value
                )
            );
        }

        return $this;
    }
    
    /**
     * Asserts if the response has not the specified header.
     *
     * @param string $name
     * @return static
     */
    public function assertHeaderMissing(string $name): static
    {
        TestCase::assertFalse(
            $this->response->hasHeader($name),
            sprintf('Response contains header with name [%s].', $name)
        );

        return $this;
    }
    
    /**
     * Asserts that the specified cookie exists.
     *
     * @param string $key
     * @return static
     */
    public function assertCookieExists(string $key): self
    {
        TestCase::assertArrayHasKey(
            $key,
            $this->cookies(),
            sprintf('Response doesn\'t have cookie with name [%s]', $key)
        );

        return $this;
    }

    /**
     * Asserts that the specified cookie does not exist.
     *
     * @param string $key
     * @return static
     */
    public function assertCookieMissed(string $key): self
    {
        TestCase::assertArrayNotHasKey(
            $key,
            $this->cookies(),
            \sprintf('Response has cookie with name [%s]', $key)
        );

        return $this;
    }

    /**
     * Asserts that the specified cookie has the specified value.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function assertCookieSame(string $key, mixed $value): self
    {
        $this->assertCookieExists($key);

        TestCase::assertSame(
            $value,
            $this->cookies[$key],
            \sprintf('Response cookie with name [%s] is not equal.', $key)
        );

        return $this;
    }
    
    /**
     * Asserts if the session has the same specified key (and value).
     *
     * @param string $key
     * @param null|string $value
     * @return static
     */
    public function assertHasSession(string $key, null|string $value = null): static
    {
        TestCase::assertTrue(
            $this->session?->has($key),
            sprintf('Session is missing expected key [%s].', $key)
        );

        if ($value) {
            $sessionValue = $this->session?->get($key);
            
            TestCase::assertSame(
                $value,
                $sessionValue,
                sprintf(
                    'Session key [%s] was found, but value [%s] does not match [%s].',
                    $key,
                    $sessionValue,
                    $value
                )
            );
        }

        return $this;
    }
    
    /**
     * Asserts if the session has not the specified key.
     *
     * @param string $key
     * @return static
     */
    public function assertSessionMissing(string $key): static
    {
        if (is_null($this->session)) {
            return $this;
        }
        
        TestCase::assertFalse(
            $this->session->has($key),
            sprintf('Session has unexpected key [%s].', $key)
        );

        return $this;
    }
    
    /**
     * Assert whether the response is redirecting to a given route.
     *
     * @param string $name
     * @param array $parameters
     * @return static
     */
    public function assertRedirectToRoute(string $name, array $parameters = []): static
    {
        if (is_null($this->router)) {
            return $this;
        }
        
        TestCase::assertTrue(
            $this->isRedirect(),
            'Response is not a redirection.'
        );
        
        $this->assertLocation((string)$this->router->url($name, $parameters));

        return $this;
    }
    
    /**
     * Assert that the current location header matches the given URI.
     *
     * @param string $uri
     * @return $this
     */
    public function assertLocation(string $uri)
    {
        $this->assertHasHeader('Location', $uri);
        
        return $this;
    }
    
    /**
     * Returns true if response is a redirect, otherwise false.
     *
     * @return bool
     */
    public function isRedirect(): bool
    {
        return in_array($this->response->getStatusCode(), [201, 301, 302, 303, 307, 308]);
    }

    /**
     * Returns the cookies.
     *
     * @return array
     */
    public function cookies(): array
    {
        return $this->cookies;
    }
    
    /**
     * Returns the response body.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->response->getBody();
    }
    
    /**
     * Fetches the cookies.
     *
     * @param array $header
     * @return array
     */
    private function fetchCookies(array $header): array
    {
        $result = [];
        
        foreach ($header as $line) {
            $cookie = explode('=', $line);
            $result[$cookie[0]] = rawurldecode(substr($cookie[1], 0, strpos($cookie[1], ';')));
        }

        return $result;
    }
}