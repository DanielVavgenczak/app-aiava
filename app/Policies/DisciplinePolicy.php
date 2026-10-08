<?php

namespace App\Policies;

use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;

class DisciplinePolicy
{
    public function viewAny($user): bool
    {
        return $user instanceof Teacher;
    }

    public function view($user, Discipline $discipline): bool
    {
        return $this->ownedBy($user, $discipline);
    }

    public function create($user): bool
    {
        return $user instanceof Teacher;
    }

    public function update($user, Discipline $discipline): bool
    {
        return $this->ownedBy($user, $discipline);
    }

    public function delete($user, Discipline $discipline): bool
    {
        return $this->ownedBy($user, $discipline);
    }

    private function ownedBy($user, Discipline $discipline): bool
    {
        return $user instanceof Teacher
            && $discipline->teacher_id === $user->id;
    }
}
