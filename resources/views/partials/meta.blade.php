<div class="post-types-meta">
    @if($post['published_at'])
        <time datetime="{{ $post['published_at'] }}">{{ $post['published_at_label'] }}</time>
    @endif
    @if($post['author'])
        <span>{{ str_replace(':author', $post['author'], $strings['by']) }}</span>
    @endif
</div>
