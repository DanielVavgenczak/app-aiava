<?php

namespace App\Models\Teacher;

use App\Models\Discipline;
use App\Models\Forum\ForumTopic;
use App\Models\Traits\UuidTrait;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Student extends Authenticatable
{
    use HasFactory, Notifiable, UuidTrait;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'position',
        'full_name',
        'email',
        'phone',
        'classe_id',
        'teacher_id',
        'discipline_id',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    public function discipline()
    {
        return $this->belongsTo(Discipline::class, 'discipline_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function profile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function firstName(): string
    {
        return explode(' ', $this->full_name)[0];
    }
    public function initialLetterToAvatar()
    {
        $parts = explode(' ', $this->full_name);
        $first = array_slice($parts, 0, 2);
        $letras = array_map(fn($p) => strtoupper(substr($p, 0, 1)), $first);

        return implode(' ', $letras);
    }

    public function owner()
    {
        return $this->morphMany(ForumTopic::class, 'owner');
    }

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
