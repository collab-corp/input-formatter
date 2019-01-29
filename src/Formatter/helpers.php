<?php

use CollabCorp\Formatter\Formatter;



if (! function_exists('formatter')) {
    /**
     * Construct a new Formatter instance
     * @param mixed|null $value
     */
    function formatter($value = null)
    {
        return new Formatter($value);
    }
}