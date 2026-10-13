<?php

use Illuminate\Support\Facades\Schema;

it('define a foreign key de teacher_id na tabela lessons', function () {
    $foreignKeys = collect(Schema::getForeignKeys('lessons'));

    $teacherForeignKey = $foreignKeys->first(
        fn (array $fk) => in_array('teacher_id', $fk['columns'], true)
    );

    expect($teacherForeignKey)->not->toBeNull()
        ->and($teacherForeignKey['foreign_table'])->toBe('teachers');
});
