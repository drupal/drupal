<?php

namespace Drupal\Core;

use Symfony\Component\HttpFoundation\Request;

/**
 * Description of ContentNegotiation
 *
 */
class ContentNegotiation {

  public function getContentType(Request $request) {
    $acceptable_content_types = $request->getAcceptableContentTypes();
    if (in_array('application/json', $request->getAcceptableContentTypes())) {
      return 'json';
    }
    if(in_array('text/html', $acceptable_content_types) || in_array('*/*', $acceptable_content_types)) {
      return 'html';
    }
  }

}

