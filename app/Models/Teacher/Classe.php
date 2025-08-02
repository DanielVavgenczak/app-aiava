<?php

namespace App\Models\Teacher;

use App\Models\Discipline;
use App\Models\Traits\UuidTrait;
use App\Models\User;
use App\Models\Teacher\{Student};
use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    use UuidTrait;
    public $incrementing = false;
    protected $keyType = "string";
    protected $fillable = ['name','discipline_id','teacher_id'];

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
        return $this->hasMany( Student::class, foreignKey: 'classe_id');
    }

}
