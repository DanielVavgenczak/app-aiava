<?php
// app/Models/Chat/Message.php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{MorphTo, BelongsTo};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Teacher\Classe;

class Message extends Model
{
    use SoftDeletes, HasUuids;

    protected $fillable = [
        'classe_id',
        'sender_id',
        'sender_type',
        'receiver_id',
        'receiver_type',
        'content',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Remetente (Professor ou Aluno)
     */
    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Destinatário (Professor ou Aluno)
     */
    public function receiver(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Turma
     */
    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    /**
     * Marcar como lida
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Verifica se foi enviada por professor
     */
    public function isSentByTeacher(): bool
    {
        return str_contains($this->sender_type, 'Teacher');
    }

    /**
     * Verifica se foi enviada por aluno
     */
    public function isSentByStudent(): bool
    {
        return str_contains($this->sender_type, 'Student');
    }

    /**
     * Verifica se é mensagem privada (1-1)
     */
    public function isPrivate(): bool
    {
        return !is_null($this->receiver_id);
    }

    /**
     * Verifica se é mensagem para turma
     */
    public function isGroupMessage(): bool
    {
        return is_null($this->receiver_id);
    }

    /**
     * Scope: Mensagens da turma
     */
    public function scopeForClasse($query, $classeId)
    {
        return $query->where('classe_id', $classeId);
    }

    /**
     * Scope: Mensagens entre dois usuários
     */
    public function scopeBetweenUsers($query, $userId1, $userType1, $userId2, $userType2)
    {
        return $query->where(function ($q) use ($userId1, $userType1, $userId2, $userType2) {
            $q->where('sender_id', $userId1)
                ->where('sender_type', $userType1)
                ->where('receiver_id', $userId2)
                ->where('receiver_type', $userType2);
        })->orWhere(function ($q) use ($userId1, $userType1, $userId2, $userType2) {
            $q->where('sender_id', $userId2)
                ->where('sender_type', $userType2)
                ->where('receiver_id', $userId1)
                ->where('receiver_type', $userType1);
        });
    }

    /**
     * Scope: Mensagens não lidas
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Accessors
     */
    public function getSenderNameAttribute(): string
    {
        return $this->sender?->full_name ?? $this->sender?->first_name ?? 'Desconhecido';
    }

    public function getReceiverNameAttribute(): ?string
    {
        return $this->receiver?->full_name ?? $this->receiver?->first_name ?? null;
    }
}
