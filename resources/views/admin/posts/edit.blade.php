@extends($layout)

@section($layout_section)
    @manifestRequire('post-types::styles')

    <div class="flex flex-col gap-6" data-post-type="{{ $post_type['key'] }}">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-1">
                <a href="{{ $index_url }}" class="text-sm text-muted-foreground hover:underline">{{ $strings['back'] }}</a>
                <h1 class="text-2xl font-semibold" dir="auto">{{ $post ? $strings['edit_title'] : $strings['new_title'] }}</h1>
                @if($post)
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <x-forms::status :color="$post['status_color']" :label="$post['status_label']" />
                        @if($post['published_at_label'])
                            <span class="text-muted-foreground">{{ $post['published_at_label'] }}</span>
                        @endif
                    </div>
                @endif
            </div>

            @if($post && $post['url'])
                <x-forms::link-button :url="$post['url']" color="light" target="_blank" end-icon="external-link">{{ $strings['view'] }}</x-forms::link-button>
            @endif
        </div>

        @if($status)
            <x-forms::alert type="success" role="status">{{ $status }}</x-forms::alert>
        @endif

        {{ $form }}
    </div>
@endsection
