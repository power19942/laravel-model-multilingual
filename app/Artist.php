<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Artist extends LocalizableModel
{
    protected $guarded = [
    ];
    /**
     * Localized attributes.
     *
     * @var array
     */
    protected $localizable = [
        'name',
    ];

    // Expose our suffixed attributes in our model output
    protected $hideLocaleSpecificAttributes = false;
}
