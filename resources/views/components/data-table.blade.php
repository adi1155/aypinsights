@props(['title', 'rows' => [], 'columns' => []])

<div class="glass-card overflow-hidden">
    <div class="border-b border-white/10 px-5 py-4">
        <h3 class="text-sm font-semibold text-slate-300">{{ $title }}</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-white/5 text-xs uppercase text-slate-500">
                <tr>
                    @foreach($columns as $col)
                        <th class="px-5 py-3">{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($rows as $row)
                    <tr class="hover:bg-white/5 transition">
                        @foreach((is_array($row) ? array_values($row) : [$row]) as $cell)
                            <td class="px-5 py-3 text-slate-300">
                                @if(is_numeric($cell) && $cell > 999)
                                    {{ number_format($cell, 0) }}
                                @else
                                    {{ $cell }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) }}" class="px-5 py-8 text-center text-slate-500">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
