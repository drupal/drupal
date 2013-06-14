<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2013 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Util;

/**
 * CSS Utils.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
abstract class CssUtils
{
    const REGEX_URLS            = '/url\((["\']?)(?P<url>.*?)(\\1)\)/';
    const REGEX_IMPORTS         = '/@import (?:url\()?(\'|"|)(?P<url>[^\'"\)\n\r]*)\1\)?;?/';
    const REGEX_IMPORTS_NO_URLS = '/@import (?!url\()(\'|"|)(?P<url>[^\'"\)\n\r]*)\1;?/';
    const REGEX_IE_FILTERS      = '/src=(["\']?)(?P<url>.*?)\\1/';

    /**
     * Filters all references -- url() and "@import" -- through a callable.
     *
     * @param string   $content  The CSS
     * @param callable $callback A PHP callable
     * @param integer  $limit
     * @param integer  $count
     *
     * @return string The filtered CSS
     */
    public static function filterReferences($content, $callback, $limit = -1, &$count = 0)
    {
        $content = static::filterUrls($content, $callback, $limit, $count);
        $content = static::filterImports($content, $callback, $limit, $count, false);
        $content = static::filterIEFilters($content, $callback, $limit, $count);

        return $content;
    }

    /**
     * Filters all CSS url()'s through a callable.
     *
     * @param string   $content  The CSS
     * @param callable $callback A PHP callable
     * @param integer  $limit    Limit the number of replacements
     * @param integer  $count    Will be populated with the count
     *
     * @return string The filtered CSS
     */
    public static function filterUrls($content, $callback, $limit = -1, &$count = 0)
    {
        return preg_replace_callback(static::REGEX_URLS, $callback, $content, $limit, $count);
    }

    /**
     * Filters all CSS imports through a callable.
     *
     * @param string   $content    The CSS
     * @param callable $callback   A PHP callable
     * @param integer  $limit      Limit the number of replacements
     * @param integer  $count      Will be populated with the count
     * @param Boolean  $includeUrl Whether to include url() in the pattern
     *
     * @return string The filtered CSS
     */
    public static function filterImports($content, $callback, $limit = -1, &$count = 0, $includeUrl = true)
    {
        $pattern = $includeUrl ? static::REGEX_IMPORTS : static::REGEX_IMPORTS_NO_URLS;

        return preg_replace_callback($pattern, $callback, $content, $limit, $count);
    }

    /**
     * Filters all IE filters (AlphaImageLoader filter) through a callable.
     *
     * @param string   $content  The CSS
     * @param callable $callback A PHP callable
     * @param integer  $limit    Limit the number of replacements
     * @param integer  $count    Will be populated with the count
     *
     * @return string The filtered CSS
     */
    public static function filterIEFilters($content, $callback, $limit = -1, &$count = 0)
    {
        return preg_replace_callback(static::REGEX_IE_FILTERS, $callback, $content, $limit, $count);
    }

    /**
     * Extracts all references from the supplied CSS content.
     *
     * @param string $content The CSS content
     *
     * @return array An array of unique URLs
     */
    public static function extractImports($content)
    {
        $imports = array();
        static::filterImports($content, function($matches) use(& $imports) {
            $imports[] = $matches['url'];
        });

        return array_unique($imports);
    }

    final private function __construct() { }
}
