<?php

namespace App;

use App\Toriom\Translatable;
use Illuminate\Database\Eloquent\Model;
// use Toriomlab\LaravelMultilang\Support\Translatable;

class Product extends Model
{
    use Translatable;
    
    protected $guarded = [];

    protected $translatable = ['name'];
}
