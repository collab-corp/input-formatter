<?php

namespace CollabCorp\Formatter\Concerns;

use CollabCorp\Formatter\Formatter;

trait FormatsInput
{
    /**
     * Format the given data.
     * @param  array $data
     * @param  array $formatters
     * @return mixed
     */
    public function convert(array $data, array $formatters)
    {
        return Formatter::convert($data, $formatters);
    }

}
