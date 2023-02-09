<?php

namespace SilverStripe\RedirectedURLs\Support;

use SilverStripe\RedirectedURLs\Model\RedirectedURL;
use SilverStripe\Core\Config\Config;

class StatusCode
{
    public static function getRedirectCode(?RedirectedURL $redirectedURL = null): int
    {
        if ($redirectedURL instanceof RedirectedURL) {
            if (isset($redirectedURL->RedirectCode) && (int) $redirectedURL->RedirectCode > 0) {
                return (int) $redirectedURL->RedirectCode;
            }
        }

        $redirectCode = 301;
        $defaultRedirectCode = intval(Config::inst()->get(RedirectedURL::class, 'default_redirect_code'));

        if ($defaultRedirectCode > 0) {
            $redirectCode = $defaultRedirectCode;
        }

        return $redirectCode;
    }
}
