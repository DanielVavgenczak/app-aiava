<?php

namespace App\Models;

use App\Models\Teacher\Teacher;
use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory, UuidTrait;
    protected $fillable = [
        'original_name',
        'hash_name',
        'path_file',
        'module_id',
        'teacher_id',
        'title',
        'description',
        'type',
        'link',
    ];
    protected $keyType = "uuid";
    public $incrementing = false;

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
