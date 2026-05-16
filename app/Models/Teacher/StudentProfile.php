<?php

namespace App\Models\Teacher;

use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    use UuidTrait;
    public $incrementing = false;
    protected $keyType = "uuid";
    protected $fillable = ['student_id', 'avatar', 'avatar_path'];

    public function student()
    {
        return $this->belongsTo(related: Student::class);
    }
}
