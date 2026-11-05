@extends($layout)

@section($layout_section)
    @manifestRequire('post-types::styles')

    <div class="flex flex-col gap-6" data-post-type="{{ $post_type['key'] }}">
        <header class="flex flex-col gap-1">
            @if($term && $archive_url)
                <a href="{{ $archive_url }}" class="text-sm hover:underline">{{ $strings['back'] }}</a>
            @endif
            <h1 class="text-3xl font-semibold" dir="auto">{{ $title }}</h1>
            @if($term && $term['description'])
                <p dir="auto">{{ $term['description'] }}</p>
            @endif
        </header>

        @if($posts)
            <div class="post-types-archive">
                @foreach($posts as $post)
                    @include('post-types::partials.card', ['post' => $post, 'strings' => $strings])
                @endforeach
            </div>
        @else
            <p>{{ $strings['empty'] }}</p>
        @endif

        @if($pagination['prev_url'] || $pagination['next_url'])
            <nav class="post-types-pagination" aria-label="{{ $strings['pagination'] }}">
                @if($pagination['prev_url'])
                    <a href="{{ $pagination['prev_url'] }}" rel="prev">{{ $strings['previous'] }}</a>
                @else
                    <span></span>
                @endif
                @if($pagination['next_url'])
                    <a href="{{ $pagination['next_url'] }}" rel="next">{{ $strings['next'] }}</a>
                @endif
            </nav>
        @endif
    </div>
@endsection
