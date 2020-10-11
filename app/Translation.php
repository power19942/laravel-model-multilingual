<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $table = 'translations';

    protected $guarded = ['id'];

	public $timestamps = false;

    public function translatable()
    {
        return $this->morphTo();
    }
}
