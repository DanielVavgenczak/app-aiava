<div
    class="fixed z-[998] right-5 bottom-24"
    x-data="{
        open: false,
        activeTab: 'submitted',
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
            this.activeTab = 'submitted';
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

                <div class="px-6 pt-4">
                    <div role="tablist" class="flex gap-2 border-b border-gray-100">
                        <button
                            type="button"
                            role="tab"
                            id="teacher-submissions-tab-submitted"
                            data-submissions-tab="submitted"
                            aria-controls="teacher-submissions-list"
                            :aria-selected="activeTab === 'submitted'"
                            @click="activeTab = 'submitted'"
                            class="border-b-2 px-3 pb-2 text-sm font-semibold transition-colors"
                            :class="activeTab === 'submitted' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        >
                            Enviadas
                            <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ count($this->submissions) }}</span>
                        </button>
                        <button
                            type="button"
                            role="tab"
                            id="teacher-submissions-tab-missing"
                            data-submissions-tab="missing"
                            aria-controls="teacher-submissions-missing-list"
                            :aria-selected="activeTab === 'missing'"
                            @click="activeTab = 'missing'"
                            class="border-b-2 px-3 pb-2 text-sm font-semibold transition-colors"
                            :class="activeTab === 'missing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        >
                            Não enviadas
                            <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ count($this->missingSubmissions) }}</span>
                        </button>
                    </div>
                </div>

                <div x-show="activeTab === 'submitted'" id="teacher-submissions-list" class="flex-1 overflow-y-auto">
                    @forelse ($this->submissions as $submission)
                        <button
                            type="button"
                            wire:key="submission-{{ $submission['id'] }}"
                            wire:click="openSubmission('{{ $submission['id'] }}')"
                            data-submission-id="{{ $submission['id'] }}"
                            data-student-id="{{ $submission['student_id'] }}"
                            data-student-name="{{ $submission['student'] }}"
                            data-submission-classe="{{ $submission['classe'] }}"
                            data-discipline-id="{{ $submission['discipline_id'] }}"
                            data-activity-id="{{ $submission['activity_id'] }}"
                            class="w-full text-left px-6 py-4 hover:bg-blue-50/50 border-b border-gray-50 last:border-0 flex items-start gap-4 transition-colors"
                        >
                            <span class="w-10 h-10 rounded-xl bg-blue-400 text-white flex items-center justify-center shrink-0 mt-0.5">
                                <x-mary-icon name="o-inbox-arrow-down" class="w-5 h-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $submission['student'] }}</p>
                                    <span class="inline-flex shrink-0 items-center gap-1 text-xs text-gray-500">
                                        <x-mary-icon name="o-calendar-days" class="w-3.5 h-3.5 text-gray-400" />
                                        Enviado em {{ $submission['submitted_at'] }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-700 mt-0.5 line-clamp-1">{{ $submission['activity'] }}</p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                                    <span class="inline-flex items-center gap-1 font-medium text-gray-700">
                                        <x-mary-icon name="o-academic-cap" class="w-3.5 h-3.5 text-gray-500" />
                                        {{ $submission['classe'] }}
                                    </span>
                                    <span class="text-gray-300">•</span>
                                    <span class="text-gray-500">{{ $submission['discipline'] }}</span>
                                </div>
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

                <div x-show="activeTab === 'missing'" x-cloak id="teacher-submissions-missing-list" class="flex-1 overflow-y-auto">
                    @if (count($this->missingSubmissions) > 0)
                        <div class="p-4" data-missing-alert>
                            <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
                                    <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-red-800">Entregas pendentes</p>
                                    <p class="mt-0.5 text-xs text-red-700">Os alunos abaixo ainda não enviaram a atividade.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @forelse ($this->missingSubmissions as $missing)
                        <div
                            wire:key="missing-{{ $missing['activity_id'] }}-{{ $missing['student_id'] }}"
                            data-missing-student-id="{{ $missing['student_id'] }}"
                            data-missing-activity-id="{{ $missing['activity_id'] }}"
                            data-missing-discipline-id="{{ $missing['discipline_id'] }}"
                            data-missing-classe="{{ $missing['classe'] }}"
                            class="w-full px-6 py-4 border-b border-red-100/70 border-l-4 border-l-red-300 last:border-b-0 flex items-start gap-4 bg-red-50/30 transition-colors hover:bg-red-50/60"
                        >
                            <span class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center shrink-0 mt-0.5">
                                <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $missing['student'] }}</p>
                                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                        Não enviou
                                    </span>
                                </div>
                                <p class="text-sm text-gray-700 mt-0.5 line-clamp-1">{{ $missing['activity'] }}</p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                                    <span class="inline-flex items-center gap-1 font-medium text-gray-700">
                                        <x-mary-icon name="o-academic-cap" class="w-3.5 h-3.5 text-gray-500" />
                                        {{ $missing['classe'] }}
                                    </span>
                                    <span class="text-gray-300">•</span>
                                    <span class="text-gray-500">{{ $missing['discipline'] }}</span>
                                </div>
                                <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600">
                                    <x-mary-icon name="o-clock" class="w-3.5 h-3.5" />
                                    {{ $missing['meta'] }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <x-mary-icon name="o-check-circle" class="w-10 h-10 text-emerald-500 mx-auto" />
                            <p class="mt-3 text-sm font-medium text-gray-900">Todos enviaram</p>
                            <p class="mt-1 text-sm text-gray-500">Não há alunos pendentes de envio.</p>
                        </div>
                    @endforelse
                </div>

                <div class="px-6 py-3 border-t border-gray-100 bg-gray-50/50">
                    <p x-show="activeTab === 'submitted'" class="text-xs text-center text-gray-500">Clique em uma entrega para abrir a correção</p>
                    <p x-show="activeTab === 'missing'" x-cloak class="text-xs text-center text-gray-500">Alunos que ainda não enviaram a atividade</p>
                </div>
            </div>
        </div>
    </template>
</div>
