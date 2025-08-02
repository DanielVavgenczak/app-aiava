<?php

namespace App\Models\Teacher;

use App\Models\Module;
use App\Models\Traits\UuidTrait;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;
    protected $keyType = "uuid";
    protected $fillable = ['id', 'question', 'type', 'module_id', 'discipline_id', 'teacher_id'];
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
        return $this->belongsTo(User::class);
    }
}
