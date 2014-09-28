<?php
/**
 * @file
 * Contains Drupal\Core\Utility\UnroutedUrlAssemblerInterface.
 */

namespace Drupal\Core\Utility;

/**
 * Provides a way to build external or non Drupal local domain URLs.
 */
interface UnroutedUrlAssemblerInterface {

  /**
   * Builds a domain-local or external URL from a path or URL.
   *
   * For actual implementations the logic probably has to be split up between
   * domain-local and external URLs.
   *
   * @param string $uri
   *   A path on the same domain or external URL being linked to, such as "foo"
   *    or "http://example.com/foo".
   *   - If you provide a full URL, it will be considered an external URL as
   *     long as it has an allowed protocol.
   *   - If you provide only a path (e.g. "foo"), it will be
   *     considered a URL local to the same domain. Additional query
   *     arguments for local paths must be supplied in $options['query'], not
   *     included in $path.
   *   - If your external URL contains a query (e.g. http://example.com/foo?a=b),
   *     then you can either URL encode the query keys and values yourself and
   *     include them in $path, or use $options['query'] to let this method
   *     URL encode them.
   *
   * @param array $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'query': An array of query key/value-pairs (without any URL-encoding) to
   *     append to the URL.
   *   - 'fragment': A fragment identifier (named anchor) to append to the URL.
   *     Do not include the leading '#' character.
   *   - 'absolute': Defaults to FALSE. Whether to force the output to be an
   *     absolute link (beginning with http:). Useful for links that will be
   *     displayed outside the site, such as in an RSS feed.
   *   - 'https': Whether this URL should point to a secure location. If not
   *     defined, the current scheme is used, so the user stays on HTTP or HTTPS
   *     respectively. TRUE enforces HTTPS and FALSE enforces HTTP, but HTTPS can
   *     only be enforced when the variable 'https' is set to TRUE.
   *
   * @return
   *   A string containing a relative or absolute URL.
   */
  public function assemble($uri, array $options = array());

}
