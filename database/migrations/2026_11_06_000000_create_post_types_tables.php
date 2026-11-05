<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('post-types.tables.posts', 'posts'), function (Blueprint $table) {
            $table->id();
            $table->string('type', 64);
            $table->string('title');
            $table->string('slug', 191);
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->nullableMorphs('author');

            // Translations (javaabu/translatable). Harmless without it.
            $table->json('translations')->nullable();
            $table->string('lang')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['type', 'slug']);
            $table->index(['type', 'status', 'published_at']);
        });

        Schema::create(config('post-types.tables.terms', 'post_terms'), function (Blueprint $table) {
            $table->id();
            $table->string('taxonomy', 64);
            $table->string('name');
            $table->string('slug', 191);
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->index();
            $table->unsignedInteger('order')->default(0);

            // Translations (javaabu/translatable). Harmless without it.
            $table->json('translations')->nullable();
            $table->string('lang')->nullable()->index();

            $table->timestamps();

            $table->unique(['taxonomy', 'slug']);
            $table->index(['taxonomy', 'order']);
        });

        Schema::create(config('post-types.tables.post_term', 'post_term'), function (Blueprint $table) {
            $table->foreignId('post_id')->constrained(config('post-types.tables.posts', 'posts'))->cascadeOnDelete();
            $table->foreignId('term_id')->constrained(config('post-types.tables.terms', 'post_terms'))->cascadeOnDelete();

            $table->primary(['post_id', 'term_id']);
            $table->index('term_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('post-types.tables.post_term', 'post_term'));
        Schema::dropIfExists(config('post-types.tables.terms', 'post_terms'));
        Schema::dropIfExists(config('post-types.tables.posts', 'posts'));
    }
};
