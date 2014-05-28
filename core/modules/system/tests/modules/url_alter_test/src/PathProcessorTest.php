<?php

/**
 * @file
 * Contains Drupal\url_alter_test\PathProcessorTest.
 */

namespace Drupal\url_alter_test;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor for url_alter_test.
 */
class PathProcessorTest implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * Implements Drupal\Core\PathProcessor\InboundPathProcessorInterface::processInbound().
   */
  public function processInbound($path, Request $request) {
    // Rewrite user/username to user/uid.
    if (preg_match('!^user/([^/]+)(/.*)?!', $path, $matches)) {
      if ($account = user_load_by_name($matches[1])) {
        $matches += array(2 => '');
        $path = 'user/' . $account->id() . $matches[2];
      }
    }

    // Rewrite community/ to forum/.
    if ($path == 'community' || strpos($path, 'community/') === 0) {
      $path = 'forum' . substr($path, 9);
    }

    if ($path == 'url-alter-test/bar') {
      $path = 'url-alter-test/foo';
    }
    return $path;
  }

  /**
   * Implements Drupal\Core\PathProcessor\OutboundPathProcessorInterface::processOutbound().
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL) {
    // Rewrite user/uid to user/username.
    if (preg_match('!^user/([0-9]+)(/.*)?!', $path, $matches)) {
      if ($account = user_load($matches[1])) {
        $matches += array(2 => '');
        $path = 'user/' . $account->getUsername() . $matches[2];
      }
    }

    // Rewrite forum/ to community/.
    if ($path == 'forum' || strpos($path, 'forum/') === 0) {
      $path = 'community' . substr($path, 5);
    }
    return $path;
  }

}
