<?php
// app/Models/Forum/ForumTopic.php

namespace App\Models\Forum;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{HasMany, MorphTo, BelongsTo};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Database\Factories\ForumTopicFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[UseFactory(ForumTopicFactory::class)]
class ForumTopic extends Model
{
    use SoftDeletes, HasUuids, HasFactory;

    protected $fillable = [
        'title',
        'description',
        'discipline_id',
        'classe_id',
        'owner_id',
        'owner_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Respostas do tópico (novo relacionamento)
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ForumReplie::class, 'topic_id');
    }

    /**
     * Apenas respostas diretas (sem parent)
     */
    public function directReplies(): HasMany
    {
        return $this->hasMany(ForumReplie::class, 'topic_id')
            ->whereNull('parent_id')
            ->where('depth', 0)
            ->with(['author', 'children']);
    }

    /**
     * Contagem de respostas
     */
    public function getRepliesCountAttribute(): int
    {
        return $this->replies()->count();
    }

    /**
     * Última resposta
     */
    public function latestReply()
    {
        return $this->hasOne(ForumReplie::class, 'topic_id')->latestOfMany();
    }

    /**
     * Dono do tópico (Professor ou Aluno)
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Disciplina
     */
    public function discipline(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Teacher\Discipline::class);
    }

    /**
     * Classe/Turma
     */
    public function classe(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Teacher\Classe::class);
    }

    /**
     * Verifica se tem respostas
     */
    public function hasReplies(): bool
    {
        return $this->replies()->exists();
    }

    /**
     * Verifica se o usuário respondeu
     */
    public function hasUserReplied($userId, $userType): bool
    {
        return $this->replies()
            ->where('author_id', $userId)
            ->where('author_type', $userType)
            ->exists();
    }

    /**
     * Métodos helper do model
     */
    public function isOwnedByStudent(): bool
    {
        return class_basename($this->owner_type) === 'Student';
    }

    public function isOwnedByTeacher(): bool
    {
        return class_basename($this->owner_type) === 'Teacher';
    }

    public function isMyTopic(?string $studentId = null): bool
    {
        $studentId = $studentId ?? auth()->guard('student')->id();
        return $this->isOwnedByStudent() && $this->owner_id === $studentId;
    }

    public function isNew(int $minutes = 60): bool
    {
        return $this->created_at->diffInMinutes(now()) < $minutes;
    }
}
