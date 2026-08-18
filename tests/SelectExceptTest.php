<?php

namespace Shabrani\SelectExcept\Tests;

use PHPUnit\Framework\Attributes\Test;
use Shabrani\SelectExcept\Exceptions\EmptySelectionException;
use Shabrani\SelectExcept\Tests\Models\User;

class SelectExceptTest extends TestCase
{
    #[Test]
    public function it_omits_columns_from_the_result(): void
    {
        $user = User::query()->selectExcept('password', 'remember_token')->first();

        $this->assertArrayNotHasKey('password', $user->getAttributes());
        $this->assertArrayNotHasKey('remember_token', $user->getAttributes());
        $this->assertSame('Ada', $user->name);
        $this->assertSame('ada@example.com', $user->email);
    }

    #[Test]
    public function it_accepts_nested_arrays(): void
    {
        $user = User::query()->selectExcept(['password', ['bio']])->first();

        $this->assertArrayNotHasKey('password', $user->getAttributes());
        $this->assertArrayNotHasKey('bio', $user->getAttributes());
        $this->assertSame('Ada', $user->name);
    }

    #[Test]
    public function it_is_a_noop_when_nothing_is_omitted(): void
    {
        $user = User::query()->selectExcept()->first();

        $this->assertArrayHasKey('password', $user->getAttributes());
        $this->assertArrayHasKey('bio', $user->getAttributes());
    }

    #[Test]
    public function it_ignores_unknown_column_names(): void
    {
        $user = User::query()->selectExcept('password', 'not_a_column')->first();

        $this->assertArrayNotHasKey('password', $user->getAttributes());
        $this->assertSame('Ada', $user->name);
    }

    #[Test]
    public function it_filters_an_existing_select(): void
    {
        $user = User::query()
            ->select(['id', 'name', 'email', 'password'])
            ->selectExcept('password')
            ->first();

        $this->assertSame(['id', 'name', 'email'], array_keys($user->getAttributes()));
    }

    #[Test]
    public function it_expands_star_selects(): void
    {
        $sql = User::query()->select('users.*')->selectExcept('password')->toSql();

        $this->assertStringNotContainsString('password', $sql);
        $this->assertStringContainsString('email', $sql);
    }

    #[Test]
    public function it_qualifies_columns_for_joins(): void
    {
        $sql = User::query()
            ->selectExcept('password')
            ->join('posts', 'posts.user_id', '=', 'users.id')
            ->toSql();

        $this->assertStringContainsString('name', $sql);
        $this->assertStringNotContainsString('password', $sql);
    }

    #[Test]
    public function it_omits_hidden_attributes_from_sql(): void
    {
        $user = User::query()->selectExceptHidden()->first();

        $this->assertArrayNotHasKey('password', $user->getAttributes());
        $this->assertArrayNotHasKey('remember_token', $user->getAttributes());
        $this->assertArrayHasKey('bio', $user->getAttributes());
    }

    #[Test]
    public function it_works_on_eager_load_constraints(): void
    {
        $user = User::query()
            ->with(['posts' => fn ($query) => $query->withAllColumns()->selectExcept('body', 'html')])
            ->first();

        $post = $user->posts->first();

        $this->assertArrayNotHasKey('body', $post->getAttributes());
        $this->assertArrayNotHasKey('html', $post->getAttributes());
        $this->assertSame('Hello', $post->title);
    }

    #[Test]
    public function it_throws_when_every_column_would_be_removed(): void
    {
        $this->expectException(EmptySelectionException::class);

        User::query()
            ->select(['password'])
            ->selectExcept('password')
            ->first();
    }

    #[Test]
    public function hidden_attributes_are_still_selected_without_select_except(): void
    {
        $user = User::query()->first();

        $this->assertArrayHasKey('password', $user->getAttributes());
        $this->assertArrayNotHasKey('password', $user->toArray());
    }
}
