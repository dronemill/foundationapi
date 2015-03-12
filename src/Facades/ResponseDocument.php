<?php

namespace DroneMill\FoundationApi\Facades;

use Illuminate\Support\Facades\Facade;

class ResponseDocument extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'DroneMill\FroundationApi\Contracts\Response\Document';
    }
}
