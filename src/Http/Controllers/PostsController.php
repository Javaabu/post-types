<?php

namespace Javaabu\PostTypes\Http\Controllers;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Javaabu\PostTypes\BulkActions\PostBulkActions;
use Javaabu\PostTypes\Filters\PostFilters;
use Javaabu\PostTypes\Forms\PostForm;
use Javaabu\PostTypes\Http\PostData;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Routing\PostTypeRoutes;
use Javaabu\PostTypes\Status;
use Javaabu\PostTypes\Views\ViewRenderer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The admin pages of a post type: the index with filters and bulk actions,
 * create and edit, trash, restore and delete for good. Every action answers
 * JSON when the request expects it.
 *
 * Extend it and set `config('post-types.controllers.posts')` (or pass
 * `controllers` to Route::postTypesAdmin()) to change what it does.
 */
class PostsController extends Controller
{
    public function __construct(
        protected ViewRenderer $views,
    ) {}

    public function index(Request $request): View|Responsable|JsonResponse
    {
        $type = $this->postType($request);

        $this->authorizeFor($request, 'viewAny', $type);

        $filters = $this->filters($request, $type);
        $query = $type->query()->with(['author', 'terms']);

        if ($type->supportsFeature(PostType::MEDIA)) {
            $query->withAttachments();
        }

        $posts = $filters->apply($query)
            ->paginate((int) $request->integer('per_page', $type->getPerPage()) ?: $type->getPerPage())
            ->withQueryString();

        $data = PostData::make($request, $type);
        $rows = array_map(fn (Post $post) => $data->row($post), $posts->items());

        if ($request->expectsJson()) {
            return new JsonResponse([
                'data' => $rows,
                'meta' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                ],
            ]);
        }

        $bulk_actions = $this->bulkActions($request, $type)->paginator($posts);
        $inertia = $this->views->isInertia();

