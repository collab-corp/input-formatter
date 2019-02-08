<?php


namespace CollabCorp\Formatter;

use CollabCorp\Formatter\Contracts\Formattable;
use CollabCorp\Formatter\Formatter;

/**
 * This class parses a given array and applies
 * any converter methods as defined in the converters
 * parameter. Several methods in this class were taken
 * from the Laravel Illuminate|Support classes for parsing|calling
 * formatter methods conveniently.
 *
 * These methods are authored and credited to the Laravel team:
 * is: \Illuminate\Support\Str::is()
 * set: \Illuminate\Support\Arr:set()
 * get: \Illuminate\Support\Arr:get()
 * @see  https://github.com/illuminate/support
 */
class FormatterMethodParser
{

    /**
     * The data.
     * @var array
     */
    protected $data;
    /**
     * The converters.
     * @var array
     */
    protected $converters;

    public function __construct(array $data, $converters)
    {
        $this->data = $data;
        $this->converters = $converters;

        $this->convertData();
    }
    /**
    * Determine if a given string matches a given pattern.
    * @param  string|array  $patterns
    * @param  string  $value
    * @return boolean
    * @see Laravel Class : \Illuminate\Support\Str@is
    */
    public static function is($patterns, $value)
    {
        if (is_null($patterns)) {
            $patterns =  [];
        }

        $patterns = is_array($patterns) ? $patterns : [$patterns];

        if (empty($patterns)) {
            return false;
        }

        foreach ($patterns as $pattern) {
            // If the given value is an exact match we can of course return true right
            // from the beginning. Otherwise, we will translate asterisks and do an
            // actual pattern match against the two strings to see if they match.
            if ($pattern == $value) {
                return true;
            }

            $pattern = preg_quote($pattern, '#');

            // Asterisks are translated into zero-or-more regular expression wildcards
            // to make it convenient to check if the strings starts with the given
            // pattern such as "library/*", making any string check convenient.
            $pattern = str_replace('\*', '.*', $pattern);

            if (preg_match('#^'.$pattern.'\z#u', $value) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get an item from an array using "dot" notation.
     * Method taken from the Laravel \Illuminate\Support\Arr class
     * @param  \ArrayAccess|array  $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (! is_array($array)) {
            return $default;
        }

        if (is_null($key)) {
            return $array;
        }
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $array[$key] ?? $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }
    /**
     * Get the data.
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
    /**
     * Get the data that matches the given input. key.
     * @param string $inputKey
     * @return array
     */
    protected function extractDataThatMatchesKey(string $inputKey)
    {
        //filter out any of the data that doesnt match the input key
        return array_filter($this->data, function ($key) use ($inputKey) {
            return static::is($inputKey, $key);
        }, ARRAY_FILTER_USE_KEY);
    }
    /**
     * Convert the data.
     * @return array
     */
    protected function convertData()
    {
        foreach ($this->converters as $inputKey => $methods) {
            $methods = is_string($methods) ? explode('|', trim($methods, '|')) : $methods;

            if (!is_array($methods)) {
                $methods = [$methods];
            }

            foreach ($data = $this->extractDataThatMatchesKey($inputKey) as $key => $value) {
                foreach ($methods as $method) {
                    //allow formatter method processing to be bailed if the value is empty.
                    if($method == 'bailIfEmpty' && $this->isEmptyValue($value)){
                        break;
                    }
                    $data = $this->callFormatter($data, $method, $key, $value);
                }
            }
            $this->data = array_merge($this->data, $data);
        }
    }
    /**
     * Is empty value.
     * @param  mixed  $value
     * @return boolean
     */
    protected function isEmptyValue($value)
    {
        if(is_array($value)){
            return empty($value);
        }

        return is_null($value) || $value == '';
    }
    /**
     * Call a formatter method on the given data.
     * @param  array $data
     * @param  mixed $method
     * @param  string $key
     * @param  mixed $value
     * @return array
     */
    protected function callFormatter(array $data, $method, string $key, $value)
    {
        /**
         * Allow the developer to pass closures for manual conversions
         */
        if ($method instanceof \Closure) {
            static::set($data, $key, call_user_func_array($method, [static::get($data, $key)]));
        }
        /**
         * Next allow the developer to format the value via a class
         * that implements our interface.
         */
        elseif (is_string($method) && is_subclass_of($method, Formattable::class)) {
            static::set($data, $key, (new $method)->format(static::get($data, $key)));
        } elseif (is_object($method) && $method instanceof Formattable) {
            static::set($data, $key, $method->format(static::get($data, $key)));
        } else {
            //otherwise parse as a string method and call accordingly.
            $details = $this->parseStringMethod($method);
            static::set($data, $key, Formatter::call($details[0],static::get($data, $key), $details[1]));
        }

        return $data;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     * @see Laravel \Illuminate\Support\Arr@set
     */
    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }
    /**
    * Extract the method/parameters
    * from a string based formatter.
    * @param  string  $formatters
    * @return array
    * @see Laravel class: Illuminate\Validation\ValidationRuleParser@parseStringRule
    */
    protected static function parseStringMethod($method)
    {
        $parameters = [];
        // {method}:{parameters}
        if (strpos($method, ':') !== false) {
            list($method, $parameter) = explode(':', $method);

            $parameters = str_getcsv($parameter);
        }

        return [(trim($method)), $parameters];
    }
}
