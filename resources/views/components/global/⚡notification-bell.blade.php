<?php

use Livewire\Component;
use Livewire\Attributes\{Computed, Locked, On, Renderless};
use App\Models\Teacher\{ActivityNotification, ActivitTask, Student, Teacher};
use App\Support\ActivityNotificationService;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    #[Locked]
    public string $notifiableType = Student::class;

    #[Locked]
    public ?string $notifiableId = null;

    public function mount(string $userType = 'student', ?string $notifiableId = null): void
    {
        $this->notifiableType = $userType === 'teacher' ? Teacher::class : Student::class;
        $this->notifiableId = $notifiableId;
    }

    #[On('echo-private:notifications.{notifiableId},.activity.notification.updated')]
    #[Renderless]
    public function handleRealtime(array $payload = []): void
    {
        $this->pollNotifications();

        if (($payload['action'] ?? null) !== 'created' || empty($payload['toast'])) {
            return;
        }

        if (! $this->authorizedUser()) {
            return;
        }

        $notificationId = $payload['notification_id'] ?? null;

        if ($notificationId && ! ActivityNotification::query()
            ->whereKey($notificationId)
            ->where('notifiable_type', $this->notifiableType)
            ->where('notifiable_id', $this->notifiableId)
            ->exists()) {
            return;
        }

        $this->dispatch('activity-toast', toast: $payload['toast']);
    }

    #[On('activity-notifications-updated')]
    public function pollNotifications(): void
    {
        $user = $this->authorizedUser();

        if (! $user) {
            return;
        }

        ActivityNotificationService::prune($user);

        $this->dispatch('activity-notifications-count', count: $this->visibleQuery()->count());
    }

    #[Computed]
    public function unreadCount(): int
    {
        if (! $this->notifiableId || ! $this->notifiableType) {
            return 0;
        }

        return $this->visibleQuery()->count();
    }

    #[Computed]
    public function notifications(): array
    {
        if (! $this->notifiableId || ! $this->notifiableType) {
            return [];
        }

        return $this->visibleQuery()
            ->with([
                'activitTask:id,title,start,end,discipline_id',
                'student:id,full_name',
            ])
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn (ActivityNotification $notification) => $this->present($notification))
            ->filter()
            ->values()
            ->all();
    }

    public function openNotification(string $id): void
    {
        if (! $this->authorizedUser()) {
            return;
        }

        $notification = ActivityNotification::query()
            ->whereKey($id)
            ->where('notifiable_type', $this->notifiableType)
            ->where('notifiable_id', $this->notifiableId)
            ->first();

        if (! $notification || ! $notification->activitTask) {
            return;
        }

        $this->redirect($this->urlFor($notification), navigate: true);
    }

    protected function authorizedUser(): Student|Teacher|null
    {
        if (! $this->notifiableId) {
            return null;
        }

        $guard = $this->notifiableType === Teacher::class ? 'teacher' : 'student';
        $user = Auth::guard($guard)->user();

        if (! $user || (string) $user->id !== (string) $this->notifiableId) {
            return null;
        }

        return $user;
    }

    protected function visibleQuery()
    {
        $query = ActivityNotification::query()
            ->where('notifiable_type', $this->notifiableType)
            ->where('notifiable_id', $this->notifiableId);

        if ($this->notifiableType === Student::class) {
            $query->where(function ($query) {
                $query->where('type', ActivityNotification::TYPE_DELIVERED)
                    ->orWhereHas('activitTask', function ($query) {
                        $query->where(function ($query) {
                            $query->whereNull('end')->orWhere('end', '>=', now());
                        });
                    });
            });
        }

        return $query;
    }

    protected function present(ActivityNotification $notification): ?array
    {
        $activity = $notification->activitTask;

        if (! $activity) {
            return null;
        }

        $age = $notification->created_at?->diffForHumans();

        if ($notification->type === ActivityNotification::TYPE_PUBLISHED) {
            return [
                'id' => (string) $notification->id,
                'title' => $activity->start?->isFuture() ? 'Nova atividade agendada' : 'Nova atividade',
                'message' => $activity->title,
                'meta' => trim($this->deadlineLabel($activity).' · '.$age, ' ·'),
                'icon' => 'o-document-text',
                'icon_class' => 'bg-blue-50 text-blue-600',
            ];
        }

        $studentName = $notification->student?->full_name ?? 'Aluno';

        return [
            'id' => (string) $notification->id,
            'title' => 'Nova entrega',
            'message' => $studentName.' respondeu "'.$activity->title.'"',
            'meta' => trim('Aguardando correção · '.$age, ' ·'),
            'icon' => 'o-inbox-arrow-down',
            'icon_class' => 'bg-amber-50 text-amber-600',
        ];
    }

    protected function deadlineLabel(ActivitTask $activity): string
    {
        if ($activity->start && $activity->start->isFuture()) {
            return 'Disponível a partir de '.$activity->start->format('d/m/Y');
        }

        if ($activity->end) {
            return 'Prazo: '.$activity->end->format('d/m/Y');
        }

        return 'Sem prazo';
    }

    protected function urlFor(ActivityNotification $notification): string
    {
        $activity = $notification->activitTask;

        if ($notification->type === ActivityNotification::TYPE_PUBLISHED) {
            return ActivityNotificationService::studentUrl($activity);
        }

        return ActivityNotificationService::teacherUrl($activity);
    }
}; ?>

