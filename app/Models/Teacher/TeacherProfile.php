<?php

namespace App\Models\Teacher;

use App\Models\Traits\UuidTrait;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherProfile extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;
    protected $keyType = "uuid";
    protected $fillable = ['user_id','avatar', 'cover','avatar_path', 'cover_path'];

    public function user()
    {
        return $this->belongsTo(related: User::class);
    }   
}
