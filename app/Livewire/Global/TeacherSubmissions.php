<?php

namespace App\Livewire\Global;

use App\Models\Teacher\{ActivityNotification, Student, Teacher};
use App\Support\ActivityNotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Computed, Locked, On};
use Livewire\Component;

class TeacherSubmissions extends Component
{
    #[Locked]
    public ?string $teacherId = null;

    public function mount(?string $teacherId = null): void
    {
        $teacherId = $teacherId ?? Auth::guard('teacher')->id();
        $this->teacherId = $teacherId ? (string) $teacherId : null;
    }

    #[On('echo-private:notifications.{teacherId},.activity.notification.updated')]
    public function handleRealtime(array $payload = []): void
    {
        $this->pollSubmissions();

        if (($payload['action'] ?? null) === 'created' && ($payload['type'] ?? null) === ActivityNotification::TYPE_DELIVERED) {
            $this->dispatch('teacher-submissions:new');
        }
    }

    #[On('activity-notifications-updated')]
    public function pollSubmissions(): void
    {
        $teacher = $this->authorizedUser();

        if (! $teacher) {
            return;
        }

        ActivityNotificationService::prune($teacher);
        $this->dispatch('teacher-submissions-count', count: $this->pendingQuery()->count());
    }

    #[Computed]
    public function pendingCount(): int
    {
        $teacher = $this->authorizedUser();

        if (! $teacher) {
            return 0;
        }

        ActivityNotificationService::prune($teacher);

        return $this->pendingQuery()->count();
    }

    #[Computed]
    public function submissions(): array
    {
        $teacher = $this->authorizedUser();

        if (! $teacher) {
            return [];
        }

        ActivityNotificationService::prune($teacher);

        return $this->pendingQuery()
            ->with([
                'activitTask:id,title,start,end,discipline_id,module_id',
                'student:id,full_name',
                'activitTask.discipline:id,title',
            ])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (ActivityNotification $notification) => $this->present($notification))
            ->filter()
            ->values()
            ->all();
    }

    public function openSubmission(string $id): void
    {
        $teacher = $this->authorizedUser();

        if (! $teacher) {
            return;
        }

        $notification = ActivityNotification::query()
            ->whereKey($id)
            ->where('notifiable_type', Teacher::class)
            ->where('notifiable_id', $teacher->id)
            ->where('type', ActivityNotification::TYPE_DELIVERED)
            ->first();

        if (! $notification || ! $notification->activitTask) {
            return;
        }

        $this->redirect(ActivityNotificationService::teacherUrl($notification->activitTask), navigate: true);
    }

    protected function authorizedUser(): ?Teacher
    {
        if (! $this->teacherId) {
            return null;
        }

        $user = Auth::guard('teacher')->user();

        if (! $user || (string) $user->id !== (string) $this->teacherId) {
            return null;
        }

        return $user;
    }

    protected function pendingQuery()
    {
        return ActivityNotification::query()
            ->where('notifiable_type', Teacher::class)
            ->where('notifiable_id', $this->teacherId)
            ->where('type', ActivityNotification::TYPE_DELIVERED);
    }

    protected function present(ActivityNotification $notification): ?array
    {
        $activity = $notification->activitTask;

        if (! $activity) {
            return null;
        }

        $studentName = $notification->student?->full_name ?? 'Aluno';
        $discipline = $activity->discipline?->title ?? 'Disciplina';
        $age = $notification->created_at?->diffForHumans();

        return [
            'id' => (string) $notification->id,
            'student' => $studentName,
            'activity' => $activity->title,
            'discipline' => $discipline,
            'meta' => 'Aguardando correção'.($age ? ' · '.$age : ''),
        ];
    }

    public function render()
    {
        return view('livewire.global.teacher-submissions');
    }
}
