<?php
namespace Pluf\Tests;

use PHPUnit\Framework\TestCase;
use Pluf\Http\Collection;

class CollectionTest extends TestCase
{

    /**
     *
     * @test
     */
    public function createEmptyCollection()
    {
        $collection = new Collection();
        self::assertEquals([], $collection->keys());
    }

    /**
     *
     * @test
     */
    public function cleanCollection()
    {
        $collection = new Collection();
        $collection['a'] = 'b';
        self::assertTrue($collection->has('a'));
        self::assertEquals(1, $collection->count());
        self::assertEquals('b', $collection['a']);

        $collection->clear();
        self::assertFalse($collection->has('a'));
        self::assertEquals(0, $collection->count());
        self::assertEquals([], $collection->keys());
    }

    /**
     *
     * @test
     */
    public function offsetUnsetCollection()
    {
        $collection = new Collection();
        $collection['a'] = 'b';
        self::assertTrue($collection->has('a'));
        self::assertEquals(1, $collection->count());
        self::assertEquals('b', $collection['a']);
        self::assertTrue($collection->offsetExists('a'));

        $collection->offsetUnset('a');
        self::assertFalse($collection->has('a'));
        self::assertEquals(0, $collection->count());
        self::assertEquals([], $collection->keys());
        self::assertFalse($collection->offsetExists('a'));
    }
}

