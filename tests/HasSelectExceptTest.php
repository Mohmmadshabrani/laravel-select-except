<?php

namespace Shabrani\SelectExcept\Tests;

use PHPUnit\Framework\Attributes\Test;
use Shabrani\SelectExcept\Tests\Models\Post;

class HasSelectExceptTest extends TestCase
{
    #[Test]
    public function it_applies_default_omissions_on_every_query(): void
    {
        $post = Post::query()->first();

        $this->assertArrayNotHasKey('body', $post->getAttributes());
        $this->assertArrayNotHasKey('html', $post->getAttributes());
        $this->assertArrayNotHasKey('search_index', $post->getAttributes());
        $this->assertSame('Hello', $post->title);
        $this->assertSame(1, $post->user_id);
    }

    #[Test]
    public function with_all_columns_loads_the_full_row(): void
    {
        $post = Post::query()->withAllColumns()->first();

        $this->assertArrayHasKey('body', $post->getAttributes());
        $this->assertArrayHasKey('html', $post->getAttributes());
        $this->assertArrayHasKey('search_index', $post->getAttributes());
        $this->assertStringContainsString('Body text.', $post->body);
    }

    #[Test]
    public function including_restores_specific_columns(): void
    {
        $post = Post::query()->including('body')->first();

        $this->assertArrayHasKey('body', $post->getAttributes());
        $this->assertArrayNotHasKey('html', $post->getAttributes());
        $this->assertArrayNotHasKey('search_index', $post->getAttributes());
    }

    #[Test]
    public function additional_select_except_calls_stack_with_defaults(): void
    {
        $post = Post::query()->selectExcept('title')->first();

        $this->assertArrayNotHasKey('title', $post->getAttributes());
        $this->assertArrayNotHasKey('body', $post->getAttributes());
        $this->assertSame(1, $post->id);
    }

    #[Test]
    public function get_table_columns_returns_schema_listing(): void
    {
        $columns = (new Post)->getTableColumns();

        $this->assertContains('title', $columns);
        $this->assertContains('body', $columns);
        $this->assertContains('created_at', $columns);
    }

    #[Test]
    public function find_for_a_detail_page_can_opt_back_in(): void
    {
        $list = Post::query()->get()->first();
        $show = Post::query()->including('body', 'html')->find(1);

        $this->assertArrayNotHasKey('body', $list->getAttributes());
        $this->assertArrayHasKey('body', $show->getAttributes());
        $this->assertArrayHasKey('html', $show->getAttributes());
        $this->assertArrayNotHasKey('search_index', $show->getAttributes());
    }
}
