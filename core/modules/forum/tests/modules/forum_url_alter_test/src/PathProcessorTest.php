<?php

namespace Drupal\forum_url_alter_test;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor for url_alter_test.
 */
class PathProcessorTest implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    // Rewrite community/ to forum/.
    return preg_replace('@^/community(.*)@', '/forum$1', $path);
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], ?Request $request = NULL, ?BubbleableMetadata $bubbleable_metadata = NULL) {
    // Rewrite forum/ to community/.
    return preg_replace('@^/forum(.*)@', '/community$1', $path);
  }

}
