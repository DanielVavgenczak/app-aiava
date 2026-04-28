<?php
// routes/channels.php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Teacher\Classe;
use Illuminate\Support\Facades\Log;

Broadcast::routes(['middleware' => ['web']]);
// Canal privado do usuário (professor ou aluno)
Broadcast::channel('chat-teacher-student.{userId}', function ($user, $userId) {
    // Verifica se é professor
    // dd('Broadcasting calling');
    if (auth()->guard('teacher')->check()) {
        Log::info('Acesso professor ', ['professor' => $user]);
        return (string) auth()->guard('teacher')->id() === (string) $userId;
    }

    // Verifica se é aluno
    if (auth()->guard('student')->check()) {
        Log::info('Acesso aluno: ', ['aluno', $user]);
        return (string) auth()->guard('student')->id() === (string) $userId;
    }

    return false;
}, ['guards' => ['teacher', 'student']]);

// Canal da turma (presence channel - mostra quem está online)
Broadcast::channel('chat.classe.{classeId}', function ($user, $classeId) {
    // Se é professor, verificar se é dono da turma
    // if (auth()->guard('teacher')->check()) {
    //     $teacher = auth()->guard('teacher')->user();
    //     $classe = Classe::find($classeId);

    //     if ($classe && $classe->teacher_id === $teacher->id) {
    //         return [
    //             'id' => $teacher->id,
    //             'name' => $teacher->full_name ?? $teacher->first_name,
    //             'type' => 'teacher',
    //             'avatar' => $teacher->profile?->avatar ?? null,
    //         ];
    //     }
    // }

    // // Se é aluno, verificar se pertence à turma
    // if (auth()->guard('student')->check()) {
    //     $student = auth()->guard('student')->user();

    //     if ($student->classe_id === $classeId) {
    //         return [
    //             'id' => $student->id,
    //             'name' => $student->full_name ?? $student->first_name,
    //             'type' => 'student',
    //             'avatar' => $student->profile?->avatar ?? null,
    //         ];
    //     }
    // }

    return true;
});
