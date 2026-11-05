<?php

namespace Javaabu\PostTypes\Exceptions;

use InvalidArgumentException;

class PostTypesException extends InvalidArgumentException
{
    public static function unknownType(string $key): static
    {
        return new static("There's no post type [{$key}]. Register it with PostTypes::register() or in config('post-types.types').");
    }

    public static function unknownTaxonomy(string $key): static
    {
        return new static("There's no taxonomy [{$key}]. Register it with PostTypes::taxonomy() or in config('post-types.taxonomies').");
    }

    public static function unknownStatus(string $key): static
    {
        return new static("There's no post status [{$key}]. Register it with PostTypes::status().");
    }

    public static function invalidKey(string $what, string $key): static
    {
        return new static("[{$key}] isn't a valid {$what} key: start with a letter and use lowercase letters, numbers, dashes and underscores.");
    }

    public static function invalidPermalink(string $type, string $structure, string $reason): static
    {
        return new static("The permalink structure [{$structure}] of post type [{$type}] is invalid: {$reason}");
    }
}
