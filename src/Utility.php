<?php

namespace Droath\ProjectX;

/**
 * Define a common class for reusable functions.
 */
class Utility
{
    /**
     * Clean input string.
     *
     * @param string $string
     *   The dirty string input.
     *
     * @return string
     *   A string with only lower/uppercase characters.
     */
    public static function cleanString($string)
    {
        if (!is_string($string)) {
            throw new \InvalidArgumentException(
                'A non string argument has been given.'
            );
        }

        return preg_replace('/[^a-zA-Z\-]/', '', $string);
    }

    /**
     * Machine name from string.
     *
     * @param string $string
     *   The machine name input.
     *
     * @return string
     *   The formatted machine name.
     */
    public static function machineName($string)
    {
        if (!is_string($string)) {
            throw new \InvalidArgumentException(
                'A non string argument has been given.'
            );
        }
        $string = strtr($string, ' ', '-');

        return strtolower(self::cleanString($string));
    }

    /**
     * Construct a random hash value.
     *
     * @return string
     *   A hexadecimal representation of the random bytes.
     */
    public static function randomHash()
    {
        return bin2hex(self::randomBytes(55));
    }

   /**
    * Returns a string of highly randomized bytes.
    *
    * @param int $count
    *   The number of characters (bytes) to return in the string.
    *
    * @return string
    *   A randomly generated string.
    */
    public static function randomBytes($count)
    {
        try {
            return random_bytes($count);
        } catch (\Exception $e) {
            throw new \Exception(
                'Unable to generate a random byte.'
            );
        }
    }
}
