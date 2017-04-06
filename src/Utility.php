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
}
