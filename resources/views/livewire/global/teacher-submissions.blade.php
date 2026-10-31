<div
    class="fixed z-[998] right-5 bottom-24"
    x-data="{
        open: false,
        count: {{ $this->pendingCount }},
        bounce: false,
        init() {
            window.addEventListener('teacher-submissions-count', (e) => {
                const newCount = e.detail.count || 0;
                if (newCount > this.count) {
                    this.bounce = true;
                    setTimeout(() => { this.bounce = false; }, 900);
                }
                this.count = newCount;
            });
            window.addEventListener('teacher-submissions:new', () => {
                this.bounce = true;
                setTimeout(() => { this.bounce = false; }, 900);
            });
        },
        openModal() {
            this.open = true;
            this.$nextTick(() => {
                if (this.count > 0) {
                    window.dispatchEvent(new CustomEvent('teacher-submissions-tour-start'));
                }
            });
        }
    }"
    @keydown.escape.window="! $store.tourguide.isTourActive && (open = false)"
    @teacher-submissions-close.window="open = false"
>
    <button
        type="button"
        @click="openModal()"
        class="relative btn btn-circle btn-lg shadow-lg transition-transform"
        :class="bounce ? 'animate-bounce' : ''"
        style="background-color: #2563eb; color: white;"
        aria-label="Ver entregas pendentes"
        title="Entregas pendentes"
    >
        <x-mary-icon name="o-document-text" class="w-6 h-6" />
        <span
            x-show="count > 0"
            x-cloak
            class="absolute -top-1 -right-1 min-w-6 h-6 px-1 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center border-2 border-white"
        >
            <span x-text="count > 99 ? '99+' : count"></span>
        </span>
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[1000] bg-gray-900/50"
            @click.self="open = false"
            role="dialog"
            aria-modal="true"
            aria-labelledby="teacher-submissions-title"
        >
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[92vw] max-w-2xl max-h-[85vh] bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h2 id="teacher-submissions-title" class="text-lg font-semibold text-gray-900">Entregas pendentes</h2>
                        <p class="text-sm text-gray-500">Atividades enviadas pelos alunos aguardando correção</p>
                    </div>
                    <button
                        type="button"
                        @click="open = false"
                        class="btn btn-ghost btn-sm btn-circle"
                        aria-label="Fechar"
                    >
                        <x-mary-icon name="o-x-mark" class="w-5 h-5" />
                    </button>
                </div>

                <div id="teacher-submissions-list" class="flex-1 overflow-y-auto">
                    @forelse ($this->submissions as $submission)
                        <button
                            type="button"
                            wire:key="submission-{{ $submission['id'] }}"
                            wire:click="openSubmission('{{ $submission['id'] }}')"
                            data-submission-id="{{ $submission['id'] }}"
                            data-student-id="{{ $submission['student_id'] }}"
                            data-student-name="{{ $submission['student'] }}"
                            data-discipline-id="{{ $submission['discipline_id'] }}"
                            data-activity-id="{{ $submission['activity_id'] }}"
                            class="w-full text-left px-6 py-4 hover:bg-blue-50/50 border-b border-gray-50 last:border-0 flex items-start gap-4 transition-colors"
                        >
                            <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 mt-0.5">
                                <x-mary-icon name="o-inbox-arrow-down" class="w-5 h-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $submission['student'] }}</p>
                                    <span class="text-xs text-gray-400 shrink-0">{{ $submission['meta'] }}</span>
                                </div>
                                <p class="text-sm text-gray-700 mt-0.5 line-clamp-1">{{ $submission['activity'] }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $submission['discipline'] }}</p>
                            </div>
                            <x-mary-icon name="o-chevron-right" class="w-5 h-5 text-gray-400 shrink-0 mt-2" />
                        </button>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <x-mary-icon name="o-check-circle" class="w-10 h-10 text-emerald-500 mx-auto" />
                            <p class="mt-3 text-sm font-medium text-gray-900">Tudo em dia</p>
                            <p class="mt-1 text-sm text-gray-500">Não há entregas pendentes de correção.</p>
                        </div>
                    @endforelse
                </div>

                <div class="px-6 py-3 border-t border-gray-100 bg-gray-50/50">
                    <p class="text-xs text-center text-gray-500">Clique em uma entrega para abrir a correção</p>
                </div>
            </div>
        </div>
    </template>
</div>
