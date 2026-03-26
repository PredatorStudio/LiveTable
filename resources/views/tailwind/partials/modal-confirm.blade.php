<template x-if="confirm.open">
    <div
        class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white rounded-xl shadow-xl border border-gray-200 p-5 w-full max-w-xs"
        style="z-index: 1060;"
        @keydown.escape.window="confirm.open = false"
    >
        <div class="flex flex-col items-center text-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-50 text-red-600 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <p class="text-sm text-gray-500 mb-0" x-text="confirm.message"></p>
            <div class="flex gap-2 w-full">
                <button
                    type="button"
                    @click="confirm.open = false"
                    class="flex-1 inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors"
                >
                    Anuluj
                </button>
                <button
                    type="button"
                    @click="confirm.action?.(); confirm.open = false"
                    class="flex-1 inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium rounded-md bg-red-600 text-white hover:bg-red-500 focus:outline-none transition-colors"
                >
                    Potwierdź
                </button>
            </div>
        </div>
    </div>
</template>