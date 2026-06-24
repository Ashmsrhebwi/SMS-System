<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptOut extends Model
{
    protected $fillable = ['phone', 'reason'];
}
