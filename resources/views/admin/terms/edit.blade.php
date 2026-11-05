@extends($layout)

@section($layout_section)
    <div class="flex flex-col gap-6" data-taxonomy="{{ $taxonomy['key'] }}">
        <div class="flex flex-col gap-1">
            <a href="{{ $index_url }}" class="text-sm text-muted-foreground hover:underline">{{ $strings['back'] }}</a>
            <h1 class="text-2xl font-semibold" dir="auto">{{ $term ? $strings['edit_title'] : $strings['new_title'] }}</h1>
        </div>

        @if($status)
            <x-forms::alert type="success" role="status">{{ $status }}</x-forms::alert>
        @endif

        {{ $form }}
    </div>
@endsection
