<?php

namespace Javaabu\PostTypes\Http\Controllers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Http\PublicData;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\Models\Term;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Routing\PostTypeRoutes;
use Javaabu\PostTypes\Taxonomy;
use Javaabu\PostTypes\Views\ViewRenderer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The public pages of a post type: the archive, term archives and single
 * posts. Only published posts are shown, except to users who can edit a post
 * (a preview). Templates are found WordPress-style:
 *
 * | Page    | Blade views, first that exists                                                   | Inertia page              |
 * | ------- | -------------------------------------------------------------------------------- | ------------------------- |
 * | archive | `->views(['archive' => …])`, `post-types.{type}.archive`, `post-types.archive`, `post-types::public.archive` | `->pages(['archive' => …])` or `post-types::Public/Archive` |
 * | term    | `->views(['term' => …])`, `post-types.{type}.term`, `post-types.term`, then the archive's | `post-types::Public/Term` |
 * | single  | `->views(['single' => …])`, `post-types.{type}.single`, `post-types.single`, `post-types::public.single` | `post-types::Public/Single` |
 *
 * Extend it and set `config('post-types.controllers.public')` (or pass
 * `controller` to Route::postTypes()) to change what it does.
 */
class PublicPostsController extends Controller
{
    public function __construct(
        protected ViewRenderer $views,
    ) {}

    public function archive(Request $request): View|Responsable|JsonResponse
    {
        $type = $this->postType($request);

        if (! $type->hasArchive()) {
            throw new NotFoundHttpException;
        }

        $posts = $this->paginate($request, $type, $this->publishedQuery($type));

        return $this->renderList($request, $type, 'archive', $posts, null, null);
    }

    public function term(Request $request, string $taxonomy, string $term): View|Responsable|JsonResponse
    {
        $type = $this->postType($request);
        $definition = $this->findTaxonomy($type, $taxonomy);
        $record = $this->findTerm($definition, $term);

        $ids = [$record->getKey(), ...($definition->isHierarchical() ? $this->descendantIds($record) : [])];

        $posts = $this->paginate($request, $type, $this->publishedQuery($type)->withTerms($definition->getKey(), $ids));

        return $this->renderList($request, $type, 'term', $posts, $definition, $record);
    }

