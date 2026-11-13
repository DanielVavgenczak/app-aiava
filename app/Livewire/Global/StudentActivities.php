<?php

namespace App\Livewire\Global;

use App\Models\Teacher\{ActivityNotification, ActivitTask, Student};
use App\Support\ActivityNotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Computed, Locked, On};
use Livewire\Component;

class StudentActivities extends Component
{
    public const LOGIN_ALERT_SESSION_KEY = 'student.pending-activities-alert';

    #[Locked]
    public ?string $studentId = null;

    public bool $showLoginAlert = false;

    public int $loginPendingCount = 0;

    public function mount(?string $studentId = null): void
    {
        $studentId = $studentId ?? Auth::guard('student')->id();
        $this->studentId = $studentId ? (string) $studentId : null;

        $this->showLoginAlert = (bool) session()->pull(self::LOGIN_ALERT_SESSION_KEY, false);

        if ($this->showLoginAlert) {
            $this->loginPendingCount = $this->availablePendingQuery()->count();
            $this->showLoginAlert = $this->loginPendingCount > 0;
        }
    }

    public function dismissLoginAlert(): void
    {
        $this->showLoginAlert = false;
        $this->loginPendingCount = 0;
    }

    #[On('echo-private:notifications.{studentId},.activity.notification.updated')]
    public function handleRealtime(array $payload = []): void
    {
        $this->pollActivities();

        if (($payload['action'] ?? null) !== 'created') {
            return;
        }

        if (($payload['type'] ?? null) === ActivityNotification::TYPE_PUBLISHED) {
            $this->dispatch('student-activities:new');
        }

        if (($payload['type'] ?? null) === ActivityNotification::TYPE_REVIEWED) {
            $this->dispatch('student-activities:reviewed');
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
        $this->dispatch('student-activities-reviewed-count', count: $this->reviewedQuery($student)->count());
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

    #[Computed]
    public function reviewedCount(): int
    {
        $student = $this->authorizedUser();

        if (! $student) {
            return 0;
        }

        ActivityNotificationService::prune($student);

        return $this->reviewedQuery($student)->count();
    }

    #[Computed]
    public function reviewedActivities(): array
    {
        $student = $this->authorizedUser();

        if (! $student) {
            return [];
        }

        ActivityNotificationService::prune($student);

        return $this->reviewedQuery($student)
            ->with([
                'discipline:id,title',
                'studentAnwsers' => fn ($query) => $query
                    ->forStudent($student->id)
                    ->reviewed()
                    ->latest('updated_at'),
                'activityGrades' => fn ($query) => $query->where('student_id', $student->id),
            ])
            ->latest('created_at')
            ->get()
            ->map(fn (ActivitTask $activity) => $this->presentReviewed($activity))
            ->filter()
            ->sortByDesc('reviewed_at_ts')
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

    public function openReviewedActivity(string $id): void
    {
        $student = $this->authorizedUser();

        if (! $student) {
            return;
        }

        $activity = $this->reviewedQuery($student)->find($id);

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

    protected function availablePendingQuery()
    {
        return $this->pendingQuery()
            ->where(function ($query) {
                $query->whereNull('start')->orWhere('start', '<=', now());
            });
    }

    protected function reviewedQuery(Student $student)
    {
        return ActivitTask::query()->reviewedForStudent($student);
    }

    protected function present(ActivitTask $activity): ?array
    {
        return [
            'id' => (string) $activity->id,
            'activity_id' => (string) $activity->id,
            'discipline_id' => (string) $activity->discipline_id,
            'title' => $this->activityLabel($activity),
            'discipline' => $activity->discipline?->title ?? 'Disciplina',
            'meta' => trim('Pendente · '.$this->deadlineLabel($activity), ' ·'),
        ];
    }

    protected function presentReviewed(ActivitTask $activity): ?array
    {
        $reviewedAt = collect([
            $activity->studentAnwsers->max('updated_at'),
            $activity->activityGrades
                ->map(fn ($grade) => $grade->registered_at ?? $grade->updated_at)
                ->filter()
                ->max(),
        ])->filter()->sortDesc()->first();

        return [
            'id' => (string) $activity->id,
            'activity_id' => (string) $activity->id,
            'discipline_id' => (string) $activity->discipline_id,
            'title' => $this->activityLabel($activity),
            'discipline' => $activity->discipline?->title ?? 'Disciplina',
            'meta' => $reviewedAt
                ? 'Corrigida em '.$reviewedAt->format('d/m/Y')
                : 'Corrigida pelo professor',
            'reviewed_at_ts' => $reviewedAt?->timestamp ?? 0,
        ];
    }

    protected function activityLabel(ActivitTask $activity): string
    {
        $title = trim((string) $activity->title);

        if ($title === '' || str_starts_with($title, 'atividade ')) {
            return 'Atividade';
        }

        return $title;
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
