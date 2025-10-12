<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Pair extends Model
{
   protected $fillable = ['currency_id_in', 'currency_id_out','group_id',  'is_active'];
   public function currencyIn()
   {
      return $this->belongsTo(Currency::class, 'currency_id_in');
   }
   public function currencyOut()
   {
      return $this->belongsTo(Currency::class, 'currency_id_out');
   }
   public function group()
   {
      return $this->belongsTo(GroupPair::class, 'group_id');
   }
}
