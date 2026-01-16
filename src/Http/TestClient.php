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

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class TestClient implements ClientInterface
{
    /**
     * All recorded outgoing HTTP requests.
     *
     * @var RequestInterface[]
     */
    protected array $requests = [];

    /**
     * The next response to return from sendRequest(), if any.
     *
     * @var null|ResponseInterface
     */
    protected null|ResponseInterface $nextResponse = null;

    /**
     * The next exception to throw from sendRequest(), if any.
     *
     * @var null|Throwable
     */
    protected null|Throwable $nextException = null;

    /**
     * Sends a request and records it.
     *
     * If a fake exception or fake response has been configured,
     * it will be used once and then cleared.
     *
     * @param RequestInterface $request The outgoing HTTP request.
     * @return ResponseInterface The simulated HTTP response.
     *
     * @throws Throwable If a fake exception has been configured.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        if ($this->nextException) {
            $e = $this->nextException;
            $this->nextException = null;
            throw $e;
        }

        if ($this->nextResponse) {
            $response = $this->nextResponse;
            $this->nextResponse = null;
            return $response;
        }

        return new Response(200);
    }

    /**
     * Returns all recorded outgoing HTTP requests.
     *
     * @return RequestInterface[] The list of recorded requests.
     */
    public function requests(): array
    {
        return $this->requests;
    }

    /**
     * Fakes the next response returned by sendRequest().
     *
     * @param ResponseInterface $response The response to return.
     * @return static
     */
    public function fakeResponse(ResponseInterface $response): static
    {
        $this->nextResponse = $response;
        return $this;
    }

    /**
     * Fakes an exception thrown by sendRequest().
     *
     * @param Throwable $e The exception to throw.
     * @return static
     */
    public function fakeException(Throwable $e): static
    {
        $this->nextException = $e;
        return $this;
    }

    /**
     * Asserts that at least one request was sent.
     *
     * If a callback is provided, it must return true for at least one request.
     *
     * @param null|callable $callback A filter callback: fn(RequestInterface $request): bool
     * @return static
     */
    public function assertSent(null|callable $callback = null): static
    {
        if ($callback === null) {
            TestCase::assertTrue(
                count($this->requests) > 0,
                'No HTTP requests were sent.'
            );
            return $this;
        }

        foreach ($this->requests as $request) {
            if ($callback($request)) {
                TestCase::assertTrue(true);
                return $this;
            }
        }

        TestCase::assertTrue(
            false,
            'No matching HTTP request was sent.'
        );

        return $this;
    }

    /**
     * Asserts that no request matching the callback was sent.
     *
     * @param callable $callback A filter callback: fn(RequestInterface $request): bool
     * @return static
     */
    public function assertNotSent(callable $callback): static
    {
        foreach ($this->requests as $request) {
            if ($callback($request)) {
                TestCase::assertTrue(
                    false,
                    'A matching HTTP request was sent.'
                );
            }
        }

        TestCase::assertTrue(true);
        return $this;
    }

    /**
     * Asserts the exact number of requests sent.
     *
     * @param int $count The expected number of requests.
     * @return static
     */
    public function assertSentCount(int $count): static
    {
        $actual = count($this->requests);

        TestCase::assertSame(
            $count,
            $actual,
            sprintf('Expected %d HTTP requests, but %d were sent.', $count, $actual)
        );

        return $this;
    }
    
    /**
     * Asserts that a request matching the callback was sent a specific number of times.
     *
     * @param callable $callback A filter callback: fn(RequestInterface $request): bool
     * @param int $times The expected number of matching requests.
     * @return static
     */
    public function assertSentTimes(callable $callback, int $times): static
    {
        $count = 0;

        foreach ($this->requests as $request) {
            if ($callback($request)) {
                $count++;
            }
        }

        TestCase::assertSame(
            $times,
            $count,
            sprintf(
                'Expected %d matching HTTP requests, but %d were sent.',
                $times,
                $count
            )
        );

        return $this;
    }
    
    /**
     * Asserts that no outgoing HTTP requests were sent.
     *
     * @return static
     */
    public function assertNothingSent(): static
    {
        $count = count($this->requests);

        TestCase::assertSame(
            0,
            $count,
            sprintf('Expected no HTTP requests, but %d were sent.', $count)
        );

        return $this;
    }

    /**
     * Returns the last recorded request.
     *
     * @return null|RequestInterface The last request, or null if none were sent.
     */
    public function lastRequest(): null|RequestInterface
    {
        return $this->requests[count($this->requests) - 1] ?? null;
    }
}