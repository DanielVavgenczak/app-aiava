<?php

namespace App\Providers;

use App\Models\Forum\ForumReplie;
use App\Models\Forum\ForumTopic;
use App\Models\Teacher\Teacher;
use App\Models\Teacher\Student;
use App\Policies\ForumRepliePolicy;
use App\Policies\ForumTopicPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        ForumTopic::class => ForumTopicPolicy::class,
        ForumReplie::class => ForumRepliePolicy::class,
    ];

    public function boot(): void
    {
        Gate::define('access-teacher-area', function ($user) {
            return $user instanceof Teacher;
        });

        Gate::define('access-student-area', function ($user) {
            return $user instanceof Teacher || $user instanceof Student;
        });

        Gate::define('manage-forum', function ($user, $topic) {
            if ($user instanceof Teacher) {
                return $topic->discipline->teacher_id === $user->id;
            }
            return false;
        });

        Gate::before(function ($user, $ability) {
            if ($user instanceof Teacher && in_array($ability, ['update', 'delete'])) {
                return null;
            }
            return null;
        });
    }
}
