<?php

namespace Javaabu\PostTypes\Policies;

use Illuminate\Database\Eloquent\Model;
use Javaabu\PostTypes\Models\Term;
use Javaabu\PostTypes\Taxonomy;

/**
 * Who may see and change taxonomy terms, by the permissions of each taxonomy
 * (`categories` → view_categories, edit_categories, delete_categories)
 *
 * | Ability                   | Permission         |
 * | ------------------------- | ------------------ |
 * | viewAny (a Taxonomy)      | view_x or edit_x   |
 * | view (a term)             | view_x or edit_x   |
 * | create (a Taxonomy)       | edit_x             |
 * | update (a term)           | edit_x             |
 * | delete (a term)           | delete_x           |
 *
 * Override it with `config('post-types.policies.term')`, `config('permissions.policies')`
 * or `Gate::policy()` for the term model and Taxonomy.
 */
class TermPolicy
{
    public function viewAny(Model $user, Taxonomy $taxonomy): bool
    {
        return $this->allows($user, $taxonomy, 'view', 'edit');
    }

    public function view(Model $user, Term $term): bool
    {
        $taxonomy = $term->taxonomyDefinition();

        return $taxonomy !== null && $this->allows($user, $taxonomy, 'view', 'edit');
    }

    public function create(Model $user, Taxonomy $taxonomy): bool
    {
        return $this->allows($user, $taxonomy, 'edit');
    }

    public function update(Model $user, Term $term): bool
    {
        $taxonomy = $term->taxonomyDefinition();

        return $taxonomy !== null && $this->allows($user, $taxonomy, 'edit');
    }

    public function delete(Model $user, Term $term): bool
    {
        $taxonomy = $term->taxonomyDefinition();

        return $taxonomy !== null && $this->allows($user, $taxonomy, 'delete');
    }

    protected function allows(Model $user, Taxonomy $taxonomy, string ...$actions): bool
    {
        foreach ($actions as $action) {
            if ($user->can($taxonomy->permissionFor($action))) {
                return true;
            }
        }

        return false;
    }
}
