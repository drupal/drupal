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
   * Builds a domain-local or external URL from a URI.
   *
   * For actual implementations the logic probably has to be split up between
   * domain-local URIs and external URLs.
   *
   * @param string $uri
   *   A local URI or an external URL being linked to, such as "base://foo"
   *    or "http://example.com/foo".
   *   - If you provide a full URL, it will be considered an external URL as
   *     long as it has an allowed protocol.
   *   - If you provide only a local URI (e.g. "base://foo"), it will be
   *     considered a path local to Drupal, but not handled by the routing
   *     system.  The base path (the subdirectory where the front controller
   *     is found) will be added to the path. Additional query arguments for
   *     local paths must be supplied in $options['query'], not part of $uri.
   *   - If your external URL contains a query (e.g. http://example.com/foo?a=b),
   *     then you can either URL encode the query keys and values yourself and
   *     include them in $uri, or use $options['query'] to let this method
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
   *
   * @throws \InvalidArgumentException
   *   Thrown when the passed in path has no scheme.
   */
  public function assemble($uri, array $options = array());

}
