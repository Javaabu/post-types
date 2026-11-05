<?php

namespace Javaabu\PostTypes\Routing;

use Illuminate\Support\Carbon;
use Javaabu\PostTypes\Exceptions\PostTypesException;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\PostType;

/**
 * A post type's permalink structure, such as `{year}/{month}/{slug}`: the route
 * it registers, and the path of each post
 *
 * | Token          | Value                                   | Matches       |
 * | -------------- | --------------------------------------- | ------------- |
 * | `{slug}`       | the post's slug                         | one segment   |
 * | `{id}`         | the post's id                           | digits        |
 * | `{year}`       | the publish year, 4 digits              | 4 digits      |
 * | `{month}`      | the publish month, 2 digits             | 2 digits      |
 * | `{day}`        | the publish day, 2 digits               | 2 digits      |
 * | `{categories}` | the slug of the post's first term of a taxonomy the post type has | one segment |
 */
class Permalink
{
    /** @var array<string, string> */
    public const array DATE_TOKENS = [
        'year' => '[0-9]{4}',
        'month' => '[0-9]{2}',
        'day' => '[0-9]{2}',
    ];

    /** @var array<int, string> */
    protected array $tokens;

    public function __construct(
        protected PostType $post_type,
        protected string $structure,
    ) {
        $this->structure = trim($structure, '/');

        preg_match_all('/\{([a-z][a-z0-9_-]*)\}/', $this->structure, $matches);

        $this->tokens = $matches[1];

        $this->validate();
    }

    public function getStructure(): string
    {
        return $this->structure;
    }

    /**
     * @return array<int, string>
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    /**
     * Whether posts are found by id rather than slug
     */
    public function usesId(): bool
    {
        return in_array('id', $this->tokens, true);
    }

    /**
     * The route URI under the post type's base: `{year}/{month}/{slug}`, with
     * taxonomy tokens renamed to valid route parameters (`{term_categories}`)
     */
    public function routeUri(): string
    {
        return preg_replace_callback('/\{([a-z][a-z0-9_-]*)\}/', fn (array $match) => '{' . $this->parameter($match[1]) . '}', $this->structure);
    }

    /**
     * The route parameter constraints
     *
     * @return array<string, string>
     */
    public function wheres(): array
    {
        $wheres = [];

        foreach ($this->tokens as $token) {
            $wheres[$this->parameter($token)] = match (true) {
                $token === 'id' => '[0-9]+',
                isset(self::DATE_TOKENS[$token]) => self::DATE_TOKENS[$token],
                default => '[^/]+',
            };
        }

        return $wheres;
    }

    /**
     * The route parameter of a token
     */
    public function parameter(string $token): string
    {
        if (in_array($token, ['slug', 'id', 'year', 'month', 'day'], true)) {
            return $token === 'slug' ? 'post_slug' : "post_{$token}";
        }

        return 'term_' . str_replace('-', '_', $token);
    }

    /**
     * The route parameters of a post
     *
     * @return array<string, string>|null null when the post can't have a permalink yet
     */
    public function parametersFor(Post $post): ?array
    {
        $parameters = [];

        foreach ($this->tokens as $token) {
            $value = $this->valueFor($post, $token);

            if ($value === null || $value === '') {
                return null;
            }

            $parameters[$this->parameter($token)] = $value;
        }

        return $parameters;
    }

    /**
     * The path of a post under the base: `2026/11/ސުރުހީ`
     */
    public function pathFor(Post $post): ?string
    {
        $parameters = $this->parametersFor($post);

        if ($parameters === null) {
            return null;
        }

        $path = $this->routeUri();

        foreach ($parameters as $name => $value) {
            $path = str_replace('{' . $name . '}', rawurlencode($value), $path);
        }

        return $path;
    }

    /**
     * Whether a request's route parameters match the post's own, so other
     * paths to it can redirect to the canonical one
     *
     * @param  array<string, mixed>  $parameters
     */
    public function matches(Post $post, array $parameters): bool
    {
        $expected = $this->parametersFor($post);

        if ($expected === null) {
            return false;
        }

        foreach ($expected as $name => $value) {
            if ((string) ($parameters[$name] ?? '') !== $value) {
                return false;
            }
        }

        return true;
    }

    protected function valueFor(Post $post, string $token): ?string
    {
        if ($token === 'slug') {
            return $post->slug;
        }

        if ($token === 'id') {
            return $post->getKey() === null ? null : (string) $post->getKey();
        }

        if (isset(self::DATE_TOKENS[$token])) {
            $date = $post->published_at ?? $post->created_at ?? Carbon::now();

            return match ($token) {
                'year' => $date->format('Y'),
                'month' => $date->format('m'),
                default => $date->format('d'),
            };
        }

        return $post->termsOf($token)->first()?->slug;
    }

    protected function validate(): void
    {
        $type = $this->post_type->getKey();

        if (! in_array('slug', $this->tokens, true) && ! in_array('id', $this->tokens, true)) {
            throw PostTypesException::invalidPermalink($type, $this->structure, 'it needs {slug} or {id}.');
        }

        if (count($this->tokens) !== count(array_unique($this->tokens))) {
            throw PostTypesException::invalidPermalink($type, $this->structure, 'a token is used twice.');
        }

        foreach ($this->tokens as $token) {
            if (in_array($token, ['slug', 'id', 'year', 'month', 'day'], true)) {
                continue;
            }

            if (! $this->post_type->hasTaxonomy($token)) {
                throw PostTypesException::invalidPermalink($type, $this->structure, "{{$token}} isn't a token or a taxonomy of the post type.");
            }
        }
    }
}
