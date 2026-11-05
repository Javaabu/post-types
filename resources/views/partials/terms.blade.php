@foreach($post['terms'] as $group)
    <ul class="post-types-terms" aria-label="{{ $group['label'] }}">
        @foreach($group['terms'] as $term)
            <li>
                @if($term['url'])
                    <a href="{{ $term['url'] }}" class="hover:underline"><bdi>{{ $term['name'] }}</bdi></a>
                @else
                    <bdi>{{ $term['name'] }}</bdi>
                @endif
            </li>
        @endforeach
    </ul>
@endforeach
