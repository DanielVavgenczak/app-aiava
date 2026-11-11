<?php

namespace App\Livewire\Global;

use App\Models\Teacher\{ActivityNotification, ActivitTask, Student};
use App\Support\ActivityNotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Computed, Locked, On};
use Livewire\Component;

class StudentActivities extends Component
{
    #[Locked]
    public ?string $studentId = null;

    public function mount(?string $studentId = null): void
    {
        $studentId = $studentId ?? Auth::guard('student')->id();
        $this->studentId = $studentId ? (string) $studentId : null;
    }

    #[On('echo-private:notifications.{studentId},.activity.notification.updated')]
    public function handleRealtime(array $payload = []): void
    {
        $this->pollActivities();

        if (($payload['action'] ?? null) === 'created' && ($payload['type'] ?? null) === ActivityNotification::TYPE_PUBLISHED) {
            $this->dispatch('student-activities:new');
        }
    }

    #[On('activity-notifications-updated')]
    public function pollActivities(): void
    {
        $student = $this->authorizedUser();

        if (! $student) {
            return;
        }

        ActivityNotificationService::prune($student);
        $this->dispatch('student-activities-count', count: $this->pendingQuery()->count());
    }

    #[Computed]
    public function pendingCount(): int
    {
        $student = $this->authorizedUser();

        if (! $student) {
            return 0;
        }

        ActivityNotificationService::prune($student);

        return $this->pendingQuery()->count();
    }

    #[Computed]
    public function activities(): array
    {
        $student = $this->authorizedUser();

        if (! $student) {
            return [];
        }

        ActivityNotificationService::prune($student);

        return $this->pendingQuery()
            ->with(['discipline:id,title'])
            ->latest()
            ->get()
            ->map(fn (ActivitTask $activity) => $this->present($activity))
            ->filter()
            ->values()
            ->all();
    }

    public function openActivity(string $id): void
    {
        $student = $this->authorizedUser();

        if (! $student) {
            return;
        }

        $activity = $this->pendingQuery()->find($id);

        if (! $activity) {
            return;
        }

        $this->redirect(ActivityNotificationService::studentUrl($activity), navigate: true);
    }

    protected function authorizedUser(): ?Student
    {
        if (! $this->studentId) {
            return null;
        }

        $user = Auth::guard('student')->user();

        if (! $user || (string) $user->id !== (string) $this->studentId) {
            return null;
        }

        return $user;
    }

    protected function pendingQuery()
    {
        $student = $this->authorizedUser();

        if (! $student) {
            return ActivitTask::query()->whereRaw('1 = 0');
        }

        return ActivitTask::query()
            ->where('discipline_id', $student->discipline_id)
            ->where(function ($query) {
                $query->whereNull('end')->orWhere('end', '>=', now());
            })
            ->whereDoesntHave('studentAnwsers', function ($query) use ($student) {
                $query->where('student_id', $student->id)
                    ->where(function ($query) {
                        $query->whereNotNull('answer_option_id')
                            ->orWhere(function ($query) {
                                $query->whereNotNull('answer_text')
                                    ->whereRaw("TRIM(answer_text) <> ''");
                            });
                    });
            });
    }

    protected function present(ActivitTask $activity): ?array
    {
        return [
            'id' => (string) $activity->id,
            'activity_id' => (string) $activity->id,
            'discipline_id' => (string) $activity->discipline_id,
            'title' => $activity->title,
            'discipline' => $activity->discipline?->title ?? 'Disciplina',
            'meta' => trim('Pendente · '.$this->deadlineLabel($activity), ' ·'),
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

    public function render()
    {
        return view('livewire.global.student-activities');
    }
}
