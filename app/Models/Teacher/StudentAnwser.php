<?php

namespace App\Models\Teacher;

use App\Models\Traits\UuidTrait;
use Database\Factories\StudentAnwserFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(StudentAnwserFactory::class)]
class StudentAnwser extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;
    protected $keyType = "string";
    protected $fillable = ['id', 'task_id', 'student_id', 'activit_task_id', 'answer_text', 'answer_option_id', 'is_correct', 'grade', 'order', 'corrected', 'attempt_number'];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'grade' => 'decimal:2',
            'corrected' => 'boolean',
            'attempt_number' => 'integer',
            'order' => 'integer',
        ];
    }

    /**
     * return student who answered
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function answerOption()
    {
        return $this->belongsTo(TaskOptions::class, 'answer_option_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(QuestionFeedback::class, 'student_anwser_id');
    }

    public function scopeForStudent(Builder $query, string $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeWithValidAnswer(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereNotNull('answer_option_id')
                ->orWhere(function (Builder $query) {
                    $query->whereNotNull('answer_text')
                        ->whereRaw("TRIM(answer_text) <> ''");
                });
        });
    }

    public function scopeReviewed(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('corrected', true)
                ->orWhereHas('feedbacks');
        });
    }
}
