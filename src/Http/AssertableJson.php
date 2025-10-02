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

use Closure;
use PHPUnit\Framework\TestCase;
use Tobento\Service\Collection\Arr;

class AssertableJson
{
    /**
     * Create a new AssertableJson instance.
     *
     * @param array $data
     * @param null|string $path
     */
    public function __construct(
        private array $data,
        private null|string $path = null,
    ) {}

    /**
     * Asserts if the given parameters matching the data.
     *
     * @param mixed ...$parameters
     * @return static $this
     */
    public function has(mixed ...$parameters): static
    {
        if (array_key_exists('key', $parameters)) {
            $this->assertHasKey($parameters['key']);
        }
        
        if (array_key_exists('value', $parameters)) {
            $this->assertHasValue($parameters['value'], $parameters['key'] ?? null);
        }
        
        if (array_key_exists('items', $parameters)) {
            $this->assertHasItems($parameters['items'], $parameters['key'] ?? null);
        }
        
        if (array_key_exists('passes', $parameters)) {
            $this->assertHasPasses($parameters['passes'], $parameters['key'] ?? null);
        }
        
        return $this;
    }
    
    /**
     * Asserts if the given parameters not matching the data.
     *
     * @param mixed ...$parameters
     * @return static $this
     */
    public function hasnt(mixed ...$parameters): static
    {
        if (
            array_key_exists('key', $parameters)
            && count($parameters) === 1
        ) {
            $this->assertHasntKey($parameters['key']);
        }
        
        if (array_key_exists('value', $parameters)) {
            $this->assertHasntValue($parameters['value'], $parameters['key'] ?? null);
        }
        
        if (array_key_exists('items', $parameters)) {
            $this->assertHasntItems($parameters['items'], $parameters['key'] ?? null);
        }
        
        if (array_key_exists('passes', $parameters)) {
            $this->assertHasPasses($parameters['passes'], $parameters['key'] ?? null);
        }
        
        return $this;
    }
    
    /**
     * Returns the json data as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Returns the absolute path to the given key.
     *
     * @param null|string|int $key
     * @return string
     */
    private function path(null|string|int $key): string
    {
        $key = (string)$key;
        
        if (is_null($this->path)) {
            return $key;
        }

        return trim(implode('.', [$this->path, $key]), '.');
    }
    
    /**
     * Asserts that the key exists.
     *
     * @param mixed $key
     * @return void
     */
    private function assertHasKey(mixed $key): void
    {
        if (!is_string($key) && !is_int($key)) {
            throw new \InvalidArgumentException('Json key parameter must be a string or int');
        }
        
        TestCase::assertTrue(
            Arr::has($this->data, $key),
            sprintf('Json property [%s] does not exist.', $this->path($key))
        );
    }
    
    /**
     * Asserts that the value matches.
     *
     * @param mixed $value
     * @param null|string|int $key
     * @return void
     */
    private function assertHasValue(mixed $value, null|string|int $key): void
    {
        if (!is_null($key)) {
            $this->assertHasKey($key);
        }
        
        $path = $this->path($key);
        $data = is_null($key) ? $this->data : Arr::get($this->data, $key);
        
        if ($value instanceof Closure) {
            if (!is_array($data)) {
                TestCase::fail(sprintf('Json property [%s] does not match the expected value.', $path));
                return;
            }
            
            $value(new AssertableJson($data, $path));
            return;
        }
        
        $message = $path === ''
            ? 'Json object does not match the expected value.'
            : sprintf('Json property [%s] does not match the expected value.', $path);
        
        TestCase::assertSame($value, $data, $message);
    }
    
    /**
     * Asserts that the items count matches.
     *
     * @param mixed $items
     * @param null|string|int $key
     * @return void
     */
    private function assertHasItems(mixed $items, null|string|int $key): void
    {
        if (!is_int($items)) {
            throw new \InvalidArgumentException('Json items parameter must be an int');
        }
        
        $path = $this->path($key);
        $data = is_null($key) ? $this->data : Arr::get($this->data, $key);
        
        if (!is_array($data)) {
            TestCase::fail(sprintf('Json property [%s] does not have any items.', $path));
            return;
        }
        
        $message = $path === ''
            ? sprintf('Json object does have %d item(s) instead of %d item(s).', count($data), $items)
            : sprintf('Json property [%s] does have %d item(s) instead of %d item(s).', $path, count($data), $items);
        
        TestCase::assertCount($items, $data, $message);
    }
    
    /**
     * Asserts that the passes evaluates to true.
     *
     * @param mixed $passes
     * @param null|string|int $key
     * @return void
     */
    private function assertHasPasses(mixed $passes, null|string|int $key): void
    {
        if (!is_bool($passes) && !is_callable($passes)) {
            throw new \InvalidArgumentException('Json passes parameter must be a bool or callable');
        }
        
        $path = $this->path($key);
        $data = is_null($key) ? $this->data : Arr::get($this->data, $key);
        
        if (is_callable($passes)) {
            $valid = $passes($data);
        } else {
            $valid = $passes;
        }
        
        $message = $path === ''
            ? 'Json object does not pass the given test.'
            : sprintf('Json property [%s] does not pass the given test.', $path);
        
        TestCase::assertTrue($valid, $message);
    }
    
    /**
     * Asserts that the key not exists.
     *
     * @param mixed $key
     * @return void
     */
    private function assertHasntKey(mixed $key): void
    {
        if (!is_string($key) && !is_int($key)) {
            throw new \InvalidArgumentException('Json key parameter must be a string or int');
        }
        
        TestCase::assertFalse(
            Arr::has($this->data, $key),
            sprintf('Json property [%s] does exist.', $this->path($key))
        );
    }
    
    /**
     * Asserts that the value not matches.
     *
     * @param mixed $value
     * @param null|string|int $key
     * @return void
     */
    private function assertHasntValue(mixed $value, null|string|int $key): void
    {
        if (!is_null($key)) {
            $this->assertHasKey($key);
        }
        
        $path = $this->path($key);
        $data = is_null($key) ? $this->data : Arr::get($this->data, $key);
        
        $message = $path === ''
            ? 'Json object does match the unexpected value.'
            : sprintf('Json property [%s] does match the unexpected value.', $path);
        
        TestCase::assertNotSame($value, $data, $message);
    }
    
    /**
     * Asserts that the items count not matches.
     *
     * @param mixed $items
     * @param null|string|int $key
     * @return void
     */
    private function assertHasntItems(mixed $items, null|string|int $key): void
    {
        if (!is_int($items)) {
            throw new \InvalidArgumentException('Json items parameter must be an int');
        }
        
        $path = $this->path($key);
        $data = is_null($key) ? $this->data : Arr::get($this->data, $key);
        
        if (!is_array($data)) {
            TestCase::fail(sprintf('Json property [%s] does not have items at all.', $path));
            return;
        }
        
        $message = $path === ''
            ? sprintf('Json object does have %d item(s).', $items)
            : sprintf('Json property [%s] does have %d item(s).', $path, $items);
        
        TestCase::assertFalse(count($data) === $items, $message);
    }
}