<?php

namespace ijeffro\Airports;

use Illuminate\Support\Facades\Facade;

/**
 * AirportsFacade
 *
 */
class AirportsFacade extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'airports'; }

}
