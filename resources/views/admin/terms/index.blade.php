@extends($layout)

@section($layout_section)
    @manifestRequire('post-types::styles')

    <div class="flex flex-col gap-6" data-taxonomy="{{ $taxonomy['key'] }}">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-semibold">{{ $strings['title'] }}</h1>
                @if($strings['description'])
                    <p class="text-muted-foreground">{{ $strings['description'] }}</p>
                @endif
            </div>

            @if($create_url)
                <x-forms::link-button :url="$create_url" start-icon="plus">{{ $strings['add'] }}</x-forms::link-button>
            @endif
        </div>

        @if($status)
            <x-forms::alert type="success" role="status">{{ $status }}</x-forms::alert>
        @endif

        <x-forms::search-form :action="$index_url" :placeholder="$strings['search']" />

        <x-forms::table no-checkbox>
            <x-slot:headers>
                <x-forms::table.heading :label="$strings['column_name']" />
                <x-forms::table.heading :label="$strings['column_slug']" />
                <x-forms::table.heading :label="$strings['column_posts']" />
                <x-forms::table.heading :label="$strings['actions']" />
            </x-slot:headers>

            <x-slot:rows>
                @forelse($terms as $term)
                    <x-forms::table.row :model="$term" :model-id="$term['id']" no-checkbox>
                        <x-forms::table.cell :label="$strings['column_name']">
                            <span class="font-medium" dir="auto" style="padding-inline-start: {{ $term['depth'] * 1.25 }}rem">
                                @if($term['depth'] > 0)<span class="text-muted-foreground" aria-hidden="true">{{ str_repeat('— ', $term['depth']) }}</span>@endif
                                @if($term['edit_url'])
                                    <a href="{{ $term['edit_url'] }}" class="hover:underline">{{ $term['name'] }}</a>
                                @else
                                    {{ $term['name'] }}
                                @endif
                            </span>
                        </x-forms::table.cell>
                        <x-forms::table.cell :label="$strings['column_slug']"><bdi>{{ $term['slug'] }}</bdi></x-forms::table.cell>
                        <x-forms::table.cell :label="$strings['column_posts']" :value="(string) $term['posts_count']" />
                        <x-forms::table.cell :label="$strings['actions']">
                            <div class="flex flex-wrap gap-2">
                                @if($term['edit_url'])
                                    <x-forms::link-button :url="$term['edit_url']" size="sm" color="light">{{ $strings['edit'] }}</x-forms::link-button>
                                @endif
                                @if($term['delete_url'])
                                    <form method="POST" action="{{ $term['delete_url'] }}" onsubmit="return confirm(@js($strings['confirm_delete']))">
                                        @csrf
                                        @method('DELETE')
                                        <x-forms::button type="submit" size="sm" color="light">{{ $strings['delete'] }}</x-forms::button>
                                    </form>
                                @endif
                            </div>
                        </x-forms::table.cell>
                    </x-forms::table.row>
                @empty
                    <x-forms::table.empty-row :columns="4" no-checkbox>
                        {{ $search !== '' ? $strings['no_results'] : $strings['empty'] }}
                    </x-forms::table.empty-row>
                @endforelse
            </x-slot:rows>
        </x-forms::table>
    </div>
@endsection
