<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Teacher\TeacherProfile;
use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, UuidTrait;

    public $incrementing = false;
    protected $keyType = "uuid";
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
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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

    public function fullName():string
    {
        $full = $this->first_name. ' '. $this->last_name;
        return $full;
    }
    public function initialLetterToAvatar()
    {
        $first = $this->first_name ? Str::substr($this->first_name, 0,1) : '';
        $last = $this->last_name ? Str::substr($this->last_name, 0,1) : '';
        $avatar  = Str::upper($first.$last);
        return $avatar;
    }
}
