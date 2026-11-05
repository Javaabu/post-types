@extends($layout)

@section($layout_section)
    @manifestRequire('post-types::styles')

    <div class="flex flex-col gap-6" data-post-type="{{ $post_type['key'] }}">
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

        <nav aria-label="{{ $strings['column_status'] }}">
            <ul class="flex flex-wrap gap-x-4 gap-y-2 text-sm">
                @foreach($tabs as $tab)
                    <li>
                        <a href="{{ $tab['url'] }}"
                           data-tab="{{ $tab['key'] }}"
                           @class(['hover:underline', 'font-semibold' => $tab['active'], 'text-muted-foreground' => ! $tab['active']])
                           @if($tab['active']) aria-current="page" @endif>
                            {{ $tab['label'] }} <span class="text-muted-foreground">({{ $tab['count'] }})</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <x-filters::bar :filters="$filters" />

        <x-forms::table :model="$bulk_actions->getName()">
            <x-slot:bulk-form :action="$bulk_actions->getUrl()">
                <x-bulk-actions::actions :actions="$bulk_actions" :paginator="$posts" />
            </x-slot:bulk-form>

            <x-slot:headers>
                <x-filters::heading :filters="$filters" sort="title" :label="$strings['column_title']" />
                <x-forms::table.heading :label="$strings['column_status']" />
                @if(in_array('author', $post_type['supports'], true))
                    <x-forms::table.heading :label="$strings['column_author']" />
                @endif
                @if($post_type['taxonomies'])
                    <x-forms::table.heading :label="$strings['column_terms']" />
                @endif
                <x-filters::heading :filters="$filters" sort="published_at" :label="$strings['column_date']" />
                <x-forms::table.heading :label="$strings['actions']" />
            </x-slot:headers>

            <x-slot:rows>
                @forelse($rows as $row)
                    @include('post-types::admin.posts.row', ['row' => $row, 'post_type' => $post_type, 'strings' => $strings, 'name' => $bulk_actions->getName()])
                @empty
                    <x-forms::table.empty-row :columns="4 + (in_array('author', $post_type['supports'], true) ? 1 : 0) + ($post_type['taxonomies'] ? 1 : 0) + 1">
                        {{ $filters->getActiveFilters() ? $strings['no_results'] : $strings['empty'] }}
                    </x-forms::table.empty-row>
                @endforelse
            </x-slot:rows>

            <x-slot:pagination>
                <x-forms::pagination :paginator="$posts" />
            </x-slot:pagination>
        </x-forms::table>

        {{-- Row actions post their own forms; forms can't nest inside the bulk form --}}
        @foreach($rows as $row)
            @foreach(['delete_url' => 'DELETE', 'restore_url' => 'POST', 'force_delete_url' => 'DELETE'] as $key => $method)
                @if($row[$key])
                    <form id="post-{{ $row['id'] }}-{{ str_replace('_url', '', $key) }}" method="POST" action="{{ $row[$key] }}" class="hidden">
                        @csrf
                        @method($method)
                    </form>
                @endif
            @endforeach
        @endforeach
    </div>
@endsection
