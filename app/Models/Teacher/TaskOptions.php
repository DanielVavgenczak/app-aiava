<?php

namespace App\Models\Teacher;

use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskOptions extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;
    protected $keyType = "uuid";
    protected $fillable = ['id', 'task_id', 'text', 'correct', 'order'];
    protected $casts = [
        'order' => 'integer'
    ];
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
    // Garanta a ordenação padrão
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
