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

    // Check all formats, if priority format is found return it.
    $first_found_format = FALSE;
    foreach ($request->getAcceptableContentTypes() as $mime_type) {
      $format = $request->getFormat($mime_type);
      if ($format === 'html') {
        return $format;
      }
      if (!is_null($format) && !$first_found_format) {
        $first_found_format = $format;
      }
    }

    // No HTML found, return first found.
    if ($first_found_format) {
      return $first_found_format;
    }

    if ($request->isXmlHttpRequest()) {
      return 'ajax';
    }

    // Do HTML last so that it always wins.
    return 'html';
  }
}
