<?php

namespace CollabCorp\Formatter;

use Carbon\Carbon;
use CollabCorp\Formatter\FormatterMethodParser;

class Formatter
{
    /**
     * The value being formatted.
     * @var $value
     */
    protected $value;

    /**
     * Flag to determine if bcmath extension is
     * installed to avoid checking if bcmath
     * function exists on each call to
     * our math functions during a single request.
     * @var boolean
     */
    protected static $bcMathExtInstalled = null;
    /**
     * Construct a new Formatter instance
     * @param mixed|null $value
     */
    public function __construct($value = null)
    {
        static::$bcMathExtInstalled = is_null(static::$bcMathExtInstalled) ? extension_loaded('bcmath') : static::$bcMathExtInstalled;

        $this->setValue($value);
    }

    /**
     * Cast the formatter to a string,
     * returning the result.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->get();
    }
    /**
     * Mass convert the given data using
     * the specified converters.
     * @param  string $data
     * @param  array  $converters.
     * @return string
     */
    public static function convert($data, array $converters)
    {
        return (new FormatterMethodParser($data, $converters))->getData();
    }
    /**
     * Call a converter method.
     * @param  string $method
     * @param  mixed $value
     * @param  string $args
     * @return mixed
     */
    public static function call($method, $value, $args = [])
    {
        $isObject = is_object($value);
        //if the method doesnt exists on this class and the value is not an object then throw exception.
        if (!method_exists(get_called_class(), $method) && !$isObject) {
            throw new \InvalidArgumentException("Call to undefined converter method [$method]");
        } elseif ($isObject && method_exists($value, $method)) {
            //call the method on the underlying object
            return (new static($value->{$method}(...$args)))->get();
        }

        return (new static($value))->callMethodOnValue($method, $args)->get();
    }
    /**
     * Get the value
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }
    /**
     * Set the value
     * @param mixed $value
     * @return static
     */
    public function setValue($value)
    {
        if ($value === '') {
            $value = null;
        }

        $this->value = $value;

        return $this;
    }

    /**
     * Get the first available value.
     * @return mixed
     */
    public function first()
    {
        if (is_array($this->value) && isset($this->value[0])) {
            return $this->value[0];
        }

        return $this->get();
    }
    /**
     * Create a instance statically.
     * @param  mixed $value
     * @return static
     */
    public static function create($value='')
    {
        return new static($value);
    }

    /**
     * Dynamically call method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return static::create(static::call($method, $this->get(), $parameters));
    }
    /**
     * Determine if the manner formatter
     * methods should be called. Recursively
     * when the value is an array or single
     * when working with a single value.
     * @param  string $method
     * @param  array  $args
     * @return static
     */
    protected function callMethodOnValue($method, $args =[])
    {
        if (is_array($this->value)) {
            $this->value = $this->invokeMethodForArrayValue($this->value, $method, $args);
        } else {
            $this->value = $this->{$method}($this->value, ...$args);
        }

        return $this;
    }
    /**
     * Invoke a method for array value.
     * @param  array $value
     * @param  string $method
     * @param  array  $args
     * @return array $args
     */
    protected function invokeMethodForArrayValue(array $value, $method, $args = [])
    {
        foreach ($value as $key => $val) {
            if (is_array($val)) {
                $value[$key] = $this->invokeMethodForArrayValue($val, $method, $args);
            } else {
                $value[$key] = $this->{$method}($val, ...$args);
            }
        }
        return $value;
    }
    /**
     * Convert the value to a carbon
     * instance.
     * @param  mixed $value
     * @return \Carbon\Carbon
     */
    protected function toCarbon($value)
    {
        return (new Carbon($value));
    }

