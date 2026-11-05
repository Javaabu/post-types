<?php

namespace Javaabu\PostTypes;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Javaabu\PostTypes\Exceptions\PostTypesException;

/**
 * A post status: draft, pending, published, or one of your own
 *
 * ```php
 * PostTypes::status(Status::make('archived')->label('Archived')->color('info'));
 * PostType::make('news')->statuses(['draft', 'published', 'archived']);
 * ```
 *
 * Posts with a public status are shown on the site once their publish date has
 * passed; before that they're scheduled.
 *
 * @implements Arrayable<string, mixed>
 */
class Status implements Arrayable
{
    public const string DRAFT = 'draft';

    public const string PENDING = 'pending';

    public const string PUBLISHED = 'published';

    protected Closure|string|null $label = null;

    protected bool $public = false;

    protected string $color = 'secondary';

    public function __construct(protected string $key)
    {
        if (! preg_match('/^[a-z][a-z0-9_-]*$/', $key)) {
            throw PostTypesException::invalidKey('status', $key);
        }
    }

    public static function make(string $key): static
    {
        return new static($key);
    }

    /**
     * The built-in statuses
     *
     * @return array<string, static>
     */
    public static function defaults(): array
    {
        return [
            self::DRAFT => static::make(self::DRAFT)->label('Draft')->color('secondary'),
            self::PENDING => static::make(self::PENDING)->label('Pending review')->color('warning'),
            self::PUBLISHED => static::make(self::PUBLISHED)->label('Published')->color('success')->public(),
        ];
    }

    /**
     * The name shown to admins. A string is translated when shown.
     */
    public function label(Closure|string|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Whether posts with this status are shown on the site (after their publish date)
     */
    public function public(bool $public = true): static
    {
        $this->public = $public;

        return $this;
    }

    /**
     * The badge color: primary, secondary, success, danger, warning or info
     */
    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        if ($this->label instanceof Closure) {
            return (string) app()->call($this->label);
        }

        return __($this->label ?? Str::headline($this->key));
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @return array{key: string, label: string, public: bool, color: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->getLabel(),
            'public' => $this->public,
            'color' => $this->color,
        ];
    }
}
