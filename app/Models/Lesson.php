<?php

namespace App\Models;

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
        'user_id',
        'title',
        'description',
    ];
    protected $keyType = "uuid";
    public $incrementing = false;

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
