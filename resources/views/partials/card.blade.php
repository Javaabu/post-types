<article class="post-types-card" data-post="{{ $post['id'] }}">
    @if($post['image'])
        <a href="{{ $post['url'] }}" tabindex="-1" aria-hidden="true">
            <img src="{{ $post['image'] }}" alt="" loading="lazy">
        </a>
    @endif

    <h2 class="text-lg font-semibold" dir="auto">
        @if($post['url'])
            <a href="{{ $post['url'] }}" class="hover:underline">{{ $post['title'] }}</a>
        @else
            {{ $post['title'] }}
        @endif
    </h2>

    @include('post-types::partials.meta', ['post' => $post, 'strings' => $strings])

    @if($post['summary'])
        <p dir="auto">{{ $post['summary'] }}</p>
    @endif
</article>
