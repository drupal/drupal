<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Plugin\PluginManagerBase;

/**
 * Stubs \Drupal\Component\Plugin\FallbackPluginManagerInterface.
 *
 * We have to stub \Drupal\Component\Plugin\FallbackPluginManagerInterface for
 * \Drupal\Tests\Component\Plugin\PluginManagerBaseTest so that we can
 * implement ::getFallbackPluginId().
 *
 * We do this so we can have it just return the plugin ID passed to it, with
 * '_fallback' appended.
 */
class StubFallbackPluginManager extends PluginManagerBase implements FallbackPluginManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    // Minimally implement getFallbackPluginId so that we can test it.
    return $plugin_id . '_fallback';
  }

}
