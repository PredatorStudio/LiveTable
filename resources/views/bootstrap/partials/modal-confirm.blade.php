<template x-if="confirm.open">
    <div
        class="position-fixed top-50 start-50 translate-middle bg-white border rounded-3 shadow-lg p-4"
        style="z-index: 1060; width: 100%; max-width: 20rem;"
        @keydown.escape.window="confirm.open = false"
    >
        <div class="d-flex flex-column align-items-center text-center gap-3">
            <div
                class="rounded-circle d-flex align-items-center justify-content-center text-danger"
                style="width: 2.5rem; height: 2.5rem; background: rgba(220,53,69,0.1); flex-shrink: 0;"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <p class="small mb-0 text-muted" x-text="confirm.message"></p>
            <div class="d-flex gap-2 w-100">
                <button
                    type="button"
                    @click="confirm.open = false"
                    class="btn btn-outline-secondary btn-sm flex-fill"
                >
                    Anuluj
                </button>
                <button
                    type="button"
                    @click="confirm.action?.(); confirm.open = false"
                    class="btn btn-danger btn-sm flex-fill"
                >
                    Potwierdź
                </button>
            </div>
        </div>
    </div>
</template>