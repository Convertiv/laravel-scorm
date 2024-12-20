<?php


namespace Convertiv\Scorm\Facade;

use Illuminate\Support\Facades\Facade;

class ScormManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'scorm-manager';
    }
}
