<?php

namespace Kyledoesdev\Essentials\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Kyledoesdev\Essentials\Essentials
 */
class Essentials extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Kyledoesdev\Essentials\Essentials::class;
    }
}
