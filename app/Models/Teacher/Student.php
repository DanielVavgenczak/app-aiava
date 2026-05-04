<?php

namespace App\Models\Teacher;

use App\Models\Forum\ForumTopic;
use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illmuninate\Broadcasting\PrivateChannel;

class Student extends Authenticatable
{
    use  UuidTrait, Notifiable;

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
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => 'string', // ✅ Cast para string
    ];

    /**
     * Scope para alunos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope para alunos inativos
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Verifica se o aluno está ativo
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verifica se o aluno está inativo
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Ativa o aluno
     */
    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Desativa o aluno
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => 'inactive']);
    }

    /**
     * Alterna o status do aluno
     */
    public function toggleStatus(): bool
    {
        $newStatus = $this->isActive() ? 'inactive' : 'active';
        return $this->update(['status' => $newStatus]);
    }

    /**
     * Retorna a cor do badge baseado no status
     */
    public function getStatusColorAttribute(): string
    {
        return $this->isActive() ? 'green' : 'gray';
    }

    /**
     * Retorna o label do status
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->isActive() ? 'Ativo' : 'Inativo';
    }
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

    public function receivesBroadcastNotificationsOn(): string
    {
        return 'chat-teacher-student.' . $this->id;
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
