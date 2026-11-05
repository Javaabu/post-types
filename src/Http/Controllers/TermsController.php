<?php

namespace Javaabu\PostTypes\Http\Controllers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Forms\TermForm;
use Javaabu\PostTypes\Models\Term;
use Javaabu\PostTypes\Routing\PostTypeRoutes;
use Javaabu\PostTypes\Support\Translations;
use Javaabu\PostTypes\Taxonomy;
use Javaabu\PostTypes\Views\ViewRenderer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The admin pages of a taxonomy's terms: a tree of the terms with a search,
 * create, edit and delete. Every action answers JSON when the request expects it.
 *
 * Extend it and set `config('post-types.controllers.terms')` (or pass
 * `controllers` to Route::postTypesAdmin()) to change what it does.
 */
class TermsController extends Controller
{
    public function __construct(
        protected ViewRenderer $views,
    ) {}

    public function index(Request $request): View|Responsable|JsonResponse
    {
        $taxonomy = $this->taxonomy($request);

        $this->authorizeFor($request, 'viewAny', $taxonomy);

        $search = trim((string) $request->query('search', ''));
        $terms = $this->rows($request, $taxonomy, $search);

        if ($request->expectsJson()) {
            return new JsonResponse(['data' => $terms]);
        }

        return $this->views->render('post-types::admin.terms.index', 'post-types::Terms/Index', [
            'taxonomy' => $taxonomy->toArray(),
            'terms' => $terms,
            'search' => $search,
            'create_url' => $this->gate($request)->allows('create', $taxonomy) ? route($this->routeName($request, $taxonomy, 'create')) : null,
            'index_url' => route($this->routeName($request, $taxonomy, 'index')),
            'status' => $request->hasSession() ? $request->session()->get('status') : null,
            'strings' => $this->strings($taxonomy),
            'layout' => config('post-types.layout', 'layouts.app'),
            'layout_section' => config('post-types.layout_section', 'content'),
        ]);
    }

    public function create(Request $request): View|Responsable|JsonResponse
    {
        $taxonomy = $this->taxonomy($request);

        $this->authorizeFor($request, 'create', $taxonomy);

        $form = $this->form($request, $this->newTerm($taxonomy))
            ->action(route($this->routeName($request, $taxonomy, 'store')))
            ->method('POST');

        if ($request->expectsJson()) {
            return new JsonResponse(['taxonomy' => $taxonomy->toArray(), 'form' => $form->render('json')]);
        }

        return $this->editView($request, $taxonomy, null, $form);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $taxonomy = $this->taxonomy($request);

        $this->authorizeFor($request, 'create', $taxonomy);

        $term = $this->form($request, $this->newTerm($taxonomy))->save();

        return $this->savedResponse($request, $taxonomy, $term, __(':type created.', ['type' => $taxonomy->getSingularLabel()]), 201);
    }

    public function edit(Request $request, string $term): View|Responsable|JsonResponse
    {
        $taxonomy = $this->taxonomy($request);
        $record = $this->findTerm($taxonomy, $term);

        $this->authorizeFor($request, 'update', $record);

        $form = $this->form($request, $record)
            ->action(route($this->routeName($request, $taxonomy, 'update'), $record->getKey()))
            ->method('PATCH');

        if ($request->expectsJson()) {
            return new JsonResponse(['data' => $this->termData($request, $taxonomy, $record), 'form' => $form->render('json')]);
        }

        return $this->editView($request, $taxonomy, $record, $form);
    }

    public function update(Request $request, string $term): RedirectResponse|JsonResponse
    {
        $taxonomy = $this->taxonomy($request);
        $record = $this->findTerm($taxonomy, $term);

        $this->authorizeFor($request, 'update', $record);

        $record = $this->form($request, $record)->save();

        return $this->savedResponse($request, $taxonomy, $record, __(':type saved.', ['type' => $taxonomy->getSingularLabel()]));
    }

    public function destroy(Request $request, string $term): RedirectResponse|JsonResponse
    {
        $taxonomy = $this->taxonomy($request);
        $record = $this->findTerm($taxonomy, $term);

        $this->authorizeFor($request, 'delete', $record);

        $record->delete();

        $message = __(':type deleted.', ['type' => $taxonomy->getSingularLabel()]);

        if ($request->expectsJson()) {
            return new JsonResponse(['message' => $message]);
        }

        return redirect()->route($this->routeName($request, $taxonomy, 'index'))->with('status', $message);
    }

    /*
    |--------------------------------------------------------------------------
    | Building blocks
    |--------------------------------------------------------------------------
    */

    protected function form(Request $request, Term $term): TermForm
    {
        return TermForm::for($term);
    }

    protected function newTerm(Taxonomy $taxonomy): Term
    {
        $model = PostTypes::termModel();

        $term = new $model;
        $term->taxonomy = $taxonomy->getKey();

        return $term;
    }

