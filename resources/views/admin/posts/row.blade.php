<x-forms::table.row :model="$row" :model-id="$row['id']" :name="$name">
    <x-forms::table.cell :label="$strings['column_title']">
        <div class="flex items-center gap-3">
            @if($row['thumbnail'])
                <img src="{{ $row['thumbnail'] }}" alt="" class="size-10 shrink-0 rounded object-cover" loading="lazy">
            @endif
            <div class="flex flex-col">
                @if($row['edit_url'])
                    <a href="{{ $row['edit_url'] }}" class="font-medium hover:underline" dir="auto">{{ $row['title'] ?: $strings['no_title'] }}</a>
                @else
                    <span class="font-medium" dir="auto">{{ $row['title'] ?: $strings['no_title'] }}</span>
                @endif
                <span class="text-muted-foreground text-xs" dir="auto">{{ $row['slug'] }}</span>
            </div>
        </div>
    </x-forms::table.cell>
    <x-forms::table.cell :label="$strings['column_status']">
        <x-forms::status :color="$row['status_color']" :label="$row['status_label']" />
    </x-forms::table.cell>
    @if(in_array('author', $post_type['supports'], true))
        <x-forms::table.cell :label="$strings['column_author']" :value="$row['author'] ?? '-'" />
    @endif
    @if($post_type['taxonomies'])
        <x-forms::table.cell :label="$strings['column_terms']">
            @foreach($row['terms'] as $group)
                <div class="text-sm"><span class="text-muted-foreground">{{ $group['label'] }}:</span> <bdi>{{ collect($group['terms'])->pluck('name')->join(', ') }}</bdi></div>
            @endforeach
        </x-forms::table.cell>
    @endif
    <x-forms::table.cell :label="$strings['column_date']" :value="$row['published_at_label'] ?? '-'" />
    <x-forms::table.cell :label="$strings['actions']">
        <div class="flex flex-wrap gap-2">
            @if($row['url'])
                <x-forms::link-button :url="$row['url']" size="sm" color="light" target="_blank">{{ $strings['view'] }}</x-forms::link-button>
            @endif
            @if($row['edit_url'])
                <x-forms::link-button :url="$row['edit_url']" size="sm" color="light">{{ $strings['edit'] }}</x-forms::link-button>
            @endif
            @if($row['delete_url'])
                <x-forms::button type="submit" size="sm" color="light" form="post-{{ $row['id'] }}-delete" onclick="return confirm(@js($strings['confirm_trash']))">{{ $strings['trash'] }}</x-forms::button>
            @endif
            @if($row['restore_url'])
                <x-forms::button type="submit" size="sm" color="light" form="post-{{ $row['id'] }}-restore">{{ $strings['restore'] }}</x-forms::button>
            @endif
            @if($row['force_delete_url'])
                <x-forms::button type="submit" size="sm" color="danger" form="post-{{ $row['id'] }}-force_delete" onclick="return confirm(@js($strings['confirm_force_delete']))">{{ $strings['force_delete'] }}</x-forms::button>
            @endif
        </div>
    </x-forms::table.cell>
</x-forms::table.row>
