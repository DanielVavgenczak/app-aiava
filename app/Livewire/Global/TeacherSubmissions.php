<?php

namespace App\Livewire\Global;

use App\Models\Teacher\{ActivityNotification, ActivitTask, Student, StudentAnwser, Teacher};
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
                'student:id,full_name,classe_id',
                'student.classe:id,name',
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

    #[Computed]
    public function missingSubmissions(): array
    {
        $teacher = $this->authorizedUser();

        if (! $teacher) {
            return [];
        }

        ActivityNotificationService::prune($teacher);

        $notifications = $this->pendingQuery()
            ->with([
                'activitTask:id,title,start,end,discipline_id',
                'activitTask.discipline:id,title',
            ])
            ->latest()
            ->get();

        $activities = $notifications
            ->pluck('activitTask')
            ->filter()
            ->unique('id')
            ->values();

        if ($activities->isEmpty()) {
            return [];
        }

        $submittedPairs = $notifications
            ->mapWithKeys(fn (ActivityNotification $notification) => [
                $notification->activit_task_id.'|'.$notification->student_id => true,
            ])
            ->all();

        StudentAnwser::query()
            ->whereIn('activit_task_id', $activities->pluck('id'))
            ->get()
            ->filter(fn (StudentAnwser $answer) => $this->hasValidAnswer($answer))
            ->each(function (StudentAnwser $answer) use (&$submittedPairs) {
                $submittedPairs[$answer->activit_task_id.'|'.$answer->student_id] = true;
            });

        $students = Student::query()
            ->whereIn('discipline_id', $activities->pluck('discipline_id')->unique()->all())
            ->active()
            ->with('classe:id,name')
            ->orderBy('full_name')
            ->get()
            ->groupBy('discipline_id');

        $missing = [];

        foreach ($activities as $activity) {
            foreach ($students->get($activity->discipline_id, collect()) as $student) {
                if (isset($submittedPairs[$activity->id.'|'.$student->id])) {
                    continue;
                }

                $missing[] = [
                    'student' => $student->full_name,
                    'student_id' => (string) $student->id,
                    'classe' => $student->classe?->name ?? 'Turma não informada',
                    'activity' => $this->activityLabel($activity),
                    'activity_id' => (string) $activity->id,
                    'discipline' => $activity->discipline?->title ?? 'Disciplina',
                    'discipline_id' => (string) $activity->discipline_id,
                    'meta' => $this->deadlineLabel($activity),
                ];

                if (count($missing) >= 50) {
                    break 2;
                }
            }
        }

        return $missing;
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

        return [
            'id' => (string) $notification->id,
            'student' => $studentName,
            'student_id' => (string) $notification->student_id,
            'classe' => $notification->student?->classe?->name ?? 'Turma não informada',
            'activity' => $this->activityLabel($activity),
            'activity_id' => (string) $activity->id,
            'discipline' => $discipline,
            'discipline_id' => (string) $activity->discipline_id,
            'submitted_at' => $notification->created_at?->format('d/m/Y'),
        ];
    }

    protected function hasValidAnswer(StudentAnwser $answer): bool
    {
        if ($answer->answer_option_id !== null) {
            return true;
        }

        return trim((string) $answer->answer_text) !== '';
    }

    protected function deadlineLabel($activity): string
    {
        if ($activity->start && $activity->start->isFuture()) {
            return 'Disponível a partir de '.$activity->start->format('d/m/Y');
        }

        return $activity->end
            ? 'Prazo: '.$activity->end->format('d/m/Y')
            : 'Sem prazo';
    }

    protected function activityLabel(ActivitTask $activity): string
    {
        $title = trim((string) $activity->title);

        if ($title === '' || str_starts_with($title, 'atividade ')) {
            return 'Atividade';
        }

        return $title;
    }

    public function render()
    {
        return view('livewire.global.teacher-submissions');
    }
}
