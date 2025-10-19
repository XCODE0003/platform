<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataProvider extends Model
{
    protected $fillable = [
        'code', 'name', 'asset_classes', 'base_url', 'enabled'
    ];

    protected $casts = [
        'asset_classes' => 'array',
        'enabled' => 'boolean',
    ];
}





