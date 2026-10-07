<?php

namespace SilverStripe\RedirectedURLs\Support;

/**
 * Helps match a request path against a From base that may hold non-ASCII characters either literally (e.g. "é")
 * or percent-encoded (e.g. "%c3%a9").
 *
 * Browsers send non-ASCII characters percent-encoded, while CMS users and CSV imports tend to enter them as typed.
 * Only bytes outside ASCII are converted, so reserved characters such as an encoded slash ("%2f") keep their meaning.
 */
class PathEncoding
{
    /**
     * Returns the forms of a path that a From base could have been entered in: the path as given first, then with
     * its non-ASCII characters decoded, then with them encoded (lower-case hex, as request paths are lower-cased).
     *
     * @return string[]
     */
    public static function getVariants(string $path): array
    {
        return array_values(array_unique([
            $path,
            self::decodeNonAscii($path),
            self::encodeNonAscii($path),
        ]));
    }

    /**
     * Decodes percent-encoded non-ASCII bytes ("%c3%a9" to "é"), leaving a sequence alone when it isn't valid UTF-8
     */
    public static function decodeNonAscii(string $path): string
    {
        return preg_replace_callback(
            '/(?:%[89a-f][0-9a-f])+/i',
            function (array $match): string {
                $decoded = rawurldecode($match[0]);

                return mb_check_encoding($decoded, 'UTF-8') ? $decoded : $match[0];
            },
            $path
        );
    }

    /**
     * Percent-encodes non-ASCII bytes with lower-case hex ("é" to "%c3%a9")
     */
    public static function encodeNonAscii(string $path): string
    {
        return preg_replace_callback(
            '/[\x80-\xff]+/',
            fn (array $match): string => strtolower(rawurlencode($match[0])),
            $path
        );
    }
}
