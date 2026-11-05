<?php

namespace Javaabu\PostTypes\Models\Translatable;

use Javaabu\BlockEditor\Document;
use Javaabu\PostTypes\Models\Post as BasePost;
use Javaabu\Translatable\Contracts\JsonTranslatable;
use Javaabu\Translatable\JsonTranslatable\IsJsonTranslatable;

/**
 * A post whose title, excerpt and content can be translated with
 * javaabu/translatable (JSON storage). Used when translatable is enabled and
 * `post-types.models.post` isn't set. PHP only loads it when it's referenced,
 * so it's safe without translatable.
 */
class Post extends BasePost implements JsonTranslatable
{
    use IsJsonTranslatable {
        stageTranslation as stageJsonTranslation;
    }

    /** @var array<int, string> */
    protected array $translatable = ['title', 'excerpt', 'content'];

    /**
     * Keep morph types (meta, attachments, menus) pointing at the base model
     */
    public function getMorphClass(): string
    {
        return BasePost::class;
    }

    public function contentDocument(?string $locale = null): Document
    {
        $content = $locale === null ? $this->content : $this->translate('content', $locale);

        return $content instanceof Document ? $content : Document::make($content);
    }

    /**
     * Translated content is sanitized like the content column (the cast doesn't
     * see the translations column)
     */
    protected function stageTranslation(string $locale, string $field, mixed $value): static
    {
        if ($field === 'content' && $value !== null && ! $this->isDefaultTranslationLocale($locale)) {
            $document = Document::make($value);

            if (config('block-editor.sanitize.on_save', true)) {
                $document = $document->sanitize();
            }

            $value = $document->toArray();
        }

        return $this->stageJsonTranslation($locale, $field, $value);
    }

    protected function searchColumns(): array
    {
        return [...parent::searchColumns(), 'translations'];
    }
}
