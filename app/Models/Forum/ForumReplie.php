<?php

namespace App\Models\Forum;

use App\Models\Traits\UuidTrait;
use Database\Factories\ForumReplieFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, MorphTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[UseFactory(ForumReplieFactory::class)]
class ForumReplie extends Model
{
    use SoftDeletes, HasUuids, UuidTrait, HasFactory;

    protected $fillable = [
        'topic_id',
        'parent_id',
        'content',
        'author_id',
        'author_type',
        'depth',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'depth' => 'integer',
    ];

    /**
     * Tópico ao qual a resposta pertence
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class, 'topic_id');
    }

    /**
     * Autor da resposta (Professor ou Aluno - polimórfico)
     */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Resposta pai (para threads aninhados)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ForumReplie::class, 'parent_id');
    }

    /**
     * Respostas filhas (sub-respostas)
     */
    public function children(): HasMany
    {
        return $this->hasMany(ForumReplie::class, 'parent_id')
            ->with('author')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Todas as respostas filhas recursivamente
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Verifica se é uma resposta direta ao tópico
     */
    public function isDirectReply(): bool
    {
        return is_null($this->parent_id) && $this->depth === 0;
    }

    /**
     * Verifica se tem sub-respostas
     */
    public function hasReplies(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Verifica se é do autor especificado
     */
    public function isAuthor($userId, $userType): bool
    {
        return $this->author_id === $userId && $this->author_type === $userType;
    }

    /**
     * Verifica se o usuário logado é o autor
     */
    public function isMyReply(?string $userId = null, string $guard = 'teacher'): bool
    {
        $userId = $userId ?? auth()->guard($guard)->id();
        $userType = $guard === 'teacher'
            ? 'App\Models\Teacher\Teacher'
            : 'App\Models\Teacher\Student';

        return $this->isAuthor($userId, $userType);
    }

    /**
     * Scope para respostas diretas (sem parent)
     */
    public function scopeDirectReplies($query)
    {
        return $query->whereNull('parent_id')->where('depth', 0);
    }

    /**
     * Scope para sub-respostas
     */
    public function scopeSubReplies($query)
    {
        return $query->whereNotNull('parent_id')->where('depth', '>', 0);
    }

    /**
     * Retorna nome do autor
     */
    public function getAuthorNameAttribute(): string
    {
        return $this->author?->full_name ?? $this->author?->first_name ?? 'Desconhecido';
    }

    /**
     * Retorna avatar do autor
     */
    public function getAuthorAvatarAttribute(): ?string
    {
        if ($this->author?->hasAvatar()) {
            return $this->author->avatar_url;
        }

        return null;
    }

    /**
     * Retorna iniciais do autor
     */
    public function getAuthorInitialsAttribute(): string
    {
        $name = $this->author_name;
        $words = explode(' ', $name);

        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[count($words) - 1], 0, 1));
        }

        return strtoupper(substr($name, 0, 1));
    }

    /**
     * Verifica se é professor
     */
    public function isTeacher(): bool
    {
        return str_contains($this->author_type, 'Teacher');
    }

    /**
     * Verifica se é aluno
     */
    public function isStudent(): bool
    {
        return str_contains($this->author_type, 'Student');
    }

    /**
     * Retorna cor do badge baseado no tipo de autor
     */
    public function getAuthorBadgeColorAttribute(): string
    {
        return $this->isTeacher() ? 'blue' : 'green';
    }

    /**
     * Verifica se foi editada
     */
    public function isEdited(): bool
    {
        return $this->created_at->ne($this->updated_at);
    }
}
