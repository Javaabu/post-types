<?php

namespace Javaabu\PostTypes\Forms;

use Illuminate\Validation\Rule;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Term;
use Javaabu\PostTypes\Taxonomy;
use Javaabu\Stencil\Components\Section;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\Textarea;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;

/**
 * The form of a taxonomy term: name, slug, parent (hierarchical taxonomies),
 * description and order
 */
class TermForm extends Form
{
    public static function for(Term $term, ?string $id = null): static
    {
        return static::make($id ?? 'term-form')
            ->model($term)
            ->with(['taxonomy' => $term->taxonomy]);
    }

    protected function setUp(): void
    {
        $this->submit(fn () => __('Save'));
    }

    public function taxonomy(): Taxonomy
    {
        return PostTypes::getTaxonomy((string) $this->context('taxonomy'));
    }

    protected function definition(): array
    {
        $taxonomy = $this->taxonomy();

        return [
            Section::make(fn () => $taxonomy->getSingularLabel())->columns(2)->schema(array_values(array_filter([
                TextInput::make('name')
                    ->label(fn () => __('Name'))
                    ->required()
                    ->maxLength(255)
                    ->thaana()
                    ->translatable(),
                TextInput::make('slug')
                    ->label(fn () => __('Slug'))
                    ->maxLength(190)
                    ->help(fn () => __('The last part of the URL. Leave it empty to make one from the name.')),
                $taxonomy->isHierarchical()
                    ? Select::make('parent_id')
                        ->label(fn () => __('Parent'))
                        ->options(fn (?Term $record) => $this->parentOptions($record))
                        ->searchable()
                        ->rules(fn (?Term $record) => [Rule::notIn($this->descendantIds($record))])
                    : null,
                TextInput::make('order')
                    ->label(fn () => __('Order'))
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                Textarea::make('description')
                    ->label(fn () => __('Description'))
                    ->maxLength(2000)
                    ->rows(3)
                    ->thaana()
                    ->columnSpanFull()
                    ->translatable(),
            ]))),
        ];
    }

    /**
     * Every term of the taxonomy except the term and its descendants
     *
     * @return array<int|string, string>
     */
    protected function parentOptions(?Term $record): array
    {
        $model = PostTypes::termModel();
        $excluded = $this->descendantIds($record);
        $options = [];

        foreach ($model::tree($this->taxonomy()->getKey()) as $node) {
            $term = $node['term'];

            if (in_array($term->getKey(), $excluded, true)) {
                continue;
            }

            $options[$term->getKey()] = str_repeat('— ', $node['depth']) . $term->name;
        }

        return $options;
    }

    /**
     * The term and every term under it, which can't be its parent
     *
     * @return array<int, int|string>
     */
    protected function descendantIds(?Term $record): array
    {
        if (! $record?->exists) {
            return [];
        }

        $ids = [$record->getKey()];
        $model = PostTypes::termModel();
        $children = $model::query()->where('parent_id', $record->getKey())->pluck('id')->all();

        while ($children !== []) {
            $children = array_values(array_diff($children, $ids));
            $ids = [...$ids, ...$children];
            $children = $children === [] ? [] : $model::query()->whereIn('parent_id', $children)->pluck('id')->all();
        }

        return $ids;
    }
}
