<?php

namespace App\Models\Forum;

use App\Models\Discipline;
use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class ForumTopic extends Model
{
    use UuidTrait;
    public $incrementing = false;

    protected $keyType = "uuid";
    protected $fillable = ['discipline_id', 'title', 'content'];

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }
}
