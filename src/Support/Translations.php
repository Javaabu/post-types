<?php

namespace Javaabu\PostTypes\Support;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Translatable\Translatable;

/**
 * Optional javaabu/translatable support, in one place: every call checks the
 * package is installed first.
 */
class Translations
{
    /** Set to false to turn translations off even when the package is installed */
    public static bool $enabled = true;

    /**
     * Whether javaabu/translatable is installed, enabled and has active languages
     */
    public static function enabled(): bool
    {
        return static::$enabled
            && class_exists(Translatable::class)
            && Translatable::enabled();
    }

    /**
     * Whether a model (or one of its fields) is translatable
     */
    public static function supports(object|string|null $model, ?string $field = null): bool
    {
        return static::$enabled
            && class_exists(Translatable::class)
            && Translatable::supports($model, $field);
    }

    /**
     * Every translatable field of the model in every locale, keyed by input
     * name (title_en, title_dv), or an empty array without translations
     *
     * @return array<string, mixed>
     */
    public static function values(?Model $model): array
    {
        if (! $model || ! static::supports($model)) {
            return [];
        }

        return Translatable::values($model);
    }

    /**
     * Which locales the model has, for badges
     *
     * @return array<int, array<string, mixed>>
     */
    public static function status(?Model $model): array
    {
        if (! $model || ! static::supports($model)) {
            return [];
        }

        return array_values(Translatable::status($model));
    }

    /**
     * The locale values are read in
     */
    public static function currentLocale(): string
    {
        return static::enabled()
            ? Translatable::currentLocale()
            : app()->getLocale();
    }
}
