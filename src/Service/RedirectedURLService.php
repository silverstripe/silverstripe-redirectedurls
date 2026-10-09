<?php

namespace SilverStripe\RedirectedURLs\Service;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;
use SilverStripe\RedirectedURLs\Support\Arr;
use SilverStripe\RedirectedURLs\Support\PathEncoding;
use SilverStripe\RedirectedURLs\Support\StatusCode;

class RedirectedURLService implements RedirectedURLInterface
{
    use Extensible;
    use Configurable;
    use Injectable;

    /**
     * When no redirect matches the request, fall back to the redirects for the same From base that require a
     * querystring, provided they all lead to one target (same link and same redirect code).
     *
     * This lets "/old-page" follow the redirects set up for "/old-page?print=1" and "/old-page?format=pdf" when
     * they agree on where the content now lives. When they disagree there is no safe choice and the request stays
     * a 404. Exact matches (with or without a querystring) and wildcard matches always take precedence.
     *
     * Off by default, because it changes which requests are redirected.
     */
    private static bool $frombase_fallback = false;

    public function findBestRedirectedURLMatch(HTTPRequest $request): ?RedirectedURL
    {
        $base = strtolower($request->getURL());
        $getVars = Arr::toLowercase($request->getVars());

        /** @var ArrayList|RedirectedURL[] $listPotentials */
        $listPotentials = new ArrayList();

        // Find all the RedirectedURL objects where the base URL matches.
        // Assumes the base url has no trailing slash.
        // A From base with non-ASCII characters may have been entered literally or percent-encoded, so we look for
        // each form. The path as requested comes first, so a redirect that matches it keeps precedence.
        foreach (PathEncoding::getVariants(rtrim($base, '/')) as $baseVariant) {
            $SQL_base = Convert::raw2sql($baseVariant);

            $potentials = RedirectedURL::get()
                ->filter(['FromBase' => '/' . $SQL_base])
                ->sort('FromQuerystring DESC');

            foreach ($potentials as $potential) {
                if (min($potential->invokeWithExtensions('filterBestRedirectedURLMatch')) === false) {
                    continue;
                }

                $listPotentials->push($potential);
            }
        }

        // Find any matching FromBase elements terminating in a wildcard /*
        $baseParts = explode('/', $base);

        for ($pos = count($baseParts) - 1; $pos >= 0; $pos--) {
            $baseStr = implode('/', array_slice($baseParts, 0, $pos));

            foreach (PathEncoding::getVariants($baseStr) as $baseStrVariant) {
                $basePart = Convert::raw2sql($baseStrVariant . '/*');
                $basePots = RedirectedURL::get()
                    ->filter(['FromBase' => '/' . $basePart])
                    ->sort('FromQuerystring DESC');

                foreach ($basePots as $basePot) {
                    // If the To URL ends in a wildcard /*, append the remaining request URL elements
                    if ($basePot->RedirectionType === 'External' && substr($basePot->To, -2) === '/*') {
                        $basePot->To = substr($basePot->To, 0, -2) . substr($base, strlen($baseStr));
                    }

                    $listPotentials->push($basePot);
                }
            }
        }

        // If there are no potential matches, then the only remaining option is the From base fallback
        if ($listPotentials->count() === 0) {
            return $this->findFromBaseFallbackMatch(rtrim($base, '/'));
        }

        $matched = null;

        // Then check the get vars, ignoring any additional get vars that this URL may have
        foreach ($listPotentials as $potential) {
            $allVarsMatch = true;

            if ($potential->FromQuerystring) {
                $reqVars = array();
                parse_str($potential->FromQuerystring, $reqVars);

                foreach ($reqVars as $k => $v) {
                    if (!$v) {
                        continue;
                    }

                    if (!isset($getVars[$k]) || $v != $getVars[$k]) {
                        $allVarsMatch = false;

                        break;
                    }
                }
            }

            if ($allVarsMatch) {
                $matched = $potential;

                break;
            }
        }

        // If we found a match, we return it - otherwise try the From base fallback, which returns null to
        // indicate that no match was found
        return $matched ?? $this->findFromBaseFallbackMatch(rtrim($base, '/'));
    }

    /**
     * Find the single target shared by all redirects for this From base that require a querystring.
     *
     * Only used when nothing else matched the request, and only when the frombase_fallback config is enabled.
     * Returns null when there are no such redirects, or when they lead to more than one target.
     *
     * @param string $base The request path, lower-cased and without leading or trailing slashes
     */
    protected function findFromBaseFallbackMatch(string $base): ?RedirectedURL
    {
        if (!static::config()->get('frombase_fallback')) {
            return null;
        }

        // The From base may have been entered literally or percent-encoded, and every form counts as the same base
        $fromBases = array_map(fn (string $variant): string => '/' . $variant, PathEncoding::getVariants($base));

        $potentials = RedirectedURL::get()
            ->filter(['FromBase' => $fromBases])
            ->exclude(['FromQuerystring' => [null, '']])
            ->sort('ID');

        $byTarget = [];

        foreach ($potentials as $potential) {
            // Apply the same extension filter as an exact match (e.g. a redirect that belongs to another subsite)
            if (min($potential->invokeWithExtensions('filterBestRedirectedURLMatch')) === false) {
                continue;
            }

            // A redirect whose target can't be resolved (e.g. a deleted page) has no target to agree on
            $link = $potential->Link();

            if (!$link) {
                continue;
            }

            // Two variants only agree when they send the visitor to the same place with the same status code
            $key = StatusCode::getRedirectCode($potential) . ' ' . rtrim($link, '/');
            $byTarget[$key] ??= $potential;
        }

        return count($byTarget) === 1 ? reset($byTarget) : null;
    }

    public function getResponse(RedirectedURL $redirect): HTTPResponse
    {
        $response = HTTPResponse::create()
            ->redirect(Director::absoluteURL($redirect->Link() ?? ''), StatusCode::getRedirectCode($redirect));

        return $response;
    }
}