    /**
     * Explode the value.
     * @return array
     */
    protected function explode($value, $delim = ',')
    {
        if (is_string($value)) {
            $value = explode($delim, $value);
        }
        return $value;
    }
    /**
     * Convert the string value
     * to a boolean value.
     * @param  mixed $value
     * @return boolean
     */
    protected function toBoolean($value)
    {
        $isString = is_string($value);

        $value = $isString ? strtolower($value) : $value;

        if ($value === 'true' || $value === '1' || $value == 'yes') {
            $value = true;
        } elseif ($value === 'false' || $value === '0' || $value == 'no') {
            $value = false;
        } else {
            //everything else filter_var will parse as boolean
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $value;
    }
    /**
     * Remove leading and ending characters.
     * @param  mixed $value
     * @param  string $character
     * @return mixed
     */
    protected function trim($value, $character = ' ')
    {
        $value = trim($value, $character);

        return $value;
    }
    /**
     * Convert the string to all lower case letters.
     * @param  mixed $value
     * @return mixed
     */
    protected function toLower($value)
    {
        $value = mb_strtolower($value);

        return $value;
    }
    /**
    * Convert the string to all lower case letters.
    * @param  mixed $value
    * @return mixed
    */
    protected function toUpper($value)
    {
        $value = mb_strtoupper($value);
        return $value;
    }
    /**
     * Remove leading characters.
     * @param  mixed $value
     * @param  string $character
     * @return mixed
     */
    protected function ltrim($value, $character = ' ')
    {
        $value = ltrim($value, $character);

        return $value;
    }

    /**
     * Truncate off the specifed number of characters.
     * @param  mixed $value
     * @param  int $takeOff
     * @return string
     */
    protected function truncate($value, int $takeOff = 0)
    {
        if(is_string($value)){
            $value = rtrim($value, substr($value, mb_strlen($value) -($takeOff)));
        }
        return $value;
    }
    /**
     * Remove succeeding characters.
     * @param  mixed $value
     * @param  string $character
     * @return string
     */
    protected function rtrim($value, $character = ' ')
    {
        $value = rtrim($value, $character);

        return $value;
    }
    /**
    * Format the value to a pretty phone format (xxx) xxx-xxxx.
    * @param  mixed $value
    * @return mixed
    */
    protected function phone($value)
    {
        if (is_numeric($value)) {
            $len = mb_strlen($value);
            if ($len == '11') {
                $value = substr($value, 0, 1).'('.substr($value, 1, 3).')'.substr($value, 4, 3).'-'.substr($value, 7);
            } elseif ($len == '10') {
                $value = '('.substr($value, 0, 3).')'.substr($value, 3, 3).'-'.substr($value, 6);
            }
        }

        return $value;
    }
    /**
     * Insert a given character after every nth character
     * till we hit the end of the value.
     * @param  mixed $value
     * @param  integer $nth
     * @param  string $insert
     * @return string
     */
    protected function insertEvery($value, int $nth, $insert)
    {
        if(is_string($value)){
            $value = rtrim(chunk_split($value, $nth, $insert), $insert);
        }
        return $value;
    }
    /**
     * Remove everything but letters from the value
     * @return string.
     */
    protected function onlyLetters($value)
    {
        return preg_replace('/\PL/u', '', $value);
    }


    /**
     * Replace all spacing between words to one single space.
     * @param string $value
     * @return static
     */
    protected function singleSpaceBetweenWords($value)
    {
        return preg_replace('/\s+/', ' ', trim($value));
    }
    /**
     * Remove everything but numbers from the value.
     * @param  mixed $value
     * @return string
     */
    protected function onlyNumbers($value)
    {
        return preg_replace('/[^0-9]/', '', $value);
    }

    /**
     * Concatenate a given value to the
     * end of the string.
     * @return string
     */
    protected function suffix($value, $concat)
    {
        return $value.$concat;
    }
    /**
     * Concatenate a given value to the
     * start of the string.
     * @return string
     */
    protected function prefix($value, $concat)
    {
        return $concat.$value;
    }
    /**
     * Remove all non alpha numeric characters
     * including spaces, unless specified.
     * @param  mixed $value
     * @param  boolean $allowSpaces
     * @return static
     */
    protected function onlyAlphaNumeric($value, $allowSpaces = false)
    {
        $regex = $this->toBoolean($allowSpaces) == true ? "/[^0-9a-zA-Z ]/":"/[^0-9a-zA-Z]/";

        $value = preg_replace($regex, "", $value);

        return $value;
    }

    /**
     * Make the value be a decimal of specified places.
     * @param  mixed $value
     * @param  int $numberOfPlaces
     * @return mixed
     */
    protected function decimals($value, int $numberOfPlaces = 2)
    {
        if (is_numeric($value)) {
            $value = number_format($value, $numberOfPlaces, ".", "");
        }

        return $value;
    }
    /**
     * Add a number to the numeric value.
     * @param mixed $value
     * @param mixed $number
     * @param int $scale
     * @return mixed
     */
    protected function add($value, $number, int $scale = 0)
    {
        if (is_numeric($value) && is_numeric($number)) {
            if (!static::$bcMathExtInstalled) {
                $value = $value + $number;
                $value = $this->decimals($value, $scale);
            } else {
                $value = bcadd($value, $number, $scale);
            }
        }
        return $value;
    }
    /**
     * Subtract a number to the numeric value.
     * @param mixed $value
     * @param mixed $number
     * @param int $scale
     * @return mixed
     */
    protected function subtract($value, $number, int $scale = 0)
    {
        if (is_numeric($value) && is_numeric($number)) {
            if (!static::$bcMathExtInstalled) {
                $value = $value - $number;
                $value = $this->decimals($value, $scale);
            } else {
                $value = bcsub($value, $number, $scale);
            }
        }
        return $value;
    }
    /**
     * Multiply a number to the numeric value.
     * @param mixed $value
     * @param mixed $number
     * @param int $scale
     * @return mixed
     */
    protected function multiply($value, $number, int $scale = 0)
    {
        if (is_numeric($value) && is_numeric($number)) {
            if (!static::$bcMathExtInstalled) {
                $value = $value * $number;
                $value = $this->decimals($value, $scale);
            } else {
                $value = bcmul($value, $number, $scale);
            }
        }
        return $value;
    }
    /**
     * Raise a number to the numeric power value.
     * @param mixed $value
     * @param mixed $number
     * @param int $scale
     * @return mixed
     */
    protected function power($value, $number, int $scale = 0)
    {
        if (is_numeric($value) && is_numeric($number)) {
            if (!static::$bcMathExtInstalled) {
                $value = $value ** $number;
                $value = $this->decimals($value, $scale);
            } else {
                $value = bcpow($value, $number, $scale);
            }
        }
        return $value;
    }

    /**
     * Divide the value by the given number.
     * @param mixed $value
     * @param mixed $number
     * @param int $scale
     * @return mixed
     */
    protected function divide($value, $number, int $scale = 0)
    {
        if (is_numeric($value) && is_numeric($number)) {
            if (!static::$bcMathExtInstalled) {
                $value = $value / $number;
                $value = $this->decimals($value, $scale);
            } else {
                $value = bcdiv($value, $number, $scale);
            }
        }
        return $value;
    }
    /**
    * Convert the value to a decimal percent.
    * @param  mixed $value
    * @param  int $scale
    * @return mixed
    */
    protected function decimalPercent($value, int $scale = 2)
    {
        if (is_numeric($value)) {
            $value = $this->divide($value, 100, $scale);
        }

        return $value;
    }
}