    public function show(Request $request): View|Responsable|JsonResponse|RedirectResponse
    {
        $type = $this->postType($request);
        $permalink = $type->getPermalink();
        $parameters = $request->route()?->parameters() ?? [];

        $query = $type->query()->with(['terms', 'author']);

        $post = $permalink->usesId()
            ? $query->find($parameters[$permalink->parameter('id')] ?? null)
            : $query->where('slug', rawurldecode((string) ($parameters[$permalink->parameter('slug')] ?? '')))->first();

        if (! $post || ! $this->canSee($request, $post)) {
            throw new NotFoundHttpException;
        }

        // Another path to the post (an old date, a changed category): send it to its permalink
        if (! $permalink->matches($post, array_map(fn ($value) => rawurldecode((string) $value), $parameters))) {
            $url = $post->url();

            if ($url) {
                return redirect()->to($url, 301);
            }
        }

        $data = PublicData::make($type);

        if ($request->expectsJson()) {
            return new JsonResponse(['data' => $data->full($post)]);
        }

        $views = $this->views->isInertia() ? [] : $this->viewNames($type, 'single');

        return $this->views->render($views, $type->getPage('single') ?? 'post-types::Public/Single', [
            'post_type' => $type->toArray(),
            'post' => $data->full($post),
            'record' => $this->views->isInertia() ? null : $post,
            'preview' => ! $post->isPublished(),
            'archive_url' => PostTypeRoutes::archiveUrl($type),
            'strings' => $data->strings(),
            'layout' => config('post-types.public_layout', 'layouts.app'),
            'layout_section' => config('post-types.layout_section', 'content'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Building blocks
    |--------------------------------------------------------------------------
    */

    /**
     * @return Builder<Post>
     */
    protected function publishedQuery(PostType $type): Builder
    {
        $query = $type->query()->published()->latestPublished()->with(['terms', 'author']);

        if ($type->supportsFeature(PostType::MEDIA)) {
            $query->withAttachments();
        }

        return $query;
    }

    /**
     * @param  Builder<Post>  $query
     */
    protected function paginate(Request $request, PostType $type, Builder $query): LengthAwarePaginator
    {
        return $query->paginate($type->getArchivePerPage())->withQueryString();
    }

    protected function renderList(Request $request, PostType $type, string $page, LengthAwarePaginator $posts, ?Taxonomy $taxonomy, ?Term $term): View|Responsable|JsonResponse
    {
        $data = PublicData::make($type);
        $items = array_map(fn (Post $post) => $data->card($post), $posts->items());

        $pagination = [
            'current_page' => $posts->currentPage(),
            'last_page' => $posts->lastPage(),
            'per_page' => $posts->perPage(),
            'total' => $posts->total(),
            'prev_url' => $posts->previousPageUrl(),
            'next_url' => $posts->nextPageUrl(),
        ];

        if ($request->expectsJson()) {
            return new JsonResponse(['data' => $items, 'meta' => $pagination]);
        }

        $views = [];

        if (! $this->views->isInertia()) {
            $views = $page === 'term'
                ? [...$this->viewNames($type, 'term', false), ...$this->viewNames($type, 'archive')]
                : $this->viewNames($type, 'archive');
        }

        $default = $page === 'term' ? 'post-types::Public/Term' : 'post-types::Public/Archive';

        return $this->views->render($views, $type->getPage($page) ?? $default, [
            'post_type' => $type->toArray(),
            'posts' => $items,
            'paginator' => $this->views->isInertia() ? null : $posts,
            'pagination' => $pagination,
            'taxonomy' => $taxonomy?->toArray(),
            'term' => $term ? $data->term($term) : null,
            'title' => $term ? (string) $term->name : $type->getPluralLabel(),
            'archive_url' => PostTypeRoutes::archiveUrl($type),
            'strings' => $data->strings(),
            'layout' => config('post-types.public_layout', 'layouts.app'),
            'layout_section' => config('post-types.layout_section', 'content'),
        ]);
    }

    /**
     * The Blade views a page is looked up in, the app's first
     *
     * @return array<int, string>
     */
    protected function viewNames(PostType $type, string $page, bool $with_default = true): array
    {
        return array_values(array_filter([
            $type->getView($page),
            "post-types.{$type->getKey()}.{$page}",
            "post-types.{$page}",
            $with_default ? "post-types::public.{$page}" : null,
        ]));
    }

    /**
     * Published posts, and others for users who can edit them (previews)
     */
    protected function canSee(Request $request, Post $post): bool
    {
        if ($post->isPublished()) {
            return true;
        }

        $user = $request->user(PostTypeRoutes::guard($request));

        if (! $user instanceof Model) {
            return false;
        }

        return app(Gate::class)->forUser($user)->allows('update', $post);
    }

    protected function postType(Request $request): PostType
    {
        $type = PostTypeRoutes::postType($request);

        if (! $type || ! $type->isPublic()) {
            throw new NotFoundHttpException;
        }

        return $type;
    }

    protected function findTaxonomy(PostType $type, string $slug): Taxonomy
    {
        foreach ($type->getTaxonomies() as $taxonomy) {
            if ($taxonomy->isPublic() && $taxonomy->getSlug() === $slug) {
                return $taxonomy;
            }
        }

        throw new NotFoundHttpException;
    }

    protected function findTerm(Taxonomy $taxonomy, string $slug): Term
    {
        $model = PostTypes::termModel();

        return $model::query()->ofTaxonomy($taxonomy)->where('slug', rawurldecode($slug))->first()
            ?? throw new NotFoundHttpException;
    }

    /**
     * @return array<int, int|string>
     */
    protected function descendantIds(Term $term): array
    {
        $model = PostTypes::termModel();
        $ids = [];
        $parents = [$term->getKey()];

        while ($parents !== []) {
            $children = $model::query()->whereIn('parent_id', $parents)->pluck('id')->all();
            $children = array_values(array_diff($children, $ids, [$term->getKey()]));
            $ids = [...$ids, ...$children];
            $parents = $children;
        }

        return $ids;
    }
}
