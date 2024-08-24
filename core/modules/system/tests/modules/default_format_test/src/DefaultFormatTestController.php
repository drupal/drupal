<?php

declare(strict_types=1);

namespace Drupal\default_format_test;

use Drupal\Core\Cache\CacheableResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultFormatTestController {

  public function content(Request $request) {
    $format = $request->getRequestFormat();
    return new CacheableResponse('format:' . $format, 200, ['Content-Type' => $request->getMimeType($format)]);
  }

}
