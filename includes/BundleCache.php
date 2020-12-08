<?php
namespace Ramphor\ProductBundles;

class BundleCache
{
    /**
     * Runtime cache for simple storage.
     *
     * @var array
     */
    protected static $cache = array();

    /**
     * Simple runtime cache getter.
     *
     * @param  string  $key
     * @param  string  $group_key
     * @return mixed
     */
    public static function cache_get($key, $group_key = '')
    {

        $value = null;

        if ($group_key) {
            if ($group_id = self::cache_get($group_key . '_id')) {
                $value = self::cache_get($group_key . '_' . $group_id . '_' . $key);
            }
        } elseif (isset(self::$cache[ $key ])) {
            $value = self::$cache[ $key ];
        }

        return $value;
    }

    /**
     * Simple runtime cache setter.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  string  $group_key
     * @return void
     */
    public static function cache_set($key, $value, $group_key = '')
    {

        if ($group_key) {
            if (null === ( $group_id = self::cache_get($group_key . '_id') )) {
                $group_id = md5($group_key);
                self::cache_set($group_key . '_id', $group_id);
            }

            self::$cache[ $group_key . '_' . $group_id . '_' . $key ] = $value;
        } else {
            self::$cache[ $key ] = $value;
        }
    }
}
