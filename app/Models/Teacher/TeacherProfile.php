<?php

namespace App\Models\Teacher;

use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherProfile extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;
    protected $keyType = "uuid";
    protected $fillable = ['user_id', 'avatar', 'cover', 'avatar_path', 'cover_path'];

    public function teacher()
    {
        return $this->belongsTo(related: Teacher::class);
    }
}
