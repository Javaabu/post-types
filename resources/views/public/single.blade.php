@extends($layout)

@section($layout_section)
    @manifestRequire('post-types::styles')

    <article class="flex flex-col gap-6" data-post-type="{{ $post_type['key'] }}" data-post="{{ $post['id'] }}">
        @if($preview)
            <x-forms::alert type="warning" role="status">{{ $strings['preview'] }}</x-forms::alert>
        @endif

        <header class="flex flex-col gap-2">
            @if($archive_url)
                <a href="{{ $archive_url }}" class="text-sm hover:underline">{{ $strings['back'] }}</a>
            @endif
            <h1 class="text-3xl font-semibold" dir="auto">{{ $post['title'] }}</h1>
            @include('post-types::partials.meta', ['post' => $post, 'strings' => $strings])
        </header>

        @if($post['image'])
            <figure class="post-types-single__image">
                <img src="{{ $post['image'] }}" alt="">
            </figure>
        @endif

        @if($post['content'] !== null)
            <div class="post-types-content">
                <x-block-editor::content :content="$record ? $record->contentDocument() : $post['content']" />
            </div>
        @elseif($post['excerpt'])
            <p dir="auto">{{ $post['excerpt'] }}</p>
        @endif

        @include('post-types::partials.terms', ['post' => $post])
    </article>
@endsection
