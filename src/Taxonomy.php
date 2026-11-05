<?php

namespace Javaabu\PostTypes;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Javaabu\PostTypes\Exceptions\PostTypesException;

/**
 * A way to group posts: categories, tags, topics. Taxonomies are shared:
 * define one, then list it on the post types that use it.
 *
 * ```php
 * PostTypes::taxonomy(Taxonomy::make('categories')->labels('Category', 'Categories')->hierarchical());
 * PostTypes::taxonomy(Taxonomy::make('tags'));
 * PostType::make('news')->taxonomies(['categories', 'tags']);
 * ```
 *
 * Extend it and set the properties for a class you can list in config('post-types.taxonomies').
 *
 * @implements Arrayable<string, mixed>
 */
class Taxonomy implements Arrayable
{
    protected string $key = '';

    protected Closure|string|null $singular = null;

    protected Closure|string|null $plural = null;

    protected Closure|string|null $description = null;

    protected ?string $slug = null;

    protected bool $hierarchical = false;

    protected bool $multiple = true;

    protected bool $required = false;

    protected bool $public = true;

    protected ?string $permission = null;

    protected string $icon = 'tags';

    public function __construct(?string $key = null)
    {
        $this->key = $key ?? $this->key;

        if (! preg_match('/^[a-z][a-z0-9_-]*$/', $this->key)) {
            throw PostTypesException::invalidKey('taxonomy', $this->key);
        }

        $this->setUp();
    }

    public static function make(?string $key = null): static
    {
        return new static($key);
    }

    /**
     * Configure a subclass
     */
    protected function setUp(): void {}

    /**
     * The names shown to admins and visitors. Strings are translated when shown.
     */
    public function labels(Closure|string|null $singular = null, Closure|string|null $plural = null): static
    {
        $this->singular = $singular ?? $this->singular;
        $this->plural = $plural ?? $this->plural;

        return $this;
    }

    public function description(Closure|string|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * The URL segment of term archives: /news/{slug}/{term}. Defaults to the key.
     */
    public function slug(?string $slug): static
    {
        $this->slug = $slug === null ? null : trim($slug, '/');

        return $this;
    }

    /**
     * Terms can have a parent (categories), instead of a flat list (tags)
     */
    public function hierarchical(bool $hierarchical = true): static
    {
        $this->hierarchical = $hierarchical;

        return $this;
    }

    /**
     * Whether a post can have several terms (default) or only one
     */
    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    /**
     * Whether a post needs at least one term
     */
    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Whether terms have public archives (default)
     */
    public function public(bool $public = true): static
    {
        $this->public = $public;

        return $this;
    }

    /**
     * The resource of the term permissions: view_{permission}, edit_{permission},
     * delete_{permission}. Defaults to the key in snake case.
     */
    public function permission(?string $permission): static
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * A Lucide icon name for admin menus
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getSingularLabel(): string
    {
        return $this->resolveLabel($this->singular, Str::headline(Str::singular($this->key)));
    }

    public function getPluralLabel(): string
    {
        return $this->resolveLabel($this->plural, Str::headline($this->key));
    }

    public function getDescription(): ?string
    {
        if ($this->description === null) {
            return null;
        }

        return $this->resolveLabel($this->description, '');
    }

    public function getSlug(): string
    {
        return $this->slug ?? Str::kebab($this->key);
    }

    public function isHierarchical(): bool
    {
        return $this->hierarchical;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getPermissionResource(): string
    {
        return $this->permission ?? Str::snake(str_replace('-', '_', $this->key));
    }

    /**
     * A permission of the taxonomy: view, edit or delete
     */
    public function permissionFor(string $action): string
    {
        return "{$action}_{$this->getPermissionResource()}";
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'singular' => $this->getSingularLabel(),
            'plural' => $this->getPluralLabel(),
            'description' => $this->getDescription(),
            'slug' => $this->getSlug(),
            'hierarchical' => $this->hierarchical,
            'multiple' => $this->multiple,
            'required' => $this->required,
            'public' => $this->public,
            'icon' => $this->icon,
        ];
    }

    protected function resolveLabel(Closure|string|null $label, string $default): string
    {
        if ($label instanceof Closure) {
            return (string) app()->call($label);
        }

        return __($label ?? $default);
    }
}