        return $this->views->render('post-types::admin.posts.index', 'post-types::Posts/Index', [
            'post_type' => $type->toArray(),
            'posts' => $inertia ? $this->paginatorData($posts, $rows) : $posts,
            'rows' => $rows,
            'filters' => $filters,
            'bulk_actions' => $inertia ? $bulk_actions->toArray() : $bulk_actions,
            'tabs' => $this->tabs($request, $type, $filters),
            'create_url' => $this->gate($request)->allows('create', $type) ? route($this->routeName($request, $type, 'create')) : null,
            'index_url' => route($this->routeName($request, $type, 'index')),
            'status' => $request->hasSession() ? $request->session()->get('status') : null,
            'strings' => $data->strings(),
            'layout' => config('post-types.layout', 'layouts.app'),
            'layout_section' => config('post-types.layout_section', 'content'),
        ]);
    }

    public function create(Request $request): View|Responsable|JsonResponse
    {
        $type = $this->postType($request);

        $this->authorizeFor($request, 'create', $type);

        $post = $type->newPost();

        $form = $this->form($request, $type, $post)
            ->action(route($this->routeName($request, $type, 'store')))
            ->method('POST');

        if ($request->expectsJson()) {
            return new JsonResponse(['post_type' => $type->toArray(), 'form' => $form->render('json')]);
        }

        return $this->editView($request, $type, null, $form);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $type = $this->postType($request);

        $this->authorizeFor($request, 'create', $type);

        $post = $type->newPost();

        $user = $this->user($request);

        if ($user) {
            $post->author()->associate($user);
        }

        $form = $this->form($request, $type, $post);
        $post = $form->save();

        $this->saved($request, $post, true);

        return $this->savedResponse($request, $type, $post, __(':type created.', ['type' => $type->getSingularLabel()]), 201);
    }

    public function edit(Request $request, string $post): View|Responsable|JsonResponse
    {
        $type = $this->postType($request);
        $record = $this->findPost($type, $post);

        $this->authorizeFor($request, 'update', $record);

        $form = $this->form($request, $type, $record)
            ->action(route($this->routeName($request, $type, 'update'), $record->getKey()))
            ->method('PATCH');

        if ($request->expectsJson()) {
            return new JsonResponse([
                'data' => PostData::make($request, $type)->full($record),
                'form' => $form->render('json'),
            ]);
        }

        return $this->editView($request, $type, $record, $form);
    }

    public function update(Request $request, string $post): RedirectResponse|JsonResponse
    {
        $type = $this->postType($request);
        $record = $this->findPost($type, $post);

        $this->authorizeFor($request, 'update', $record);

        $record = $this->form($request, $type, $record)->save();

        $this->saved($request, $record, false);

        return $this->savedResponse($request, $type, $record, __(':type saved.', ['type' => $type->getSingularLabel()]));
    }

    /**
     * Move the post to the trash
     */
    public function destroy(Request $request, string $post): RedirectResponse|JsonResponse
    {
        $type = $this->postType($request);
        $record = $this->findPost($type, $post);

        $this->authorizeFor($request, 'delete', $record);

        $record->delete();

        $this->deleted($request, $record, false);

        return $this->doneResponse($request, $type, __(':type moved to the trash.', ['type' => $type->getSingularLabel()]));
    }

    public function restore(Request $request, string $post): RedirectResponse|JsonResponse
    {
        $type = $this->postType($request);
        $record = $this->findPost($type, $post, true);

        $this->authorizeFor($request, 'restore', $record);

        $record->restore();

        return $this->savedResponse($request, $type, $record, __(':type restored.', ['type' => $type->getSingularLabel()]));
    }

    public function forceDelete(Request $request, string $post): RedirectResponse|JsonResponse
    {
        $type = $this->postType($request);
        $record = $this->findPost($type, $post, true);

        $this->authorizeFor($request, 'forceDelete', $record);

        $record->forceDelete();

        $this->deleted($request, $record, true);

        return $this->doneResponse($request, $type, __(':type deleted for good.', ['type' => $type->getSingularLabel()]), true);
    }

    /**
     * Run a bulk action on the selected posts (javaabu/bulk-actions)
     */
    public function bulk(Request $request): Response
    {
        $type = $this->postType($request);

        $this->authorizeFor($request, 'viewAny', $type);

        return $this->bulkActions($request, $type)->respond($request);
    }

    /*
    |--------------------------------------------------------------------------
    | Hooks
    |--------------------------------------------------------------------------
    */

    /**
     * Called after a post is created or saved
     */
    protected function saved(Request $request, Post $post, bool $created): void {}

    /**
     * Called after a post is trashed or deleted for good
     */
    protected function deleted(Request $request, Post $post, bool $force): void {}

    /*
    |--------------------------------------------------------------------------
    | Building blocks
    |--------------------------------------------------------------------------
    */

    protected function form(Request $request, PostType $type, Post $post): PostForm
    {
        return PostForm::for($post, can_publish: $this->gate($request)->allows('publish', $post->exists ? $post : $type));
    }

    protected function filters(Request $request, PostType $type): PostFilters
    {
        $filters = PostFilters::for($type)->fromRequest($request);

        if ($filters->getValues()['trashed'] ?? null) {
            $this->authorizeFor($request, 'viewTrash', $type);
        }

        return $filters;
    }

    protected function bulkActions(Request $request, PostType $type): PostBulkActions
    {
        $user = $this->user($request);

        return PostBulkActions::for($type)
            ->url(route($this->routeName($request, $type, 'bulk')))
            ->user($user instanceof Authorizable ? $user : null);
    }

    protected function editView(Request $request, PostType $type, ?Post $post, PostForm $form): View|Responsable
    {
        $data = PostData::make($request, $type);

        return $this->views->render('post-types::admin.posts.edit', 'post-types::Posts/Edit', [
            'post_type' => $type->toArray(),
            'post' => $post ? $data->full($post) : null,
            'form' => $this->views->isInertia() ? $form->render('json') : $form,
            'index_url' => route($this->routeName($request, $type, 'index')),
            'status' => $request->hasSession() ? $request->session()->get('status') : null,
            'strings' => $data->strings(),
            'layout' => config('post-types.layout', 'layouts.app'),
            'layout_section' => config('post-types.layout_section', 'content'),
        ]);
    }

    /**
     * The status tabs above the index: all, each status, scheduled, and the
     * trash for users who can see it
     *
     * @return array<int, array{key: string, label: string, count: int, url: string, active: bool}>
     */
    protected function tabs(Request $request, PostType $type, PostFilters $filters): array
    {
        $index = route($this->routeName($request, $type, 'index'));
        $values = $filters->getValues();
        $status = $values['status'] ?? null;
        $trashed = $values['trashed'] ?? null;

        $tabs = [[
            'key' => 'all',
            'label' => __('All'),
            'count' => $type->query()->count(),
            'url' => $index,
            'active' => $status === null && $trashed === null,
        ]];

        foreach ($filters->statusOptions() as $key => $label) {
            $query = $type->query();

            match (true) {
                $key === PostFilters::SCHEDULED => $query->scheduled(),
                in_array($key, $type->getPublicStatuses(), true) => $query->published(),
                default => $query->withStatus($key),
            };

            $count = $query->count();

            if ($count === 0 && $status !== $key && $key !== Status::DRAFT) {
                continue;
            }

            $tabs[] = [
                'key' => $key,
                'label' => $label,
                'count' => $count,
                'url' => $index . '?' . http_build_query(['status' => $key]),
                'active' => $status === $key && $trashed === null,
            ];
        }

        if ($this->gate($request)->allows('viewTrash', $type)) {
            $tabs[] = [
                'key' => 'trashed',
                'label' => __('Trash'),
                'count' => $type->query()->onlyTrashed()->count(),
                'url' => $index . '?' . http_build_query(['trashed' => 'only']),
                'active' => $trashed === 'only',
            ];
        }

        return $tabs;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    protected function paginatorData(LengthAwarePaginator $posts, array $rows): array
    {
        return [
            ...$posts->toArray(),
            'data' => $rows,
        ];
    }

    protected function savedResponse(Request $request, PostType $type, Post $post, string $message, int $status = 200): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => $message,
                'data' => PostData::make($request, $type)->full($post->fresh() ?? $post),
            ], $status);
        }

        return redirect()
            ->route($this->routeName($request, $type, 'edit'), $post->getKey())
            ->with('status', $message);
    }

    protected function doneResponse(Request $request, PostType $type, string $message, bool $to_trash = false): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return new JsonResponse(['message' => $message]);
        }

        $parameters = $to_trash && $type->query()->onlyTrashed()->exists() ? ['trashed' => 'only'] : [];

        return redirect()
            ->route($this->routeName($request, $type, 'index'), $parameters)
            ->with('status', $message);
    }

    protected function postType(Request $request): PostType
    {
        return PostTypeRoutes::postType($request) ?? throw new NotFoundHttpException;
    }

    protected function findPost(PostType $type, string $id, bool $with_trashed = false): Post
    {
        $query = $type->query();

        $post = $with_trashed ? $query->withTrashed()->find($id) : $query->find($id);

        return $post ?? throw new NotFoundHttpException;
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

    protected function routeName(Request $request, PostType $type, string $action): string
    {
        return PostTypeRoutes::namePrefix($request) . $type->getKey() . '.' . $action;
    }
}
