<?php 

namespace App\Models\Traits;
use Illuminate\Support\Str;

trait UuidTrait
{
    public static function bootUuidTrait()
    {
        static::creating(function($model){
            $model->{$model->getKeyName()} = (String) Str::uuid();
        });
    }
}