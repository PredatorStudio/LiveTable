@if ($total > 0)
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

        {{-- Per-page selector --}}
        <div class="d-flex align-items-center gap-1 small text-muted">
            <span class="me-1">Wyświetl:</span>
            @foreach ([10, 25, 50, 100, 200] as $option)
                <button
                    type="button"
                    wire:click="$set('perPage', {{ $option }})"
                    class="btn btn-sm {{ $perPage === $option ? 'btn-primary' : 'btn-outline-secondary' }} px-2 py-0"
                    style="font-size: 0.8rem;"
                >{{ $option }}</button>
            @endforeach
            @if ($allowInfiniteScroll)
                <button
                    type="button"
                    wire:click="$set('perPage', 0)"
                    class="btn btn-sm {{ $perPage === 0 ? 'btn-primary' : 'btn-outline-secondary' }} px-2 py-0"
                    style="font-size: 0.8rem;"
                >Wszystkie</button>
            @endif
        </div>

        {{-- Page links --}}
        @if (!$infiniteMode && $lastPage > 1)
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                        <button type="button" class="page-link" wire:click="setPage({{ max(1, $page - 1) }})">
                            &laquo;
                        </button>
                    </li>

                    @foreach ($pages as $p)
                        @if ($p === '...')
                            <li class="page-item disabled">
                                <span class="page-link">…</span>
                            </li>
                        @else
                            <li class="page-item {{ $page === $p ? 'active' : '' }}">
                                <button type="button" class="page-link" wire:click="setPage({{ $p }})">
                                    {{ $p }}
                                </button>
                            </li>
                        @endif
                    @endforeach

                    <li class="page-item {{ $page >= $lastPage ? 'disabled' : '' }}">
                        <button type="button" class="page-link" wire:click="setPage({{ min($lastPage, $page + 1) }})">
                            &raquo;
                        </button>
                    </li>
                </ul>
            </nav>
        @endif

        {{-- Showing X–Y of Z --}}
        <div class="small text-muted">
            Pokazano
            <strong>{{ $from }}–{{ $to }}</strong>
            z
            <strong>{{ $total }}</strong>
        </div>

    </div>
@endif