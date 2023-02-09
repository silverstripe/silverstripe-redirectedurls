<?php

namespace SilverStripe\RedirectedURLs\Support;

class Arr
{
    /**
     * Converts an array of key value pairs to lowercase
     */
    public static function toLowercase(array $vars): array
    {
        $result = array();

        foreach ($vars as $k => $v) {
            if (is_array($v)) {
                $result[strtolower($k)] = self::toLowercase($v);
            } else {
                $result[strtolower($k)] = strtolower($v);
            }
        }

        return $result;
    }
}
