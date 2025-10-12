<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupPair extends Model
{
    protected $fillable = ['name', 'is_active'];
    public function pairs()
    {
        return $this->hasMany(Pair::class, 'group_id');
    }
}
