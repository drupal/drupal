<?php

/**
 * @file
 * Contains \Drupal\Core\PathProcessor\PathProcessorFront.
 */

namespace Drupal\Core\PathProcessor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path by resolving it to the front page if empty.
 *
 * @todo - remove ::processOutbound() when we remove UrlGenerator::fromPath().
 */
class PathProcessorFront implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * A config factory for retrieving required config settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a PathProcessorFront object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   A config factory for retrieving the site front page configuration.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if ($path === '/') {
      $path = $this->config->get('system.site')->get('page.front');
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    // The special path '<front>' links to the default front page.
    if ($path === '/<front>') {
      $path = '/';
    }
    return $path;
  }

}
