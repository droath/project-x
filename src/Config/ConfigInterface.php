<?php

namespace Droath\ProjectX\Config;

/**
 * Define configuration interface.
 */
interface ConfigInterface
{
    /**
     * Define to array callback.
     *
     * @return array|string
     *   An array or string that's passed along to the call_user_func_array().
     */
    public static function toArrayCallback();

    /**
     * Define to string callback.
     *
     * @return array|string
     *   An array or string that's passed along to the call_user_func_array().
     */
    public static function toStringCallback();
}
