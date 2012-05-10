<?php

/**
 * @file
 * Definition of Drupal\Core\ContentNegotiation.
 */

namespace Drupal\Core;

use Symfony\Component\HttpFoundation\Request;

/**
 * This class is a central library for content type negotiation.
 *
 * @todo Replace this class with a real content negotiation library based on
 *   mod_negotiation. Development of that is a work in progress.
 */
class ContentNegotiation {

  /**
   * Gets the normalized type of a request.
   *
   * The normalized type is a short, lowercase version of the format, such as
   * 'html', 'json' or 'atom'.
   *
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The request object from which to extract the content type.
   *
   * @return
   *   The normalized type of a given request.
   */
  public function getContentType(Request $request) {
    // AJAX iframe uploads need special handling, because they contain a JSON
    // response wrapped in <textarea>.
    if ($request->get('ajax_iframe_upload', FALSE)) {
      return 'iframeupload';
    }

    // AJAX calls need to be run through ajax rendering functions
    elseif ($request->isXmlHttpRequest()) {
      return 'ajax';
    }

    foreach ($request->getAcceptableContentTypes() as $mime_type) {
      $format = $request->getFormat($mime_type);
      if (!is_null($format)) {
        return $format;
      }
    }

    // Do HTML last so that it always wins.
    return 'html';
  }
}
