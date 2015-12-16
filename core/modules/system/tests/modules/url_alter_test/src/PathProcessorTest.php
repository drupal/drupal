<?php

/**
 * @file
 * Contains \Drupal\url_alter_test\PathProcessorTest.
 */

namespace Drupal\url_alter_test;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;

/**
 * Path processor for url_alter_test.
 */
class PathProcessorTest implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    // Rewrite user/username to user/uid.
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

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    // Rewrite user/uid to user/username.
    if (preg_match('!^/user/([0-9]+)(/.*)?!', $path, $matches)) {
      if ($account = User::load($matches[1])) {
        $matches += array(2 => '');
        $path = '/user/' . $account->getUsername() . $matches[2];
        if ($bubbleable_metadata) {
          $bubbleable_metadata->addCacheTags($account->getCacheTags());
        }
      }
    }

    // Rewrite forum/ to community/.
    return preg_replace('@^/forum(.*)@', '/community$1', $path);
  }

}
