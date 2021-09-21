<?php

// phpcs:ignoreFile

namespace GuzzleHttp;

/**
 * Generates URL-encoded query string.
 *
 * This shim exists to make Guzzle 6 PHP 8.1 compatible.
 *
 * @link https://php.net/manual/en/function.http-build-query.php
 *
 * @param object|array $data
 *   May be an array or object containing properties.
 * @param string|null $numeric_prefix
 *   (optional) If numeric indices are used in the base array and this parameter
 *   is provided, it will be prepended to the numeric index for elements in
 *   the base array only.
 * @param string|null $arg_separator [optional] <p>
 *   (optional) arg_separator.output is used to separate arguments, unless this
 *   parameter is specified, and is then used.
 * @param int $encoding_type
 *   (optional) By default, PHP_QUERY_RFC1738.
 *
 * @return string
 *   A URL-encoded string.
 */
function http_build_query($data, $numeric_prefix = '', $arg_separator = '&', $encoding_type = \PHP_QUERY_RFC1738) {
  return \http_build_query($data, is_null($numeric_prefix) ? '' : $numeric_prefix, $arg_separator, $encoding_type);
}