    /**
     * The terms as a tree (or the matches of a search), with their depth and URLs
     *
     * @return array<int, array<string, mixed>>
     */
    protected function rows(Request $request, Taxonomy $taxonomy, string $search): array
    {
        $model = PostTypes::termModel();

        if ($search !== '') {
            $nodes = $model::query()->ofTaxonomy($taxonomy)->search($search)->ordered()->get()
                ->map(fn (Term $term) => ['term' => $term, 'depth' => 0])
                ->all();
        } else {
            $nodes = $model::tree($taxonomy->getKey());
        }

        $counts = $model::query()->ofTaxonomy($taxonomy)->withCount('posts')->pluck('posts_count', 'id');

        return array_map(fn (array $node) => [
            ...$this->termData($request, $taxonomy, $node['term']),
            'depth' => $node['depth'],
            'posts_count' => (int) ($counts[$node['term']->getKey()] ?? 0),
        ], $nodes);
    }

    /**
     * @return array<string, mixed>
     */
    protected function termData(Request $request, Taxonomy $taxonomy, Term $term): array
    {
        $gate = $this->gate($request);

        return [
            'id' => $term->getKey(),
            'name' => (string) $term->name,
            'slug' => $term->slug,
            'description' => $term->description,
            'parent_id' => $term->parent_id,
            'order' => $term->order,
            'translations' => Translations::status($term),
            'edit_url' => $gate->allows('update', $term) ? route($this->routeName($request, $taxonomy, 'edit'), $term->getKey()) : null,
            'delete_url' => $gate->allows('delete', $term) ? route($this->routeName($request, $taxonomy, 'destroy'), $term->getKey()) : null,
        ];
    }

    protected function editView(Request $request, Taxonomy $taxonomy, ?Term $term, TermForm $form): View|Responsable
    {
        return $this->views->render('post-types::admin.terms.edit', 'post-types::Terms/Edit', [
            'taxonomy' => $taxonomy->toArray(),
            'term' => $term ? $this->termData($request, $taxonomy, $term) : null,
            'form' => $this->views->isInertia() ? $form->render('json') : $form,
            'index_url' => route($this->routeName($request, $taxonomy, 'index')),
            'status' => $request->hasSession() ? $request->session()->get('status') : null,
            'strings' => $this->strings($taxonomy),
            'layout' => config('post-types.layout', 'layouts.app'),
            'layout_section' => config('post-types.layout_section', 'content'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function strings(Taxonomy $taxonomy): array
    {
        return [
            'title' => $taxonomy->getPluralLabel(),
            'description' => (string) $taxonomy->getDescription(),
            'add' => __('Add :type', ['type' => $taxonomy->getSingularLabel()]),
            'new_title' => __('New :type', ['type' => $taxonomy->getSingularLabel()]),
            'edit_title' => __('Edit :type', ['type' => $taxonomy->getSingularLabel()]),
            'back' => __('Back to :type', ['type' => $taxonomy->getPluralLabel()]),
            'empty' => __('No :type yet.', ['type' => mb_strtolower($taxonomy->getPluralLabel())]),
            'no_results' => __('Nothing matches this search.'),
            'search' => __('Search'),
            'column_name' => __('Name'),
            'column_slug' => __('Slug'),
            'column_posts' => __('Posts'),
            'actions' => __('Actions'),
            'edit' => __('Edit'),
            'delete' => __('Delete'),
            'confirm_delete' => __('Delete this term? Posts keep their other terms.'),
        ];
    }

    protected function savedResponse(Request $request, Taxonomy $taxonomy, Term $term, string $message, int $status = 200): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return new JsonResponse(['message' => $message, 'data' => $this->termData($request, $taxonomy, $term)], $status);
        }

        return redirect()->route($this->routeName($request, $taxonomy, 'index'))->with('status', $message);
    }

    protected function taxonomy(Request $request): Taxonomy
    {
        return PostTypeRoutes::taxonomy($request) ?? throw new NotFoundHttpException;
    }

    protected function findTerm(Taxonomy $taxonomy, string $id): Term
    {
        $model = PostTypes::termModel();

        return $model::query()->ofTaxonomy($taxonomy)->find($id) ?? throw new NotFoundHttpException;
    }

    protected function authorizeFor(Request $request, string $ability, mixed $argument): void
    {
        $this->gate($request)->authorize($ability, [$argument]);
    }

    protected function gate(Request $request): Gate
    {
        $gate = app(Gate::class);

        $user = $this->user($request);

        return $user ? $gate->forUser($user) : $gate;
    }

    protected function user(Request $request): ?Model
    {
        $user = $request->user(PostTypeRoutes::guard($request));

        return $user instanceof Model ? $user : null;
    }

    protected function routeName(Request $request, Taxonomy $taxonomy, string $action): string
    {
        return PostTypeRoutes::namePrefix($request) . "taxonomies.{$taxonomy->getKey()}.{$action}";
    }
}
