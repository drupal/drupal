<?php

namespace Drupal\Core\PathProcessor;

use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path using path alias lookups.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\path_alias\PathProcessor\AliasPathProcessor.
 *
 * @see https://www.drupal.org/node/3092086
 */
class PathProcessorAlias implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * An alias manager for looking up the system path.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a PathProcessorAlias object.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   An alias manager for looking up the system path.
   */
  public function __construct(AliasManagerInterface $alias_manager) {
    $this->aliasManager = $alias_manager;

    // This is used as base class by the new class, so we do not trigger
    // deprecation notices when that or any child class is instantiated.
    $new_class = 'Drupal\path_alias\PathProcessor\AliasPathProcessor';
    if (!is_a($this, $new_class) && class_exists($new_class)) {
      @trigger_error('The \\' . __CLASS__ . ' class is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Instead, use \\' . $new_class . '. See https://drupal.org/node/3092086', E_USER_DEPRECATED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $path = $this->aliasManager->getPathByAlias($path);
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (empty($options['alias'])) {
      $langcode = isset($options['language']) ? $options['language']->getId() : NULL;
      $path = $this->aliasManager->getAliasByPath($path, $langcode);
      // Ensure the resulting path has at most one leading slash, to prevent it
      // becoming an external URL without a protocol like //example.com. This
      // is done in \Drupal\Core\Routing\UrlGenerator::generateFromRoute()
      // also, to protect against this problem in arbitrary path processors,
      // but it is duplicated here to protect any other URL generation code
      // that might call this method separately.
      if (strpos($path, '//') === 0) {
        $path = '/' . ltrim($path, '/');
      }
    }
    return $path;
  }

}
