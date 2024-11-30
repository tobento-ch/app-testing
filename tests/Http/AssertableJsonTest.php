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

namespace Tobento\App\Testing\Test\Http;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Tobento\App\Testing\Http\AssertableJson;

class AssertableJsonTest extends TestCase
{
    public function testToArrayMethod()
    {
        $json = new AssertableJson(['name' => 'John']);
        $this->assertSame(['name' => 'John'], $json->toArray());
    }
    
    public function testHasWithoutParams()
    {
        $json = new AssertableJson(['name' => 'John']);
        $json->has();
        $this->assertTrue(true);
    }
    
    public function testHasKey()
    {
        $json = new AssertableJson(['name' => 'John']);
        $json->has(key: 'name');
        $this->assertTrue(true);
    }
    
    public function testHasKeyThrowsIfMissing()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [foo] does not exist.');
        
        $json = new AssertableJson(['name' => 'John']);
        $json->has(key: 'foo');
    }
    
    public function testHasKeyNested()
    {
        $json = new AssertableJson(['address' => ['name' => 'John']]);
        $json->has(key: 'address.name');
        $this->assertTrue(true);
    }
    
    public function testHasKeyNestedThrowsIfMissing()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [address.foo] does not exist.');
        
        $json = new AssertableJson(['address' => ['name' => 'John']]);
        $json->has(key: 'address.foo');
    }
    
    public function testHasValue()
    {
        $json = new AssertableJson(['name' => 'John']);
        $json->has(value: ['name' => 'John']);
        $this->assertTrue(true);
    }
    
    public function testHasValueThrowsIfNotMatching()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json object does not match the expected value.');
        
        $json = new AssertableJson(['name' => 'John']);
        $json->has(value: ['name' => 'Sam']);
    }
    
    public function testHasValueClosure()
    {
        $json = new AssertableJson(['name' => 'John']);
        $json->has(value: fn (AssertableJson $j) =>
            $j->has(key: 'name')
        );
        
        $this->assertTrue(true);
    }
    
    public function testHasValueClosureThrowsIfFailing()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [foo] does not exist.');
        
        $json = new AssertableJson(['name' => 'John']);
        $json->has(value: fn (AssertableJson $j) =>
            $j->has(key: 'foo')
        );
    }
    
    public function testHasKeyValue()
    {
        $json = new AssertableJson(['name' => 'John']);
        $json->has(key: 'name', value: 'John');
        $this->assertTrue(true);
    }
    
    public function testHasKeyValueThrowsIfValueNotMatching()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [name] does not match the expected value.');
        
        $json = new AssertableJson(['name' => 'John']);
        $json->has(key: 'name', value: 'Sam');
    }
    
    public function testHasKeyValueThrowsIfKeyMissing()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [foo] does not exist.');
        
        $json = new AssertableJson(['name' => 'John']);
        $json->has(key: 'foo', value: 'John');
    }
    
    public function testHasKeyValueNested()
    {
        $json = new AssertableJson(['address' => ['name' => 'John']]);
        $json->has(key: 'address.name', value: 'John');
        $this->assertTrue(true);
    }
    
    public function testHasKeyValueNestedThrowsIfValueNotMatching()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [address.name] does not match the expected value.');
        
        $json = new AssertableJson(['address' => ['name' => 'John']]);
        $json->has(key: 'address.name', value: 'Sam');
    }
    
    public function testHasKeyValueNestedThrowsIfKeyMissing()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [address.foo] does not exist.');
        
        $json = new AssertableJson(['address' => ['name' => 'John']]);
        $json->has(key: 'address.foo', value: 'John');
    }
    
    public function testHasKeyValueClosure()
    {
        $json = new AssertableJson(['address' => ['name' => 'John']]);
        $json->has(key: 'address', value: fn (AssertableJson $j) =>
            $j->has(value: ['name' => 'John'])
        );
        $this->assertTrue(true);
        
        $json->has(key: 'address', value: fn (AssertableJson $j) =>
            $j->has(key: 'name', value: 'John')
        );
        $this->assertTrue(true);
    }
    
    public function testHasKeyValueClosureThrowsIfFailing()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [address.name] does not match the expected value.');
        
        $json = new AssertableJson(['address' => ['name' => 'John']]);
        $json->has(key: 'address', value: fn (AssertableJson $j) =>
            $j->has(key: 'name', value: 'Foo')
        );
    }
    
    public function testHasKeyValueClosureThrowsIfNotArrayValue()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Json property [name] does not match the expected value.');
        
        $json = new AssertableJson(['name' => 'John']);
        $json->has(key: 'name', value: fn (AssertableJson $j) =>
            $j->has(value: 'John')
        );
    }
    
    public function testHasItems()
    {
        $json = new AssertableJson(['name' => 'John']);
        $json->has(items: 1);
        $this->assertTrue(true);
    }
    
    public function testHasItemsThrowsIfNotMatching()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json object does have 1 item(s) instead of 2 item(s).');
        
        $json = new AssertableJson(['name' => 'John']);
        $json->has(items: 2);
    }
    
    public function testHasItemsWithKey()
    {
        $json = new AssertableJson(['name' => ['foo', 'bar']]);
        $json->has(key: 'name', items: 2);
        $this->assertTrue(true);
    }
    
    public function testHasItemsWithKeyThrowsIfNotMatching()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [name] does have 2 item(s) instead of 1 item(s).');
        
        $json = new AssertableJson(['name' => ['foo', 'bar']]);
        $json->has(key: 'name', items: 1);
    }
    
    public function testHasItemsWithKeyThrowsIfNotArray()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Json property [name] does not have any items.');
        
        $json = new AssertableJson(['name' => '']);
        $json->has(key: 'name', items: 1);
    }
    
    public function testHasPasses()
    {
        (new AssertableJson([]))->has(passes: true);
        (new AssertableJson([]))->has(passes: fn () => true);
        $this->assertTrue(true);
    }
    
    public function testHasPassesThrowsIfFalse()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json object does not pass the given test.');
        
        (new AssertableJson([]))->has(passes: false);
    }
    
    public function testHasPassesUsingClosureThrowsIfFalse()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json object does not pass the given test.');
        
        (new AssertableJson([]))->has(passes: fn (array $data) => false);
    }
    
    public function testHasPassesClosureWithKeyPassesValue()
    {
        (new AssertableJson(['name' => 'John']))
            ->has(key: 'name', passes: fn (mixed $name) => is_string($name));
        $this->assertTrue(true);
    }

    public function testHasntWithoutParams()
    {
        $json = new AssertableJson(['name' => 'John']);
        $json->hasnt();
        $this->assertTrue(true);
    }
    
    public function testHasntKey()
    {
        $json = new AssertableJson(['name' => 'John']);
        $json->hasnt(key: 'foo');
        $this->assertTrue(true);
    }
    
    public function testHasntKeyThrowsIfExists()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [name] does exist.');
        
        $json = new AssertableJson(['name' => 'John']);
        $json->hasnt(key: 'name');
    }
    
    public function testHasntKeyNested()
    {
        $json = new AssertableJson(['address' => ['name' => 'John']]);
        $json->hasnt(key: 'address.foo');
        $this->assertTrue(true);
    }
    
    public function testHasntKeyNestedThrowsIfMissing()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [address.name] does exist.');
        
        $json = new AssertableJson(['address' => ['name' => 'John']]);
        $json->hasnt(key: 'address.name');
    }
    
    public function testHasntValue()
    {
        $json = new AssertableJson(['name' => 'John']);
        $json->hasnt(value: ['name' => 'Tom']);
        $this->assertTrue(true);
    }
    
    public function testHasntValueThrowsIfMatching()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json object does match the unexpected value.');
        
        $json = new AssertableJson(['name' => 'John']);
        $json->hasnt(value: ['name' => 'John']);
    }
    
    public function testHasntKeyValue()
    {
        $json = new AssertableJson(['name' => 'John']);
        $json->hasnt(key: 'name', value: 'Tom');
        $this->assertTrue(true);
    }
    
    public function testHasntKeyValueThrowsIfValueMatching()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [name] does match the unexpected value.');
        
        $json = new AssertableJson(['name' => 'John']);
        $json->hasnt(key: 'name', value: 'John');
    }
    
    public function testHasntKeyValueThrowsIfKeyMissing()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [foo] does not exist.');
        
        $json = new AssertableJson(['name' => 'John']);
        $json->hasnt(key: 'foo', value: 'John');
    }
    
    public function testHasntKeyValueNested()
    {
        $json = new AssertableJson(['address' => ['name' => 'John']]);
        $json->hasnt(key: 'address.name', value: 'Tom');
        $this->assertTrue(true);
    }
    
    public function testHasntKeyValueNestedThrowsIfValueNotMatching()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [address.name] does match the unexpected value.');
        
        $json = new AssertableJson(['address' => ['name' => 'John']]);
        $json->hasnt(key: 'address.name', value: 'John');
    }
    
    public function testHasntKeyValueNestedThrowsIfKeyMissing()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [address.foo] does not exist.');
        
        $json = new AssertableJson(['address' => ['name' => 'John']]);
        $json->hasnt(key: 'address.foo', value: 'John');
    }
    
    public function testHasntItems()
    {
        $json = new AssertableJson(['name' => 'John']);
        $json->hasnt(items: 3);
        $this->assertTrue(true);
    }
    
    public function testHasntItemsThrowsIfMatching()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json object does have 1 item(s).');
        
        $json = new AssertableJson(['name' => 'John']);
        $json->hasnt(items: 1);
    }
    
    public function testHasntItemsWithKey()
    {
        $json = new AssertableJson(['name' => ['foo', 'bar']]);
        $json->hasnt(key: 'name', items: 3);
        $this->assertTrue(true);
    }
    
    public function testHasntItemsWithKeyThrowsIfMatching()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json property [name] does have 2 item(s).');
        
        $json = new AssertableJson(['name' => ['foo', 'bar']]);
        $json->hasnt(key: 'name', items: 2);
    }
    
    public function testHasntItemsWithKeyThrowsIfNotArray()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Json property [name] does not have items at all.');
        
        $json = new AssertableJson(['name' => '']);
        $json->hasnt(key: 'name', items: 2);
    }
    
    public function testHasntPasses()
    {
        (new AssertableJson([]))->hasnt(passes: true);
        (new AssertableJson([]))->hasnt(passes: fn () => true);
        $this->assertTrue(true);
    }
    
    public function testHasntPassesThrowsIfFalse()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json object does not pass the given test.');
        
        (new AssertableJson([]))->hasnt(passes: false);
    }
    
    public function testHasntPassesUsingClosureThrowsIfFalse()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Json object does not pass the given test.');
        
        (new AssertableJson([]))->hasnt(passes: fn (array $data) => false);
    }
    
    public function testHasntPassesClosureWithKeyPassesValue()
    {
        (new AssertableJson(['name' => 'John']))
            ->hasnt(key: 'name', passes: fn (mixed $name) => is_string($name));
        $this->assertTrue(true);
    }
}