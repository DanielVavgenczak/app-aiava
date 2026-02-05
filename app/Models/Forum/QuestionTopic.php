<?php

namespace App\Models\Forum;

use App\Models\Teacher\Student;
use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class QuestionTopic extends Model
{
    use UuidTrait;
    public $incrementing = false;

    protected $keyType = "uuid";
    protected $fillable = ['student_id', 'teacher_id', 'content', 'forum_topic_id'];


    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function ForumTopic()
    {
        return $this->belongsTo(ForumTopic::class, 'forum_topic_id');
    }
}
