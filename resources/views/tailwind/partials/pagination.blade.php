@if ($total > 0)
    <div class="flex flex-wrap items-center justify-between gap-4">

        {{-- Per-page selector --}}
        <div class="flex items-center gap-1 text-sm text-gray-500">
            <span class="mr-1">Wyświetl:</span>
            @foreach ([10, 25, 50, 100, 200] as $option)
                <button
                    type="button"
                    wire:click="$set('perPage', {{ $option }})"
                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors {{ $perPage === $option ? 'bg-indigo-500 text-white hover:bg-indigo-600 border border-indigo-500' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}"
                >{{ $option }}</button>
            @endforeach
            @if ($allowInfiniteScroll)
                <button
                    type="button"
                    wire:click="$set('perPage', 0)"
                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors {{ $perPage === 0 ? 'bg-indigo-500 text-white hover:bg-indigo-600 border border-indigo-500' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}"
                >Wszystkie</button>
            @endif
        </div>

        {{-- Page links --}}
        @if (!$infiniteMode && $lastPage > 1)
            <nav class="flex items-center gap-1">
                <button
                    type="button"
                    wire:click="setPage({{ max(1, $page - 1) }})"
                    @disabled($page <= 1)
                    class="relative inline-flex items-center px-2 py-1.5 text-sm rounded-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >&laquo;</button>

                @foreach ($pages as $p)
                    @if ($p === '...')
                        <span class="relative inline-flex items-center px-2 py-1.5 text-sm rounded-md border border-gray-300 bg-white text-gray-400 cursor-default">…</span>
                    @else
                        <button
                            type="button"
                            wire:click="setPage({{ $p }})"
                            class="relative inline-flex items-center px-3 py-1.5 text-sm rounded-md border transition-colors {{ $page === $p ? 'bg-indigo-500 text-white border-indigo-500 hover:bg-indigo-600' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}"
                        >{{ $p }}</button>
                    @endif
                @endforeach

                <button
                    type="button"
                    wire:click="setPage({{ min($lastPage, $page + 1) }})"
                    @disabled($page >= $lastPage)
                    class="relative inline-flex items-center px-2 py-1.5 text-sm rounded-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >&raquo;</button>
            </nav>
        @endif

        {{-- Showing X–Y of Z --}}
        <p class="text-sm text-gray-700">
            Pokazano <span class="font-medium">{{ $from }}–{{ $to }}</span> z <span class="font-medium">{{ $total }}</span>
        </p>

    </div>
@endif
