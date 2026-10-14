<?php

use App\Models\Teacher\Student;
use App\Models\Teacher\StudentProfile;
use App\Models\Teacher\Teacher;
use App\Models\Teacher\TeacherProfile;
use Illuminate\Support\Facades\Storage;

it('retorna fallback quando o aluno não possui avatar', function () {
    $student = Student::factory()->create();

    expect($student->hasAvatar())->toBeFalse()
        ->and($student->avatar_url)->toBe(asset('images/user-noavatar.png'));
});

it('retorna fallback quando o arquivo do avatar do aluno foi removido', function () {
    Storage::fake('public');

    $student = Student::factory()->create();
    StudentProfile::create([
        'student_id' => $student->id,
        'avatar' => 'profiles/inexistente.jpg',
        'avatar_path' => 'profile',
    ]);

    $student = $student->fresh();

    expect($student->hasAvatar())->toBeFalse()
        ->and($student->avatar_url)->toBe(asset('images/user-noavatar.png'));
});

it('retorna a url do aluno quando o arquivo do avatar existe', function () {
    Storage::fake('public');
    Storage::disk('public')->put('profiles/existe.jpg', 'conteudo');

    $student = Student::factory()->create();
    StudentProfile::create([
        'student_id' => $student->id,
        'avatar' => 'profiles/existe.jpg',
        'avatar_path' => 'profile',
    ]);

    $student = $student->fresh();

    expect($student->hasAvatar())->toBeTrue()
        ->and($student->avatar_url)->toBe(asset('storage/profiles/existe.jpg'));
});

it('retorna fallback quando o arquivo do avatar do professor foi removido', function () {
    Storage::fake('public');

    $teacher = Teacher::factory()->create();
    TeacherProfile::create([
        'teacher_id' => $teacher->id,
        'avatar' => 'profiles/inexistente.jpg',
        'avatar_path' => 'profile',
    ]);

    $teacher = $teacher->fresh();

    expect($teacher->hasAvatar())->toBeFalse()
        ->and($teacher->avatar_url)->toBe(asset('images/user-noavatar.png'));
});

it('retorna a url do professor quando o arquivo do avatar existe', function () {
    Storage::fake('public');
    Storage::disk('public')->put('profiles/existe.jpg', 'conteudo');

    $teacher = Teacher::factory()->create();
    $teacher->profile()->update([
        'avatar' => 'profiles/existe.jpg',
        'avatar_path' => 'profile',
    ]);

    $teacher = $teacher->fresh();

    expect($teacher->hasAvatar())->toBeTrue()
        ->and($teacher->avatar_url)->toBe(asset('storage/profiles/existe.jpg'));
});

it('aceita avatar como url completa sem consultar o disco', function () {
    $student = Student::factory()->create();
    StudentProfile::create([
        'student_id' => $student->id,
        'avatar' => 'https://example.com/avatar.jpg',
        'avatar_path' => 'profile',
    ]);

    $student = $student->fresh();

    expect($student->hasAvatar())->toBeTrue()
        ->and($student->avatar_url)->toBe('https://example.com/avatar.jpg');
});