<div
    class="relative"
    x-data="{ open: false, unreadCount: {{ $this->unreadCount }} }"
    @activity-notifications-count.window="unreadCount = $event.detail.count"
>
    <button
        type="button"
        @click="open = !open; if (open) $wire.pollNotifications()"
        class="relative p-2 hover:bg-gray-100 rounded-lg transition-colors"
        title="Notificações"
        aria-label="Notificações"
    >
        <x-mary-icon name="o-bell" class="w-5 h-5 text-gray-600" />
        <span
            x-show="unreadCount > 0"
            x-cloak
            class="absolute -top-0.5 -right-0.5 min-w-5 h-5 px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"
        >
            <span x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
        </span>
    </button>

    <div
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden"
        style="display: none;"
    >
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-800">Notificações</p>
            <span
                x-show="unreadCount > 0"
                x-cloak
                class="text-xs text-gray-500"
                x-text="unreadCount + ' pendente(s)'"
            >{{ $this->unreadCount }} pendente(s)</span>
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($this->notifications as $notification)
                <button
                    type="button"
                    wire:key="notification-{{ $notification['id'] }}"
                    wire:click="openNotification('{{ $notification['id'] }}')"
                    class="w-full text-left px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-0 flex gap-3"
                >
                    <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 {{ $notification['icon_class'] }}">
                        <x-mary-icon :name="$notification['icon']" class="w-5 h-5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-gray-800">{{ $notification['title'] }}</span>
                        <span class="block text-xs text-gray-600 truncate">{{ $notification['message'] }}</span>
                        <span class="block text-[11px] text-gray-400 mt-1">{{ $notification['meta'] }}</span>
                    </span>
                </button>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-500">
                    Nenhuma notificação pendente.
                </div>
            @endforelse
        </div>

        @if ($this->unreadCount > 15)
            <div class="px-4 py-2 text-center text-[11px] text-gray-400 border-t border-gray-100">
                Mostrando as 15 mais recentes
            </div>
        @endif
    </div>

    <div
        x-data="{
            toasts: [],
            seen: {},
            modal: null,
            push(toast) {
                if (!toast || !toast.id) return;

                const key = toast.key || toast.id;
                const now = Date.now();

                if (this.seen[key] && now - this.seen[key] < 60000) return;

                this.seen[key] = now;
                this.toasts.push(toast);

                if (toast.modal) this.modal = toast.modal;

                setTimeout(() => this.remove(toast.id), toast.timeout || 8000);
            },
            remove(id) {
                this.toasts = this.toasts.filter(toast => toast.id !== id);
            },
            closeModal() {
                this.modal = null;
            },
            go(toast) {
                if (!toast.url) {
                    this.remove(toast.id);
                    return;
                }

                this.closeModal();
                this.navigate(toast.url);
                this.remove(toast.id);
            },
            goModal() {
                if (!this.modal?.url) return;

                const url = this.modal.url;
                this.closeModal();
                this.navigate(url);
            },
            navigate(url) {
                if (window.Livewire && typeof window.Livewire.navigate === 'function') {
                    window.Livewire.navigate(url);
                } else {
                    window.location.href = url;
                }
            }
        }"
        @activity-toast.window="push($event.detail.toast)"
        @keydown.escape.window="closeModal()"
    >
        <template x-teleport="body">
            <div class="fixed bottom-44 right-5 z-[999] flex w-80 max-w-[calc(100vw-2.5rem)] flex-col gap-2 pointer-events-none">
                <template x-for="toast in toasts" :key="toast.id">
                    <div class="pointer-events-auto relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
                        <div
                            role="link"
                            tabindex="0"
                            @click="go(toast)"
                            @keydown.enter.prevent="go(toast)"
                            class="flex cursor-pointer items-start gap-3 p-3 pr-9 transition-colors hover:bg-gray-50"
                        >
                            <span
                                class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                                :class="toast.kind === 'published' ? 'bg-blue-50 text-blue-600' : 'bg-amber-50 text-amber-600'"
                            >
                                <span x-show="toast.kind === 'published'">
                                    <x-mary-icon name="o-document-text" class="w-4 h-4" />
                                </span>
                                <span x-show="toast.kind !== 'published'">
                                    <x-mary-icon name="o-inbox-arrow-down" class="w-4 h-4" />
                                </span>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold leading-snug text-gray-800" x-text="toast.title"></span>
                                <span class="mt-0.5 block text-xs leading-snug text-gray-600" x-text="toast.description"></span>
                            </span>
                        </div>

                        <button
                            type="button"
                            @click="remove(toast.id)"
                            class="absolute right-2 top-2 rounded p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                            aria-label="Fechar notificação"
                        >
                            <x-mary-icon name="o-x-mark" class="w-4 h-4" />
                        </button>

                        <div class="h-1 w-full bg-gray-100">
                            <div
                                class="h-full"
                                :class="toast.kind === 'published' ? 'bg-blue-500' : 'bg-amber-500'"
                                :style="`animation: activity-toast-progress ${toast.timeout || 8000}ms linear forwards`"
                            ></div>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <template x-teleport="body">
            <div
                x-show="modal"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[1000] bg-gray-900/50"
                @click.self="closeModal()"
                role="dialog"
                aria-modal="true"
                aria-labelledby="activity-modal-title"
            >
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl sm:w-2/3 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
                    <div class="flex justify-between items-center">
                        <h2 id="activity-modal-title" class="text-lg font-normal text-gray-700" x-text="modal?.title"></h2>
                        <div class="flex justify-end">
                            <x-mary-button
                                icon="o-x-mark"
                                class="bg-white border-none btn-square hover:none hover:bg-pink-600 hover:text-gray-100"
                                x-on:click="closeModal()"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div class="rounded-lg bg-gray-50 p-3">
                            <span class="block text-xs text-gray-500">Atividade</span>
                            <span class="mt-1 block font-medium text-gray-700" x-text="modal?.activity"></span>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3">
                            <span class="block text-xs text-gray-500">Disciplina</span>
                            <span class="mt-1 block font-medium text-gray-700" x-text="modal?.discipline"></span>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3">
                            <span class="block text-xs text-gray-500">Professor</span>
                            <span class="mt-1 block font-medium text-gray-700" x-text="modal?.teacher"></span>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3">
                            <span class="block text-xs text-gray-500">Prazo</span>
                            <span class="mt-1 block font-medium text-gray-700" x-text="modal?.deadline"></span>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 sm:col-span-2">
                            <span class="block text-xs text-gray-500">Tipo</span>
                            <span class="mt-1 block font-medium text-gray-700" x-text="modal?.badge"></span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <x-mary-button
                            label="Fechar"
                            class="btn btn-ghost"
                            x-on:click="closeModal()"
                        />
                        <x-mary-button
                            label="Abrir atividade"
                            class="font-medium text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-600 hover:text-gray-100"
                            x-on:click="goModal()"
                        />
                    </div>
                </div>
            </div>
        </template>
    </div>

    <style>
        @keyframes activity-toast-progress {
            from { width: 100%; }
            to { width: 0%; }
        }
    </style>
</div>
