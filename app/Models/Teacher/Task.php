<?php

namespace App\Models\Teacher;

use App\Models\Module;
use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;
    protected $keyType = "uuid";
    protected $fillable = ['id', 'question', 'type', 'module_id', 'discipline_id', 'teacher_id','value','order'];
    public function module()
    {
        return $this->belongsTo(Module::class);
    }
    public function options()
    {
        return $this->hasMany(TaskOptions::class);
    }
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function studentAnwsers()
    {
        return $this->hasMany(StudentAnwser::class, 'task_id');
    }
}
