<?php

namespace Shabrani\SelectExcept\Tests;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class QueryBuilderSelectExceptTest extends TestCase
{
    #[Test]
    public function it_omits_columns_on_the_query_builder(): void
    {
        $row = (array) DB::table('users')->selectExcept('password', 'remember_token')->first();

        $this->assertArrayNotHasKey('password', $row);
        $this->assertArrayNotHasKey('remember_token', $row);
        $this->assertSame('Ada', $row['name']);
    }

    #[Test]
    public function it_supports_table_aliases(): void
    {
        $sql = DB::table('users as u')->selectExcept('password')->toSql();

        $this->assertStringContainsString('"u"."name"', $sql);
        $this->assertStringNotContainsString('password', $sql);
    }
}
