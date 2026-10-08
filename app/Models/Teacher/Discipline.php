<?php

namespace App\Models\Teacher;

use App\Models\Forum\ForumTopic;
use App\Models\Module;
use App\Models\Teacher\Classe;
use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;
use App\Models\Traits\UuidTrait;
use Database\Factories\DisciplineFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[UseFactory(DisciplineFactory::class)]
class Discipline extends Model
{
    use HasFactory, SoftDeletes, UuidTrait;

    public $incrementing = false;

    protected $keyType = "uuid";
    protected $fillable = ['teacher_id', 'title'];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Compatibilidade: nomes de método em PHP são case-insensitive,
     * portanto $discipline->Teacher() continua funcionando.
     */
    public function teacher()
    {
        return $this->belongsTo(related: Teacher::class);
    }

    /**
     *  Na verdade modules quer dizaer lessons
     */
    public function modules()
    {
        return $this->hasMany(related: Module::class);
    }

    public function students()
    {
        return $this->hasMany(related: Student::class);
    }

    public function forumTopics()
    {
        return $this->hasMany(related: ForumTopic::class);
    }

    public function classes()
    {
        return $this->hasMany(related: Classe::class);
    }

    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = is_string($value) ? trim($value) : $value;
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeNormalized($query, $title)
    {
        return $query->whereRaw('LOWER(TRIM(title)) = ?', [static::normalizeTitle($title)]);
    }

    public static function normalizeTitle(?string $title): string
    {
        return Str::lower(trim((string) $title));
    }

    /**
     * Inclui disciplinas soft-deletadas: o índice único (teacher_id, title)
     * vale para todas as linhas, então a checagem precisa enxergar as excluídas
     * para evitar violação de constraint no banco.
     */
    public static function teacherHasDiscipline($teacher_id, $title)
    {
        return static::withTrashed()
            ->forTeacher($teacher_id)
            ->normalized($title)
            ->exists();
    }
}
