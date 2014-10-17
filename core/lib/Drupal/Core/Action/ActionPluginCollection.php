<?php

/**
 * @file
 * Contains \Drupal\Core\Action\ActionPluginCollection.
 */

namespace Drupal\Core\Action;

use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a container for lazily loading Action plugins.
 */
class ActionPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Action\ActionInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

}
