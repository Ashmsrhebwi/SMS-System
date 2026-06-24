<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalBlacklist extends Model
{
    protected $table = 'global_blacklist';

    protected $fillable = ['phone', 'reason'];
}
