<?php

namespace Drupal\url_alter_test;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor for url_alter_test.
 */
class PathProcessor implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (preg_match('!^/user/([^/]+)(/.*)?!', $path, $matches)) {
      if ($account = user_load_by_name($matches[1])) {
        $matches += array(2 => '');
        $path = '/user/' . $account->id() . $matches[2];
      }
    }

    // Rewrite community/ to forum/.
    $path = preg_replace('@^/community(.*)@', '/forum$1', $path);

    if ($path == '/url-alter-test/bar') {
      $path = '/url-alter-test/foo';
    }
    return $path;
  }
}
