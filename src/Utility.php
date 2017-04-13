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
    * Returns a string of highly randomized bytes (over the full 8-bit range).
    *
    * This function is better than simply calling mt_rand() or any other built-in
    * PHP function because it can return a long string of bytes (compared to < 4
    * bytes normally from mt_rand()) and uses the best available pseudo-random
    * source.
    *
    * In PHP 7 and up, this uses the built-in PHP function random_bytes().
    * In older PHP versions, this uses the random_bytes() function provided by
    * the random_compat library, or the fallback hash-based generator.
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
            static $random_state, $bytes;

            if (strlen($bytes) < $count) {
                if (!isset($random_state)) {
                    $random_state = print_r($_SERVER, true);

                    if (function_exists('getmypid')) {
                        $random_state .= getmypid();
                    }
                    $bytes = '';
                    mt_srand();
                }
                do {
                    $random_state = hash('sha256', microtime() . mt_rand() . $random_state);
                    $bytes .= hash('sha256', mt_rand() . $random_state, true);
                } while (strlen($bytes) < $count);
            }
            $output = substr($bytes, 0, $count);
            $bytes = substr($bytes, $count);

            return $output;
        }
    }
}
