<?php

namespace App\Support;

use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\ActivityAccess;
use App\Models\Teacher\Student;
use App\Models\Teacher\StudentAnwser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcula a situação de atividades para um aluno com base nos registros
 * reais do sistema: acesso (activity_accesses), respostas (student_anwsers)
 * e janela de disponibilidade da atividade (start/end).
 *
 * Regras:
 * - Respondida: existe resposta válida; "em atraso" quando o envio ocorreu
 *   depois do prazo (end).
 * - Fora do prazo: sem resposta e o prazo já passou.
 * - Pendente: houve acesso, mas ainda não há resposta.
 * - Não acessada: não existe registro de acesso.
 *
 * Atividades sem prazo (end nulo) nunca são classificadas como fora do prazo.
 */
class ActivityProgress
{
    public const NOT_ACCESSED = 'not_accessed';
    public const PENDING = 'pending';
    public const ANSWERED = 'answered';
    public const OVERDUE = 'overdue';

    /**
     * @param  Collection<int, ActivitTask>  $activities
     * @return array<string, array{
     *     status: string,
     *     late: bool,
     *     answered: bool,
     *     answered_at: ?Carbon,
     *     first_accessed_at: ?Carbon,
     *     deadline: ?Carbon,
     *     available_at: ?Carbon
     * }>
     */
    public static function forStudent(Student $student, Collection $activities, ?Carbon $now = null): array
    {
        $now ??= now();

        $activityIds = $activities->pluck('id')->filter()->all();

        if (empty($activityIds)) {
            return [];
        }

        $accesses = ActivityAccess::query()
            ->where('student_id', $student->id)
            ->whereIn('activit_task_id', $activityIds)
            ->get()
            ->keyBy('activit_task_id');

        $answers = StudentAnwser::query()
            ->selectRaw('activit_task_id, MAX(created_at) as answered_at')
            ->where('student_id', $student->id)
            ->whereIn('activit_task_id', $activityIds)
            ->where(function ($query) {
                $query->whereNotNull('answer_option_id')
                    ->orWhere(function ($query) {
                        $query->whereNotNull('answer_text')
                            ->whereRaw("TRIM(answer_text) <> ''");
                    });
            })
            ->groupBy('activit_task_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->activit_task_id => $row->answered_at ? Carbon::parse($row->answered_at) : null,
            ]);

        $progress = [];

        foreach ($activities as $activity) {
            $access = $accesses->get($activity->id);
            $answeredAt = $answers->get($activity->id);
            $answered = $answeredAt !== null;

            $deadline = $activity->end
                ? Carbon::parse($activity->end)
                : null;

            $late = $answered && $deadline !== null && $answeredAt->gt($deadline);

            if ($answered) {
                $status = self::ANSWERED;
            } elseif ($deadline !== null && $now->gt($deadline)) {
                $status = self::OVERDUE;
            } elseif ($access !== null) {
                $status = self::PENDING;
            } else {
                $status = self::NOT_ACCESSED;
            }

            $availableAt = $activity->start
                ? Carbon::parse($activity->start)
                : null;

            $progress[$activity->id] = [
                'status' => $status,
                'late' => $late,
                'answered' => $answered,
                'answered_at' => $answeredAt,
                'first_accessed_at' => $access?->first_accessed_at,
                'deadline' => $deadline,
                'available_at' => $availableAt,
            ];
        }

        return $progress;
    }

    public static function label(string $status, bool $late = false): string
    {
        return match ($status) {
            self::NOT_ACCESSED => 'Não acessada',
            self::PENDING => 'Pendente',
            self::ANSWERED => $late ? 'Respondida em atraso' : 'Respondida',
            self::OVERDUE => 'Fora do prazo',
            default => '—',
        };
    }

    public static function badgeClass(string $status, bool $late = false): string
    {
        return match ($status) {
            self::ANSWERED => $late ? 'badge-info' : 'badge-success',
            self::PENDING => 'badge-warning',
            self::OVERDUE => 'badge-error',
            default => 'badge-ghost',
        };
    }
}
