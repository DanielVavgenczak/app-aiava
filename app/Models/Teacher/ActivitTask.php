<?php

namespace App\Models\Teacher;

use App\Models\Discipline;
use App\Models\Module;
use App\Models\Traits\UuidTrait;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivitTask extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;
    protected $keyType = "uuid";
    protected $fillable = ['id', 'title', 'start', 'end', 'module_id', 'discipline_id', 'teacher_id'];
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
    public function module()
    {
        return $this->belongsTo(Module::class);
    }
    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->title  = 'atividade ' . now()->format('d/m/Y') . Str::uuid()->toString();
        });
    }
}
