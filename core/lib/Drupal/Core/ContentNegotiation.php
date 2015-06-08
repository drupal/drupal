<?php

/**
 * @file
 * Definition of Drupal\Core\ContentNegotiation.
 */

namespace Drupal\Core;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides content negotation based upon query parameters.
 */
class ContentNegotiation {

  /**
   * Gets the normalized type of a request.
   *
   * The normalized type is a short, lowercase version of the format, such as
   * 'html', 'json' or 'atom'.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object from which to extract the content type.
   *
   * @return string
   *   The normalized type of a given request.
   */
  public function getContentType(Request $request) {
    // AJAX iframe uploads need special handling, because they contain a JSON
    // response wrapped in <textarea>.
    if ($request->get('ajax_iframe_upload', FALSE)) {
      return 'iframeupload';
    }

    if ($request->query->has('_format')) {
      return $request->query->get('_format');
    }

    if ($request->isXmlHttpRequest()) {
      return 'ajax';
    }

    // Do HTML last so that it always wins.
    return 'html';
  }
}
