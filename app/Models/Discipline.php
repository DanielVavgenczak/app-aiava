<?php

namespace App\Models;

use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;
use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discipline extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;

    protected $keyType = "uuid";
    protected $fillable = ['teacher_id','title'];

    public function Teacher()
    {
        return $this->belongsTo(related: Teacher::class);
    }
    public function modules()
    {
        return $this->hasMany(related: Module::class);
    }
    public function students()
    {
        return $this->hasMany(related: Student::class);
    }
}
