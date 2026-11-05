<?php

namespace Javaabu\PostTypes\Models\Translatable;

use Javaabu\PostTypes\Models\Term as BaseTerm;
use Javaabu\Translatable\Contracts\JsonTranslatable;
use Javaabu\Translatable\JsonTranslatable\IsJsonTranslatable;

/**
 * A taxonomy term whose name and description can be translated with
 * javaabu/translatable (JSON storage). Used when translatable is enabled and
 * `post-types.models.term` isn't set.
 */
class Term extends BaseTerm implements JsonTranslatable
{
    use IsJsonTranslatable;

    /** @var array<int, string> */
    protected array $translatable = ['name', 'description'];

    public function getMorphClass(): string
    {
        return BaseTerm::class;
    }

    protected function searchColumns(): array
    {
        return [...parent::searchColumns(), 'translations'];
    }
}
