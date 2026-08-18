<?php

namespace Shabrani\SelectExcept\Tests;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Shabrani\SelectExcept\ColumnListingCache;
use Shabrani\SelectExcept\Tests\Models\Post;

class ColumnListingCacheTest extends TestCase
{
    #[Test]
    public function it_caches_column_listings(): void
    {
        $cache = app(ColumnListingCache::class);

        $first = $cache->get(DB::connection(), 'users');
        $second = $cache->get(DB::connection(), 'users');

        $this->assertSame($first, $second);
        $this->assertContains('email', $first);
        $this->assertContains('password', $first);
    }

    #[Test]
    public function flush_busts_the_cache(): void
    {
        $cache = app(ColumnListingCache::class);
        $before = $cache->get(DB::connection(), 'users');

        $cache->flush();

        $after = $cache->get(DB::connection(), 'users');

        $this->assertSame($before, $after);
        $this->assertContains('title', (new Post)->getTableColumns());
    }
}
