<?php
// routes/channels.php

use App\Models\Teacher\Classe;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['web']]);

// Canal privado do usuário (professor ou aluno)
Broadcast::channel('chat-teacher-student.{userId}', function ($user, $userId) {
    if (auth()->guard('teacher')->check()) {
        return (string) auth()->guard('teacher')->id() === (string) $userId;
    }

    if (auth()->guard('student')->check()) {
        return (string) auth()->guard('student')->id() === (string) $userId;
    }

    return false;
}, ['guards' => ['teacher', 'student']]);

// Canal das notificações de atividades (professor ou aluno)
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    if (auth()->guard('teacher')->check()) {
        return (string) auth()->guard('teacher')->id() === (string) $userId;
    }

    if (auth()->guard('student')->check()) {
        return (string) auth()->guard('student')->id() === (string) $userId;
    }

    return false;
}, ['guards' => ['teacher', 'student']]);

// Canal da turma (presence channel - mostra quem está online)
Broadcast::channel('chat.classe.{classeId}', function ($user, $classeId) {
    // Professor: só acessa se for o dono da turma
    if (auth()->guard('teacher')->check()) {
        $teacher = auth()->guard('teacher')->user();
        $classe = Classe::find($classeId);

        if ($classe && (string) $classe->teacher_id === (string) $teacher->id) {
            return [
                'id' => $teacher->id,
                'name' => $teacher->first_name,
                'type' => 'teacher',
                'avatar' => $teacher->profile?->avatar ?? null,
            ];
        }

        return false;
    }

    // Aluno: só acessa se pertencer à turma
    if (auth()->guard('student')->check()) {
        $student = auth()->guard('student')->user();

        if ((string) $student->classe_id === (string) $classeId) {
            return [
                'id' => $student->id,
                'name' => $student->full_name,
                'type' => 'student',
                'avatar' => $student->profile?->avatar ?? null,
            ];
        }

        return false;
    }

    return false;
}, ['guards' => ['teacher', 'student']]);
