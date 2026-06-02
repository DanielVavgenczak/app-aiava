<?php

namespace App\Policies;

use App\Models\Forum\ForumTopic;
use App\Models\Teacher\Teacher;
use App\Models\Teacher\Student;

class ForumTopicPolicy
{
    public function viewAny($user): bool
    {
        return $user instanceof Teacher || $user instanceof Student;
    }

    public function view($user, ForumTopic $topic): bool
    {
        return true;
    }

    public function create($user): bool
    {
        return $user instanceof Teacher || $user instanceof Student;
    }

    public function update($user, ForumTopic $topic): bool
    {
        if ($topic->owner_id === $user->id && $topic->owner_type === get_class($user)) {
            return true;
        }

        return false;
    }

    public function delete($user, ForumTopic $topic): bool
    {
        if ($topic->owner_id === $user->id && $topic->owner_type === get_class($user)) {
            return true;
        }

        if ($user instanceof Teacher && $topic->discipline->teacher_id === $user->id) {
            return true;
        }

        return false;
    }
}
