<?php

namespace Javaabu\PostTypes\Views;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFactory;
use Inertia\Inertia;
use RuntimeException;

/**
 * Renders a page with the stack chosen in `config('post-types.views')`
 */
class ViewRenderer
{
    public const string BLADE = 'blade';

    public const string INERTIA = 'inertia';

    /**
     * @param  string|array<int, string>  $view  Blade views, the first that exists wins
     * @param  string  $page  the Inertia page, e.g. `post-types::Posts/Index`
     * @param  array<string, mixed>  $data
     */
    public function render(string|array $view, string $page, array $data = []): View|Responsable
    {
        if ($this->isInertia()) {
            return $this->renderInertia($page, $data);
        }

        return ViewFactory::first((array) $view, $data);
    }

    public function stack(): string
    {
        return config('post-types.views', self::BLADE);
    }

    public function isInertia(): bool
    {
        return $this->stack() === self::INERTIA;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function renderInertia(string $page, array $data): Responsable
    {
        if (! class_exists(Inertia::class)) {
            throw new RuntimeException('Install inertiajs/inertia-laravel to use the inertia views, or set post-types.views to blade.');
        }

        return Inertia::render($page, $data);
    }
}
