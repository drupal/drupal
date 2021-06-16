<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides some helper methods for dealing with the request.
 */
class RequestHelper {

  /**
   * Returns whether the request is using a clean URL.
   *
   * A clean URL is one that does not include the script name. For example,
   * - http://example.com/node/1 is a clean URL.
   * - http://example.com/index.php/node/1 is not a clean URL.
   *
   * Unclean URLs are required on sites hosted by web servers that cannot be
   * configured to implicitly route URLs to index.php.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return bool
   *   TRUE if the request is using a clean URL.
   */
  public static function isCleanUrl(Request $request) {
    $base_url = $request->getBaseUrl();
    return (empty($base_url) || strpos($base_url, $request->getScriptName()) === FALSE);
  }

}
