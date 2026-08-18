<?php

namespace Shabrani\SelectExcept\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Shabrani\SelectExcept\SelectExceptServiceProvider;
use Shabrani\SelectExcept\Tests\Models\Post;
use Shabrani\SelectExcept\Tests\Models\User;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        User::query()->create([
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'secret',
            'remember_token' => 'token',
            'bio' => 'A long biography.',
        ]);

        Post::query()->create([
            'user_id' => 1,
            'title' => 'Hello',
            'body' => str_repeat('Body text. ', 20),
            'html' => '<p>Hello</p>',
            'search_index' => 'hello body',
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            SelectExceptServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('select-except.cache.enabled', true);
        $app['config']->set('select-except.throw_when_empty', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('title');
            $table->text('body');
            $table->text('html');
            $table->text('search_index');
            $table->timestamps();
        });
    }
}
