<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Illuminate\Database\Seeder;

class ActivityTestSeeder extends Seeder
{
    /**
     * Cria uma atividade avaliativa de exemplo para testes manuais:
     * 1 questão de múltipla escolha (com alternativa correta) e 1 dissertativa.
     *
     * Uso: php artisan db:seed --class=ActivityTestSeeder
     */
    public function run(): void
    {
        $teacher = Teacher::factory()->create([
            'first_name' => 'Professor',
            'last_name' => 'Teste',
            'email' => 'professor.teste@example.com',
        ]);

        $discipline = Discipline::factory()->for($teacher)->create([
            'title' => 'Disciplina de Teste',
        ]);

        $module = Module::factory()->create([
            'title' => 'Módulo de Teste',
            'discipline_id' => $discipline->id,
        ]);

        $activity = ActivitTask::factory()
            ->forDiscipline($discipline, $module, $teacher)
            ->create([
                'is_avaliative' => true,
                'total_value' => 100,
                'start' => now()->subDay(),
                'end' => now()->addDays(7),
            ]);

        $multipleChoice = $activity->tasks()->create([
            'question' => 'Qual é a capital do Brasil?',
            'type' => 'multiple_choice',
            'order' => 1,
            'value' => 40,
        ]);

        $multipleChoice->options()->createMany([
            ['text' => 'Rio de Janeiro', 'correct' => false, 'order' => 1],
            ['text' => 'Brasília', 'correct' => true, 'order' => 2],
            ['text' => 'São Paulo', 'correct' => false, 'order' => 3],
            ['text' => 'Salvador', 'correct' => false, 'order' => 4],
        ]);

        $activity->tasks()->create([
            'question' => 'Explique, com suas palavras, o que é uma avaliação formativa.',
            'type' => 'dissertative',
            'order' => 2,
            'value' => 60,
        ]);

        $this->command->info('Atividade de teste criada com sucesso.');
        $this->command->info("Professor: {$teacher->email} (senha: password)");
        $this->command->info("Disciplina: {$discipline->title} ({$discipline->id})");
        $this->command->info("Atividade: {$activity->id} — Total {$activity->total_value}");
        $this->command->info("Questões: {$activity->tasks()->count()}");
    }
}
