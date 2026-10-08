<?php

namespace App\Models\Teacher;

use App\Models\Forum\ForumTopic;
use App\Models\Traits\UuidTrait;
use App\Models\Teacher\{Discipline, Student};
use Database\Factories\ClasseFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(ClasseFactory::class)]
class Classe extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;
    protected $keyType = "string";
    protected $fillable = ['name', 'discipline_id', 'teacher_id'];

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class, foreignKey: 'classe_id');
    }

    public function forumTopics()
    {
        return $this->hasMany(related: ForumTopic::class, foreignKey: 'classe_id');
    }
}
