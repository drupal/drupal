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
    if ($request->isXmlHttpRequest()) {
      if ($request->get('ajax_iframe_upload', FALSE)) {
        return 'iframeupload';
      }
      else {
        return 'ajax';
      }
    }
    elseif (in_array('application/json', $request->getAcceptableContentTypes())) {
      return 'json';
    }
    elseif(in_array('text/html', $acceptable_content_types) || in_array('*/*', $acceptable_content_types)) {
      return 'html';
    }
  }

}

