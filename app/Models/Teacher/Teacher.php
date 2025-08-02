<?php

namespace App\Models\Teacher;

use App\Models\Discipline;
use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Teacher extends Authenticatable
{
    use HasFactory, Notifiable, UuidTrait;
    //protected $table = 'teachers';
    //protected $guard ='teacher';
    public $incrementing = false;
    protected $keyType = "string";
    //protected $guarded = ['teacher'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ]; // Adicione este método
    // public function sessions()
    // {
    //     return $this->morphMany(Session::class, 'user');
    // }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function disciplines()
    {
        return $this->hasMany(Discipline::class);
    }
    public function profile()
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function fullName(): string
    {
        $full = $this->first_name . ' ' . $this->last_name;
        return $full;
    }
    public function initialLetterToAvatar()
    {
        $first = $this->first_name ? Str::substr($this->first_name, 0, 1) : '';
        $last = $this->last_name ? Str::substr($this->last_name, 0, 1) : '';
        $avatar  = Str::upper($first . $last);
        return $avatar;
    }

    /**
     *
     */
    public function getAvatarUrlAttribute()
    {
        if (!$this->profile?->avatar) {
            return asset('images/user-noavatar.png');
        }

        return Storage::exists($this->profile->avatar)
            ? Storage::url($this->profile->avatar)
            : asset('images/user-noavatar.png');
    }
}
