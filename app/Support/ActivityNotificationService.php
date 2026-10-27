<?php

namespace App\Support;

use App\Events\ActivityNotificationUpdated;
use App\Models\Teacher\ActivityGrade;
use App\Models\Teacher\ActivityNotification;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Student;
use App\Models\Teacher\StudentAnwser;
use App\Models\Teacher\Teacher;
use Illuminate\Support\Str;

class ActivityNotificationService
{
    public static function publish(ActivitTask $activity): void
    {
        if ($activity->end && $activity->end->isPast()) {
            return;
        }

        $activity->loadMissing(['teacher', 'discipline']);

        $studentIds = Student::query()
            ->where('discipline_id', $activity->discipline_id)
            ->active()
            ->pluck('id');

        if ($studentIds->isEmpty()) {
            return;
        }

        $answeredIds = StudentAnwser::query()
            ->where('activit_task_id', $activity->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->filter(fn (StudentAnwser $answer) => self::hasValidAnswer($answer))
            ->pluck('student_id')
            ->unique()
            ->values();

        if ($answeredIds->isNotEmpty()) {
            ActivityNotification::query()
                ->where('type', ActivityNotification::TYPE_PUBLISHED)
                ->where('activit_task_id', $activity->id)
                ->where('notifiable_type', Student::class)
                ->whereIn('notifiable_id', $answeredIds)
                ->delete();
        }

        $targetIds = $studentIds->diff($answeredIds)->values();

        if ($targetIds->isEmpty()) {
            return;
        }

        $existingIds = ActivityNotification::query()
            ->where('type', ActivityNotification::TYPE_PUBLISHED)
            ->where('activit_task_id', $activity->id)
            ->where('notifiable_type', Student::class)
            ->whereIn('notifiable_id', $targetIds)
            ->pluck('notifiable_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $now = now();
        $rows = [];
        $newNotifications = [];
        $toastBase = self::publishedToastBase($activity);

        foreach ($targetIds as $studentId) {
            if (in_array((string) $studentId, $existingIds, true)) {
                continue;
            }

            $studentId = (string) $studentId;
            $notificationId = (string) Str::uuid();
            $dedupeKey = self::publishedKey($activity->id, $studentId);

            $rows[] = [
                'id' => $notificationId,
                'notifiable_type' => Student::class,
                'notifiable_id' => $studentId,
                'dedupe_key' => $dedupeKey,
                'type' => ActivityNotification::TYPE_PUBLISHED,
                'activit_task_id' => $activity->id,
                'student_id' => $studentId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $newNotifications[] = [
                'student_id' => $studentId,
                'notification_id' => $notificationId,
                'dedupe_key' => $dedupeKey,
                'toast' => [
                    'id' => $notificationId,
                    'key' => $dedupeKey,
                ] + $toastBase,
            ];
        }

        if ($rows === []) {
            return;
        }

        ActivityNotification::insertOrIgnore($rows);

        foreach ($newNotifications as $newNotification) {
            ActivityNotificationUpdated::dispatch(
                $newNotification['student_id'],
                'created',
                (string) $activity->id,
                ActivityNotification::TYPE_PUBLISHED,
                notificationId: $newNotification['notification_id'],
                dedupeKey: $newNotification['dedupe_key'],
                toast: $newNotification['toast'],
            );
        }
    }

    public static function activityAnswered(ActivitTask $activity, Student $student): void
    {
        $removed = ActivityNotification::query()
            ->where('type', ActivityNotification::TYPE_PUBLISHED)
            ->where('activit_task_id', $activity->id)
            ->where('notifiable_type', Student::class)
            ->where('notifiable_id', $student->id)
            ->delete();

        if ($removed > 0) {
            ActivityNotificationUpdated::dispatch(
                (string) $student->id,
                'removed',
                (string) $activity->id,
                ActivityNotification::TYPE_PUBLISHED,
            );
        }

        $teacher = Teacher::find($activity->teacher_id);

        if (! $teacher) {
            return;
        }

        $notification = ActivityNotification::firstOrCreate(
            ['dedupe_key' => self::deliveredKey($teacher->id, $activity->id, $student->id)],
            [
                'notifiable_type' => Teacher::class,
                'notifiable_id' => $teacher->id,
                'type' => ActivityNotification::TYPE_DELIVERED,
                'activit_task_id' => $activity->id,
                'student_id' => $student->id,
            ]
        );

        if ($notification->wasRecentlyCreated) {
            ActivityNotificationUpdated::dispatch(
                (string) $teacher->id,
                'created',
                (string) $activity->id,
                ActivityNotification::TYPE_DELIVERED,
                notificationId: (string) $notification->id,
                dedupeKey: $notification->dedupe_key,
                toast: self::deliveredToast($activity, $student, $notification),
            );
        }
    }

    public static function correctionCompleted(ActivitTask $activity, string $studentId): void
    {
        if (! $activity->teacher_id) {
            return;
        }

        $notification = ActivityNotification::query()
            ->where('dedupe_key', self::deliveredKey($activity->teacher_id, $activity->id, $studentId))
            ->first();

        if (! $notification) {
            return;
        }

        $answers = StudentAnwser::query()
            ->where('activit_task_id', $activity->id)
            ->where('student_id', $studentId)
            ->get();

        if ($answers->isEmpty()) {
            return;
        }

        $allCorrected = $answers->every(fn (StudentAnwser $answer) => (bool) $answer->corrected);
        $hasFinalGrade = ActivityGrade::query()
            ->where('activit_task_id', $activity->id)
            ->where('student_id', $studentId)
            ->exists();

        if (! $allCorrected && ! $hasFinalGrade) {
            return;
        }

        $notification->delete();

        ActivityNotificationUpdated::dispatch(
            (string) $activity->teacher_id,
            'removed',
            (string) $activity->id,
            ActivityNotification::TYPE_DELIVERED,
        );
    }

    public static function forgetActivity(ActivitTask $activity): void
    {
        $notifications = ActivityNotification::query()
            ->where('activit_task_id', $activity->id)
            ->get();

        if ($notifications->isEmpty()) {
            return;
        }

        ActivityNotification::query()
            ->where('activit_task_id', $activity->id)
            ->delete();

        foreach ($notifications as $notification) {
            ActivityNotificationUpdated::dispatch(
                (string) $notification->notifiable_id,
                'removed',
                (string) $activity->id,
                $notification->type,
            );
        }
    }

    public static function prune(Student|Teacher $user): void
    {
        if ($user instanceof Student) {
            self::pruneStudent($user);

            return;
        }

        self::pruneTeacher($user);
    }

    protected static function pruneStudent(Student $student): void
    {
        $activityIds = ActivityNotification::query()
            ->where('notifiable_type', Student::class)
            ->where('notifiable_id', $student->id)
            ->where('type', ActivityNotification::TYPE_PUBLISHED)
            ->pluck('activit_task_id')
            ->unique()
            ->values();

        if ($activityIds->isEmpty()) {
            return;
        }

        $answeredIds = StudentAnwser::query()
            ->where('student_id', $student->id)
            ->whereIn('activit_task_id', $activityIds->all())
            ->get()
            ->filter(fn (StudentAnwser $answer) => self::hasValidAnswer($answer))
            ->pluck('activit_task_id')
            ->unique()
            ->values();

        $closedIds = ActivitTask::query()
            ->whereIn('id', $activityIds->all())
            ->whereNotNull('end')
            ->where('end', '<', now())
            ->pluck('id')
            ->unique();

        $idsToRemove = $answeredIds->merge($closedIds)->unique()->values();

        if ($idsToRemove->isEmpty()) {
            return;
        }

        ActivityNotification::query()
            ->where('notifiable_type', Student::class)
            ->where('notifiable_id', $student->id)
            ->where('type', ActivityNotification::TYPE_PUBLISHED)
            ->whereIn('activit_task_id', $idsToRemove->all())
            ->delete();
    }

    protected static function pruneTeacher(Teacher $teacher): void
    {
        $notifications = ActivityNotification::query()
            ->where('notifiable_type', Teacher::class)
            ->where('notifiable_id', $teacher->id)
            ->where('type', ActivityNotification::TYPE_DELIVERED)
            ->get();

        if ($notifications->isEmpty()) {
            return;
        }

        $activityIds = $notifications->pluck('activit_task_id')->unique()->values()->all();
        $studentIds = $notifications->pluck('student_id')->unique()->values()->all();

        $answersByPair = StudentAnwser::query()
            ->whereIn('activit_task_id', $activityIds)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy(fn (StudentAnwser $answer) => $answer->activit_task_id.'|'.$answer->student_id);

        $gradesByPair = ActivityGrade::query()
            ->whereIn('activit_task_id', $activityIds)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy(fn (ActivityGrade $grade) => $grade->activit_task_id.'|'.$grade->student_id);

        foreach ($notifications as $notification) {
            $pair = $notification->activit_task_id.'|'.$notification->student_id;
            $answers = $answersByPair->get($pair);

            $allCorrected = $answers !== null
                && $answers->isNotEmpty()
                && $answers->every(fn (StudentAnwser $answer) => (bool) $answer->corrected);

            if ($allCorrected || $gradesByPair->has($pair)) {
                $notification->delete();
            }
        }
    }

    public static function studentUrl(ActivitTask $activity): string
    {
        return route('student.lessons', [
            'discipline_id' => $activity->discipline_id,
            'activity' => $activity->id,
        ]).'#open-activity-'.$activity->id;
    }

    public static function teacherUrl(ActivitTask $activity): string
    {
        return route('teacher.discipline.show', [
            'id' => $activity->discipline_id,
            'activity' => $activity->id,
            'tab' => 'deliveries-tab',
        ]);
    }

    protected static function publishedToastBase(ActivitTask $activity): array
    {
        $teacherName = $activity->teacher?->fullName() ?: 'Professor';
        $url = self::studentUrl($activity);
        $scheduled = $activity->start !== null && $activity->start->isFuture();

        return [
            'kind' => ActivityNotification::TYPE_PUBLISHED,
            'title' => ($scheduled ? 'Nova atividade agendada' : 'Nova atividade').' de Prof. '.$teacherName,
            'description' => self::disciplineTitle($activity).' · '.$activity->title.' · '.self::deadlineLabel($activity),
            'url' => $url,
            'timeout' => 8000,
            'modal' => [
                'eyebrow' => $scheduled ? 'Atividade agendada' : 'Nova atividade',
                'title' => $scheduled ? 'Nova atividade agendada' : 'Nova atividade disponível',
                'activity' => $activity->title,
                'discipline' => self::disciplineTitle($activity),
                'teacher' => $teacherName,
                'deadline' => self::deadlineLabel($activity),
                'badge' => $activity->is_avaliative
                    ? 'Avaliativa · '.ActivityValue::format($activity->total_value).' pts'
                    : 'Atividade não avaliativa',
                'url' => $url,
            ],
        ];
    }

    protected static function deliveredToast(ActivitTask $activity, Student $student, ActivityNotification $notification): array
    {
        $studentName = trim((string) $student->full_name) ?: 'Aluno';

        return [
            'id' => (string) $notification->id,
            'key' => $notification->dedupe_key,
            'kind' => ActivityNotification::TYPE_DELIVERED,
            'title' => 'Nova resposta de '.$studentName,
            'description' => $activity->title.' · '.self::disciplineTitle($activity).' · Aguardando correção',
            'url' => self::teacherUrl($activity),
            'timeout' => 8000,
        ];
    }

    protected static function disciplineTitle(ActivitTask $activity): string
    {
        return $activity->discipline?->title ?: 'Disciplina';
    }

    protected static function deadlineLabel(ActivitTask $activity): string
    {
        if ($activity->start && $activity->start->isFuture()) {
            $label = 'Disponível a partir de '.$activity->start->format('d/m/Y');

            return $activity->end
                ? $label.' · entrega até '.$activity->end->format('d/m/Y')
                : $label;
        }

        return $activity->end
            ? 'Entrega até '.$activity->end->format('d/m/Y')
            : 'Sem prazo';
    }

    protected static function hasValidAnswer(StudentAnwser $answer): bool
    {
        if ($answer->answer_option_id !== null) {
            return true;
        }

        return trim((string) $answer->answer_text) !== '';
    }

    protected static function publishedKey(string $activityId, string $studentId): string
    {
        return 'student-published:'.$activityId.':'.$studentId;
    }

    protected static function deliveredKey(string $teacherId, string $activityId, string $studentId): string
    {
        return 'teacher-delivered:'.$teacherId.':'.$activityId.':'.$studentId;
    }
}
