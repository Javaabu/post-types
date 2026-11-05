<?php

namespace Javaabu\PostTypes\Tests\TestSupport\Http;

use Illuminate\Http\Request;
use Javaabu\PostTypes\Http\Controllers\PostsController;
use Javaabu\PostTypes\Models\Post;

class CustomPostsController extends PostsController
{
    /** @var array<int, int> */
    public static array $saved = [];

    protected function saved(Request $request, Post $post, bool $created): void
    {
        static::$saved[] = $post->id;
    }
}
