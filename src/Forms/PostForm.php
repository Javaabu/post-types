<?php

namespace Javaabu\PostTypes\Forms;

use Illuminate\Database\Eloquent\Model;
use Javaabu\BlockEditor\Stencil\BlockEditorField;
use Javaabu\Mediapicker\Stencil\MediaPickerField;
use Javaabu\Meta\MetaFields;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\Models\Term;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Status;
use Javaabu\PostTypes\Taxonomy;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Components\Grid;
use Javaabu\Stencil\Components\Group;
use Javaabu\Stencil\Components\Section;
use Javaabu\Stencil\Fields\DateTimePicker;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\Textarea;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;

/**
 * The form of a post, built from its post type: title, slug, content and
 * excerpt; the meta fields (nested under `meta`); then status, publish date,
 * featured image, more media and taxonomy terms (nested under `taxonomies`)
 *
 * ```php
 * $form = PostForm::for($post, can_publish: $user->can('publish', $post))->action($url);
 * $post = $form->save();
 * ```
 *
 * Extend it and override a section method to change part of it; set your class
 * as `form()` in a PostsController subclass.
 */
class PostForm extends Form
{
    public static function for(Post $post, bool $can_publish = true, ?string $id = null): static
    {
        return static::make($id ?? 'post-form')
            ->model($post)
            ->with([
                'post_type' => $post->type,
                'can_publish' => $can_publish,
            ]);
    }

    protected function setUp(): void
    {
        $this->submit(fn () => __('Save'));
    }

    public function postType(): PostType
    {
        return PostTypes::get((string) $this->context('post_type'));
    }

    public function canPublish(): bool
    {
        return (bool) $this->context('can_publish', true);
    }

    protected function definition(): array
    {
        return [
            Grid::make(['default' => 1, 'lg' => 3])->schema([
                Group::make()->columnSpan(['lg' => 2])->schema(array_values(array_filter([
                    $this->mainSection(),
                    $this->metaSection(),
                ]))),
                Group::make()->schema(array_values(array_filter([
                    $this->publishSection(),
                    $this->mediaSection(),
                    $this->taxonomiesSection(),
                ]))),
            ]),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Sections
    |--------------------------------------------------------------------------
    */

    protected function mainSection(): Component
    {
        $type = $this->postType();

        return Section::make(fn () => $type->getSingularLabel())->schema(array_values(array_filter([
            TextInput::make('title')
                ->label(fn () => __('Title'))
                ->required()
                ->maxLength(255)
                ->thaana()
                ->translatable(),
            TextInput::make('slug')
                ->label(fn () => __('Slug'))
                ->maxLength(190)
                ->help(fn () => __('The last part of the URL. Leave it empty to make one from the title.')),
            $type->supportsFeature(PostType::CONTENT)
                ? BlockEditorField::make('content')
                    ->label(fn () => __('Content'))
                    ->profile($type->getBlockProfile())
                    ->translatable()
                : null,
            $type->supportsFeature(PostType::EXCERPT)
                ? Textarea::make('excerpt')
                    ->label(fn () => __('Excerpt'))
                    ->maxLength(1000)
                    ->rows(3)
                    ->thaana()
                    ->help(fn () => __('A short summary shown in lists. Leave it empty to use the start of the content.'))
                    ->translatable()
                : null,
        ])));
    }

    /**
     * The post type's javaabu/meta fields, nested under `meta` (meta[subtitle])
     */
    protected function metaSection(): ?Component
    {
        if (! $this->postType()->supportsFeature(PostType::META)) {
            return null;
        }

        $record = $this->getRecord();
        $model = $record instanceof Model ? $record : Post::class;
        $group = $record instanceof Model ? null : $this->postType()->getKey();

        if (app(MetaFields::class)->formFields($model, $group) === []) {
            return null;
        }

        return Section::make(fn () => __('Details'))->schema([
            app(MetaFields::class)->nested($model, $group),
        ]);
    }

    protected function publishSection(): Component
    {
        $type = $this->postType();

        return Section::make(fn () => __('Publish'))->schema(array_values(array_filter([
            Select::make('status')
                ->label(fn () => __('Status'))
                ->options(fn () => $this->statusOptions())
                ->required()
                ->default($type->getDefaultStatus())
                ->disabled(fn (?Post $record) => ! $this->canPublish() && $record?->hasPublicStatus()),
            $type->supportsFeature(PostType::SCHEDULING)
                ? DateTimePicker::make('published_at')
                    ->label(fn () => __('Publish date'))
                    ->help(fn () => __('Leave it empty to publish now. A date in the future schedules the post.'))
                    ->disabled(fn () => ! $this->canPublish())
                : null,
        ])));
    }

    protected function mediaSection(): ?Component
    {
        $type = $this->postType();
        $fields = [];

        if ($type->supportsFeature(PostType::MEDIA)) {
            $fields[] = MediaPickerField::make(Post::FEATURED_IMAGE)
                ->label(fn () => __('Featured image'))
                ->mediaType('image');
        }

        $fields = [...$fields, ...$type->getMediaFields()];

        if ($fields === []) {
            return null;
        }

        return Section::make(fn () => __('Media'))->schema($fields);
    }

    /**
     * A select per taxonomy, nested under `taxonomies`
     */
    protected function taxonomiesSection(): ?Component
    {
        $taxonomies = $this->postType()->getTaxonomies();

        if ($taxonomies === []) {
            return null;
        }

        return Section::make(fn () => __('Taxonomies'))->schema([
            Group::make()->statePath('taxonomies')->schema(array_values(array_map(
                fn (Taxonomy $taxonomy) => $this->taxonomyField($taxonomy),
                $taxonomies,
            ))),
        ]);
    }

    protected function taxonomyField(Taxonomy $taxonomy): Select
    {
        $field = Select::make($taxonomy->getKey())
            ->label(fn () => $taxonomy->getPluralLabel())
            ->options(fn () => $this->termOptions($taxonomy))
            ->searchable()
            ->required($taxonomy->isRequired());

        return $taxonomy->isMultiple() ? $field->multiple() : $field;
    }

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    */

    /**
     * The statuses the user may give the post: only the ones that aren't public
     * without the publish permission
     *
     * @return array<string, string>
     */
    protected function statusOptions(): array
    {
        $options = [];

        foreach ($this->postType()->getStatuses() as $key => $status) {
            if ($status->isPublic() && ! $this->canPublish() && ! $this->recordHasStatus($key)) {
                continue;
            }

            $options[$key] = $status->getLabel();
        }

        return $options;
    }

    protected function recordHasStatus(string $status): bool
    {
        $record = $this->getRecord();

        return $record instanceof Post && $record->exists && $record->status === $status;
    }

    /**
     * The terms of a taxonomy, children indented under their parents
     *
     * @return array<int|string, string>
     */
    protected function termOptions(Taxonomy $taxonomy): array
    {
        $model = PostTypes::termModel();
        $options = [];

        foreach ($model::tree($taxonomy->getKey()) as $node) {
            /** @var Term $term */
            $term = $node['term'];

            $options[$term->getKey()] = str_repeat('— ', $node['depth']) . $term->name;
        }

        return $options;
    }

    /**
     * Statuses that are public need the publish permission
     */
    public function statusIsAllowed(?string $status): bool
    {
        $definition = $this->postType()->getStatus($status);

        return $definition instanceof Status && (! $definition->isPublic() || $this->canPublish());
    }
}
