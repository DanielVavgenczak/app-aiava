<?php

namespace App\Policies;

use App\Models\Forum\ForumReplie;
use App\Models\Teacher\Teacher;
use App\Models\Teacher\Student;

class ForumRepliePolicy
{
    public function viewAny($user): bool
    {
        return $user instanceof Teacher || $user instanceof Student;
    }

    public function view($user, ForumReplie $reply): bool
    {
        return true;
    }

    public function create($user): bool
    {
        return $user instanceof Teacher || $user instanceof Student;
    }

    public function update($user, ForumReplie $reply): bool
    {
        return $reply->author_id === $user->id && $reply->author_type === get_class($user);
    }

    public function delete($user, ForumReplie $reply): bool
    {
        if ($reply->author_id === $user->id && $reply->author_type === get_class($user)) {
            return true;
        }

        if ($user instanceof Teacher && $reply->topic && $reply->topic->discipline->teacher_id === $user->id) {
            return true;
        }

        return false;
    }
}
