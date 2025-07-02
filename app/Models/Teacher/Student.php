<?php

namespace App\Models\Teacher;

use App\Models\Traits\UuidTrait;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use UuidTrait;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'position',
        'full_name',
        'email',
        'phone',
        'class_id',
        'teacher_id',
        'discipline_id'
    ];

    public function class()
    {
        return $this->belongsTo(Classe::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class);
    }
}
