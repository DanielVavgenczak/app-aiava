<?php

namespace App\Models\Teacher;

use App\Models\Discipline;
use App\Models\Traits\UuidTrait;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;
    protected $keyType = "uuid";
    protected $fillable = ['id','name','discipline_id','teacher_id'];

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class);
    }

}
