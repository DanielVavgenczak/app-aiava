<?php

namespace App\Models\Teacher;

use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class ActivityNotification extends Model
{
    use UuidTrait;

    public const TYPE_PUBLISHED = 'published';

    public const TYPE_DELIVERED = 'delivered';

    public const TYPE_REVIEWED = 'reviewed';

    public $incrementing = false;

    protected $keyType = 'uuid';

    protected $fillable = [
        'id',
        'notifiable_type',
        'notifiable_id',
        'dedupe_key',
        'type',
        'activit_task_id',
        'student_id',
    ];

    public function notifiable()
    {
        return $this->morphTo();
    }

    public function activitTask()
    {
        return $this->belongsTo(ActivitTask::class, 'activit_task_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
