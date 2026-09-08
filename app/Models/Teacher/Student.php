<?php

namespace App\Models\Teacher;

use App\Models\Forum\ForumTopic;
use App\Models\Traits\UuidTrait;
use App\Traits\GeneratePasswordStudent;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Student extends Authenticatable
{
    use  UuidTrait, Notifiable, GeneratePasswordStudent;

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
        'temporary_password',
        'must_change_password',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'temporary_password'
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => 'string', // ✅ Cast para string
        'password_changed_at' => 'datetime',
    ];

    /**
     * Scope para alunos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function needsPasswordChange(): bool
    {
        return $this->must_change_password;
    }

    /**
     * Gera uma nova senha temporária
     */
    public function generateTemporaryPassword(): string
    {
        $password = $this->generateRandomPassword();

        $this->password = Hash::make($password);
        $this->temporary_password = $password;
        $this->must_change_password = true;
        $this->password_changed_at = null;

        $this->save();

        return $password;
    }

    /**
     * Marca que o aluno alterou a senha
     */
    public function markPasswordChanged(): void
    {
        $this->must_change_password = false;
        $this->temporary_password = null;
        $this->password_changed_at = now();
        $this->save();
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
        return explode(' ', trim($this->full_name))[0];
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




    public function lastName(): string
    {
        $parts = explode(' ', trim($this->full_name));
        return count($parts) > 1 ? end($parts) : '';
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
